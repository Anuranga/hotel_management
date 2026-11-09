<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpsertCategoryRequest;
use App\Http\Requests\UpsertMenuItemRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\InventoryLog;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RestaurantTable;
use App\Services\OrderBillingService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AdminReportController extends Controller
{
    public function __construct(
        public OrderBillingService $billingService
    ) {}

    /**
     * Render the unified Management & Administrative Console (Reports, Menu CRUD, QR Tables, Audit Logs).
     */
    public function index(Request $request): View
    {
        $viewer = $request->user();
        $startDate = $request->query('start_date', now()->startOfMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());
        $shift = (string) $request->query('shift', 'all');

        $startDateTime = Carbon::parse((string) $startDate)->startOfDay();
        $endDateTime = Carbon::parse((string) $endDate)->endOfDay();

        $settledOrdersQuery = Order::query()
            ->where('order_status', 'settled')
            ->whereBetween('created_at', [$startDateTime, $endDateTime])
            ->with(['restaurantTable', 'discountApprover']);

        $settledOrders = $settledOrdersQuery->latest()->get()->filter(function (Order $order) use ($shift): bool {
            if ($shift === 'all' || $shift === '') {
                return true;
            }

            $hour = (int) ($order->settled_at ?? $order->created_at)?->format('G');

            return match ($shift) {
                'morning' => $hour >= 6 && $hour < 14,
                'afternoon' => $hour >= 14 && $hour < 22,
                'night' => $hour >= 22 || $hour < 6,
                default => true,
            };
        })->values();

        $settledOrderIds = $settledOrders->pluck('id')->all();

        $grossSales = round((float) $settledOrders->sum('gross_subtotal'), 2);
        $totalDiscounts = round((float) $settledOrders->sum('discount_amount'), 2);
        $netRevenue = max(0.0, round($grossSales - $totalDiscounts, 2));
        $collectedVat = round((float) $settledOrders->sum('vat_amount'), 2);
        $collectedServiceCharge = round((float) $settledOrders->sum('service_charge_amount'), 2);
        $grandTotalCollected = round((float) $settledOrders->sum('grand_total'), 2);

        $skuConsumption = OrderItem::query()
            ->where('item_status', '!=', 'cancelled')
            ->whereHas('batch', function ($query) use ($settledOrderIds): void {
                $query->whereIn('order_id', $settledOrderIds ?: [0]);
            })
            ->with('menuItem.category')
            ->get()
            ->groupBy('menu_item_id')
            ->map(function ($rows): array {
                /** @var OrderItem $first */
                $first = $rows->first();
                $menuItem = $first->menuItem;
                $unitsSold = (int) $rows->sum('quantity');
                $revenue = round((float) $rows->sum('total_price'), 2);
                $unitCost = (float) ($menuItem?->cost_price ?? 0.0);
                $totalCogs = round($unitsSold * $unitCost, 2);

                return [
                    'menu_item_id' => $first->menu_item_id,
                    'sku_name' => $menuItem?->name ?? 'Deleted SKU',
                    'category_name' => $menuItem?->category?->name ?? 'Uncategorized',
                    'station' => $menuItem?->category?->station ?? 'kitchen',
                    'units_sold' => $unitsSold,
                    'revenue' => $revenue,
                    'cogs' => $totalCogs,
                    'gross_profit' => round($revenue - $totalCogs, 2),
                ];
            })
            ->sortByDesc('units_sold')
            ->values();

        $categories = Category::query()
            ->orderBy('display_order')
            ->with(['menuItems' => function ($query): void {
                $query->orderBy('name');
            }])
            ->get();

        $menuItems = MenuItem::query()
            ->with('category')
            ->orderBy('category_id')
            ->orderBy('name')
            ->get();

        $tables = RestaurantTable::query()
            ->with('activeOrder')
            ->orderBy('table_number')
            ->get();

        $auditLogs = AuditLog::query()
            ->with('user.role')
            ->latest()
            ->limit(100)
            ->get();

        $inventoryLogs = InventoryLog::query()
            ->with(['menuItem', 'order'])
            ->latest()
            ->limit(50)
            ->get();

        return view('admin.reports', [
            'viewer' => $viewer,
            'filters' => [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'shift' => $shift,
            ],
            'financialSummary' => [
                'settled_orders_count' => $settledOrders->count(),
                'gross_sales' => $grossSales,
                'applied_discounts' => $totalDiscounts,
                'net_revenue' => $netRevenue,
                'collected_vat' => $collectedVat,
                'collected_service_charge' => $collectedServiceCharge,
                'grand_total_collected' => $grandTotalCollected,
            ],
            'settledOrders' => $settledOrders,
            'skuConsumption' => $skuConsumption,
            'categories' => $categories,
            'menuItems' => $menuItems,
            'tables' => $tables,
            'auditLogs' => $auditLogs,
            'inventoryLogs' => $inventoryLogs,
        ]);
    }

    /**
     * Store a new menu Category.
     */
    public function storeCategory(UpsertCategoryRequest $request): JsonResponse|RedirectResponse
    {
        $category = Category::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Category created.', 'category' => $category], 201);
        }

        return redirect()->back()->with('status', "Category '{$category->name}' created.");
    }

    /**
     * Update an existing menu Category.
     */
    public function updateCategory(UpsertCategoryRequest $request, Category $category): JsonResponse|RedirectResponse
    {
        $category->update($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Category updated.', 'category' => $category]);
        }

        return redirect()->back()->with('status', "Category '{$category->name}' updated.");
    }

    /**
     * Delete a menu Category if it has no dependent menu items.
     */
    public function destroyCategory(Request $request, Category $category): JsonResponse|RedirectResponse
    {
        if ($category->menuItems()->exists()) {
            return redirect()->back()->withErrors(['category' => 'Cannot delete a category that still has menu items.']);
        }

        $category->delete();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Category deleted.']);
        }

        return redirect()->back()->with('status', 'Category deleted.');
    }

    /**
     * Create a new MenuItem.
     */
    public function storeMenuItem(UpsertMenuItemRequest $request): JsonResponse|RedirectResponse
    {
        $menuItem = MenuItem::create($request->validated());

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Menu item created.', 'menu_item' => $menuItem], 201);
        }

        return redirect()->back()->with('status', "Menu item '{$menuItem->name}' added.");
    }

    /**
     * Update an existing MenuItem and log price_update in audit_logs if the price or cost changed.
     */
    public function updateMenuItem(UpsertMenuItemRequest $request, MenuItem $menuItem): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        DB::transaction(function () use ($request, $menuItem, $validated): void {
            $oldPrice = (float) $menuItem->price;
            $oldCost = (float) $menuItem->cost_price;
            $newPrice = (float) $validated['price'];
            $newCost = (float) $validated['cost_price'];

            $menuItem->update($validated);

            if (abs($oldPrice - $newPrice) > 0.005 || abs($oldCost - $newCost) > 0.005) {
                $this->billingService->recordAudit(
                    'price_update',
                    $menuItem,
                    ['price' => $oldPrice, 'cost_price' => $oldCost],
                    ['price' => $newPrice, 'cost_price' => $newCost],
                    $request->user()?->id,
                    (string) $request->ip()
                );
            }
        });

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Menu item updated.', 'menu_item' => $menuItem->fresh()]);
        }

        return redirect()->back()->with('status', "Menu item '{$menuItem->name}' updated.");
    }

    /**
     * Live one-click toggle for a MenuItem's Out-of-Stock / Availability status.
     */
    public function toggleAvailability(Request $request, MenuItem $menuItem): JsonResponse|RedirectResponse
    {
        $menuItem->update([
            'is_available' => ! $menuItem->is_available,
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Availability updated for {$menuItem->name}.",
                'is_available' => $menuItem->is_available,
            ]);
        }

        return redirect()->back()->with('status', "Availability toggled for '{$menuItem->name}'.");
    }

    /**
     * Create a new RestaurantTable with a unique UUID qr_token.
     */
    public function storeTable(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'table_number' => ['required', 'string', 'max:30', 'unique:restaurant_tables,table_number'],
        ]);

        $table = RestaurantTable::create([
            'table_number' => trim(strip_tags((string) $validated['table_number'])),
            'qr_token' => (string) Str::uuid(),
            'status' => 'vacant',
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Restaurant table created.',
                'table' => $table,
                'signed_url' => $table->signedQrUrl(),
            ], 201);
        }

        return redirect()->back()->with('status', "Table '{$table->table_number}' created with new signed QR token.");
    }

    /**
     * Regenerate the UUID qr_token for a RestaurantTable to invalidate old printed QR codes.
     */
    public function regenerateTableQr(Request $request, RestaurantTable $table): JsonResponse|RedirectResponse
    {
        $table->update([
            'qr_token' => (string) Str::uuid(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'QR token regenerated.',
                'table' => $table,
                'signed_url' => $table->signedQrUrl(),
            ]);
        }

        return redirect()->back()->with('status', "QR token rotated for Table '{$table->table_number}'.");
    }
}
