<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\InventoryLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class OrderBillingService
{
    /**
     * Strictly compute order financial figures from non-cancelled order items.
     *
     * Formula:
     * Net Taxable = max(0, Gross Subtotal - Discount Amount)
     * VAT Amount = is_vat_exempt ? 0 : round(Net Taxable * (VAT Rate / 100), 2)
     * Service Charge = round(Net Taxable * (Service Rate / 100), 2)
     * Grand Total = Net Taxable + VAT Amount + Service Charge
     *
     * @param  Collection<int, OrderItem>  $items
     * @return array{
     *     gross_subtotal: float,
     *     discount_percentage: float,
     *     discount_amount: float,
     *     net_taxable: float,
     *     vat_rate: float,
     *     vat_amount: float,
     *     service_charge_rate: float,
     *     service_charge_amount: float,
     *     grand_total: float
     * }
     */
    public function calculateTotals(
        Collection $items,
        float $discountPercentage = 0.0,
        ?float $vatRate = null,
        ?float $serviceChargeRate = null,
        bool $forceVatExempt = false
    ): array {
        $vatRate ??= (float) config('hotel.vat_rate', 18.00);
        $serviceChargeRate ??= (float) config('hotel.service_charge_rate', 10.00);
        $discountPercentage = max(0.0, min(100.0, round($discountPercentage, 2)));

        $billableItems = $items->filter(
            fn (OrderItem $item): bool => $item->item_status !== 'cancelled'
        );

        $grossSubtotal = round(
            (float) $billableItems->sum(fn (OrderItem $item): float => (float) $item->total_price),
            2
        );

        $taxableGross = round(
            (float) $billableItems->sum(function (OrderItem $item): float {
                $isExempt = (bool) ($item->menuItem?->is_vat_exempt ?? false);

                return $isExempt ? 0.0 : (float) $item->total_price;
            }),
            2
        );

        $discountAmount = round($grossSubtotal * ($discountPercentage / 100), 2);
        $netTaxable = max(0.0, round($grossSubtotal - $discountAmount, 2));

        if ($forceVatExempt || $grossSubtotal <= 0.0 || $taxableGross <= 0.0) {
            $vatAmount = 0.00;
        } elseif (abs($taxableGross - $grossSubtotal) < 0.005) {
            $vatAmount = round($netTaxable * ($vatRate / 100), 2);
        } else {
            $taxableRatio = $taxableGross / $grossSubtotal;
            $netTaxableForVat = round($netTaxable * $taxableRatio, 2);
            $vatAmount = round($netTaxableForVat * ($vatRate / 100), 2);
        }

        $serviceChargeAmount = round($netTaxable * ($serviceChargeRate / 100), 2);
        $grandTotal = round($netTaxable + $vatAmount + $serviceChargeAmount, 2);

        return [
            'gross_subtotal' => $grossSubtotal,
            'discount_percentage' => $discountPercentage,
            'discount_amount' => $discountAmount,
            'net_taxable' => $netTaxable,
            'vat_rate' => $vatRate,
            'vat_amount' => $vatAmount,
            'service_charge_rate' => $serviceChargeRate,
            'service_charge_amount' => $serviceChargeAmount,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Recalculate and persist financial totals on an Order instance.
     */
    public function recalculateOrder(Order $order, ?float $discountPercentage = null): Order
    {
        $order->loadMissing('batches.items.menuItem');

        $allItems = $order->batches->flatMap(fn (OrderBatch $batch) => $batch->items);
        $discountPct = $discountPercentage ?? (float) $order->discount_percentage;

        $vatRate = (float) ($order->vat_rate > 0 ? $order->vat_rate : config('hotel.vat_rate', 18.00));
        $serviceRate = (float) ($order->service_charge_rate > 0 ? $order->service_charge_rate : config('hotel.service_charge_rate', 10.00));

        $totals = $this->calculateTotals($allItems, $discountPct, $vatRate, $serviceRate);

        $order->fill([
            'gross_subtotal' => $totals['gross_subtotal'],
            'discount_percentage' => $totals['discount_percentage'],
            'discount_amount' => $totals['discount_amount'],
            'vat_rate' => $totals['vat_rate'],
            'vat_amount' => $totals['vat_amount'],
            'service_charge_rate' => $totals['service_charge_rate'],
            'service_charge_amount' => $totals['service_charge_amount'],
            'grand_total' => $totals['grand_total'],
        ]);

        $order->save();

        return $order;
    }

    /**
     * Lock requested menu items, verify availability/stock, persist order items, and log inventory deductions.
     *
     * @param  array<int, array{menu_item_id: int|string, quantity: int|string, notes?: ?string}>  $cartItems
     * @return Collection<int, OrderItem>
     *
     * @throws ValidationException
     */
    public function attachItemsToBatchWithLock(Order $order, OrderBatch $batch, array $cartItems): Collection
    {
        $requestedByMenuId = collect($cartItems)
            ->groupBy(fn (array $row): int => (int) $row['menu_item_id'])
            ->map(fn (Collection $rows): int => (int) $rows->sum(fn (array $r): int => (int) $r['quantity']));

        $lockedMenuItems = MenuItem::query()
            ->whereIn('id', $requestedByMenuId->keys()->all())
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        foreach ($requestedByMenuId as $menuItemId => $totalRequestedQty) {
            /** @var ?MenuItem $menuItem */
            $menuItem = $lockedMenuItems->get($menuItemId);

            if ($menuItem === null || ! $menuItem->is_available) {
                $name = $menuItem?->name ?? "Item #{$menuItemId}";

                throw ValidationException::withMessages([
                    'items' => ["{$name} is currently unavailable."],
                ]);
            }

            if ($menuItem->current_stock !== -1 && $menuItem->current_stock < $totalRequestedQty) {
                throw ValidationException::withMessages([
                    'items' => ["Insufficient stock for {$menuItem->name} (Remaining: {$menuItem->current_stock})."],
                ]);
            }
        }

        $createdItems = collect();

        foreach ($cartItems as $line) {
            /** @var MenuItem $menuItem */
            $menuItem = $lockedMenuItems->get((int) $line['menu_item_id']);
            $qty = (int) $line['quantity'];
            $unitPrice = round((float) $menuItem->price, 2);
            $totalPrice = round($unitPrice * $qty, 2);

            $orderItem = OrderItem::create([
                'order_batch_id' => $batch->id,
                'menu_item_id' => $menuItem->id,
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'total_price' => $totalPrice,
                'notes' => isset($line['notes']) && $line['notes'] !== ''
                    ? strip_tags((string) $line['notes'])
                    : null,
                'item_status' => $batch->status === 'confirmed' ? 'pending' : 'pending',
            ]);

            if ($menuItem->current_stock !== -1) {
                $menuItem->decrement('current_stock', $qty);
                $menuItem->refresh();

                if ($menuItem->current_stock <= 0) {
                    $menuItem->update(['is_available' => false]);
                }
            }

            InventoryLog::create([
                'menu_item_id' => $menuItem->id,
                'order_id' => $order->id,
                'quantity_change' => -$qty,
                'reason' => 'sale',
            ]);

            $createdItems->push($orderItem);
        }

        return $createdItems;
    }

    /**
     * Verify a Manager or Admin PIN code against active users with role manager or admin.
     */
    public function verifyManagerOrAdminPin(string $plainPin): ?User
    {
        if (trim($plainPin) === '') {
            return null;
        }

        $authorizers = User::query()
            ->active()
            ->whereNotNull('pin_code')
            ->whereHas('role', function ($query): void {
                $query->whereIn('name', [Role::ADMIN, Role::MANAGER]);
            })
            ->with('role')
            ->get();

        foreach ($authorizers as $authorizer) {
            if (Hash::check($plainPin, (string) $authorizer->pin_code)) {
                return $authorizer;
            }
        }

        return null;
    }

    /**
     * Record a security / financial audit log entry.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function recordAudit(
        string $eventType,
        Model $auditable,
        ?array $oldValues,
        ?array $newValues,
        ?int $userId,
        string $ipAddress = '127.0.0.1'
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'event_type' => $eventType,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => (int) $auditable->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => substr($ipAddress, 0, 45),
        ]);
    }
}
