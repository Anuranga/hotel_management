<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class KitchenController extends Controller
{
    /**
     * Render the Kitchen Display System (KDS) screen or return non-financial polling JSON.
     */
    public function index(Request $request): View|JsonResponse
    {
        $stationFilter = $request->query('station', 'all');

        if ($request->expectsJson() || $request->boolean('poll')) {
            return response()->json($this->buildKdsPayload((string) $stationFilter));
        }

        return view('kitchen.kds', [
            'initialPayload' => $this->buildKdsPayload((string) $stationFilter),
            'stationFilter' => $stationFilter,
        ]);
    }

    /**
     * Transition an OrderBatch to 'preparing' (Mark Cooking) or 'ready' (Mark Ready).
     */
    public function updateBatchStatus(Request $request, OrderBatch $batch): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:preparing,ready'],
        ]);

        DB::transaction(function () use ($batch, $validated): void {
            $lockedBatch = OrderBatch::query()
                ->whereKey($batch->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder = Order::query()
                ->whereKey($lockedBatch->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $newBatchStatus = $validated['status'];
            $lockedBatch->update(['status' => $newBatchStatus]);

            $targetItemStatus = $newBatchStatus === 'preparing' ? 'cooking' : 'ready';

            $lockedBatch->items()
                ->whereNotIn('item_status', ['served', 'cancelled'])
                ->update(['item_status' => $targetItemStatus]);

            if ($newBatchStatus === 'preparing' && in_array($lockedOrder->order_status, ['pending_confirmation', 'confirmed'], true)) {
                $lockedOrder->update(['order_status' => 'preparing']);
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Batch #{$batch->batch_number} updated to {$validated['status']}.",
                'payload' => $this->buildKdsPayload((string) $request->query('station', 'all')),
            ]);
        }

        return redirect()->back()->with('status', "Batch #{$batch->batch_number} marked as {$validated['status']}.");
    }

    /**
     * Transition an individual OrderItem between 'cooking' and 'ready'.
     */
    public function updateItemStatus(Request $request, OrderItem $item): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'item_status' => ['required', 'in:cooking,ready'],
        ]);

        DB::transaction(function () use ($item, $validated): void {
            $lockedItem = OrderItem::query()
                ->whereKey($item->id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedBatch = OrderBatch::query()
                ->whereKey($lockedItem->order_batch_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedOrder = Order::query()
                ->whereKey($lockedBatch->order_id)
                ->lockForUpdate()
                ->firstOrFail();

            $lockedItem->update(['item_status' => $validated['item_status']]);

            $remainingUnready = $lockedBatch->items()
                ->whereNotIn('item_status', ['ready', 'served', 'cancelled'])
                ->exists();

            if (! $remainingUnready) {
                $lockedBatch->update(['status' => 'ready']);
            } elseif ($validated['item_status'] === 'cooking') {
                $lockedBatch->update(['status' => 'preparing']);

                if (in_array($lockedOrder->order_status, ['pending_confirmation', 'confirmed'], true)) {
                    $lockedOrder->update(['order_status' => 'preparing']);
                }
            }
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Item status updated to {$validated['item_status']}.",
                'payload' => $this->buildKdsPayload((string) $request->query('station', 'all')),
            ]);
        }

        return redirect()->back()->with('status', 'Item preparation status updated.');
    }

    /**
     * Build the KDS card payload strictly omitting all financial figures and pricing.
     *
     * @return array<string, mixed>
     */
    private function buildKdsPayload(string $stationFilter = 'all'): array
    {
        $batches = OrderBatch::query()
            ->whereIn('status', ['confirmed', 'preparing', 'ready'])
            ->whereHas('order', function ($query): void {
                $query->whereIn('order_status', ['confirmed', 'preparing', 'served']);
            })
            ->with([
                'order.restaurantTable',
                'items' => function ($query): void {
                    $query->where('item_status', '!=', 'cancelled')
                        ->with('menuItem.category');
                },
            ])
            ->orderBy('created_at')
            ->get();

        $cards = $batches->map(function (OrderBatch $batch) use ($stationFilter): ?array {
            $filteredItems = $batch->items->filter(function (OrderItem $item) use ($stationFilter): bool {
                if ($stationFilter === 'all' || $stationFilter === '') {
                    return true;
                }

                return ($item->menuItem?->category?->station ?? 'kitchen') === $stationFilter;
            });

            if ($filteredItems->isEmpty()) {
                return null;
            }

            return [
                'batch_id' => $batch->id,
                'order_id' => $batch->order_id,
                'table_number' => $batch->order?->restaurantTable?->table_number ?? 'N/A',
                'batch_number' => $batch->batch_number,
                'batch_status' => $batch->status,
                'elapsed_human' => $batch->created_at?->diffForHumans(null, true) ?? 'Just now',
                'created_time' => $batch->created_at?->format('h:i A'),
                'items' => $filteredItems->map(function (OrderItem $item): array {
                    return [
                        'id' => $item->id,
                        'name' => $item->menuItem?->name ?? 'Menu Item',
                        'station' => $item->menuItem?->category?->station ?? 'kitchen',
                        'quantity' => $item->quantity,
                        'notes' => $item->notes,
                        'item_status' => $item->item_status,
                    ];
                })->values()->all(),
            ];
        })->filter()->values()->all();

        return [
            'cards_count' => count($cards),
            'cards' => $cards,
            'polled_at' => now()->format('h:i:s A'),
        ];
    }
}
