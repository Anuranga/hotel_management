<?php

namespace App\Models;

use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'restaurant_table_id',
        'session_token',
        'customer_name',
        'customer_phone',
        'order_status',
        'gross_subtotal',
        'discount_percentage',
        'discount_amount',
        'discount_approved_by',
        'vat_rate',
        'vat_amount',
        'service_charge_rate',
        'service_charge_amount',
        'grand_total',
        'payment_method',
        'settled_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'order_status' => 'pending_confirmation',
        'gross_subtotal' => 0.00,
        'discount_percentage' => 0.00,
        'discount_amount' => 0.00,
        'vat_rate' => 0.00,
        'vat_amount' => 0.00,
        'service_charge_rate' => 0.00,
        'service_charge_amount' => 0.00,
        'grand_total' => 0.00,
        'payment_method' => 'unpaid',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'gross_subtotal' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'vat_rate' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'service_charge_rate' => 'decimal:2',
            'service_charge_amount' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'settled_at' => 'datetime',
        ];
    }

    /**
     * Table where this order was placed.
     *
     * @return BelongsTo<RestaurantTable, $this>
     */
    public function restaurantTable(): BelongsTo
    {
        return $this->belongsTo(RestaurantTable::class);
    }

    /**
     * Manager/Admin user who approved a discount override (> 10%).
     *
     * @return BelongsTo<User, $this>
     */
    public function discountApprover(): BelongsTo
    {
        return $this->belongsTo(User::class, 'discount_approved_by');
    }

    /**
     * Batches (Initial Batch #1 and Add-on Batches #2+) belonging to this order.
     *
     * @return HasMany<OrderBatch, $this>
     */
    public function batches(): HasMany
    {
        return $this->hasMany(OrderBatch::class)->orderBy('batch_number');
    }

    /**
     * All order items across all batches of this order.
     *
     * @return HasManyThrough<OrderItem, OrderBatch, $this>
     */
    public function items(): HasManyThrough
    {
        return $this->hasManyThrough(OrderItem::class, OrderBatch::class);
    }

    /**
     * Inventory logs associated with this order.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Menu items linked through inventory_logs.
     *
     * @return BelongsToMany<MenuItem, $this>
     */
    public function loggedMenuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'inventory_logs')
            ->withPivot(['id', 'quantity_change', 'reason'])
            ->withTimestamps();
    }

    /**
     * Polymorphic audit logs for this order (e.g., discount_override).
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Scope for active (non-settled, non-cancelled) orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('order_status', ['settled', 'cancelled']);
    }

    /**
     * Scope for settled orders.
     *
     * @param  Builder<Order>  $query
     * @return Builder<Order>
     */
    public function scopeSettled(Builder $query): Builder
    {
        return $query->where('order_status', 'settled');
    }

    /**
     * Determine whether the order is still active.
     */
    public function isActive(): bool
    {
        return ! in_array($this->order_status, ['settled', 'cancelled'], true);
    }

    /**
     * Mask the customer's phone number for non-admin views (e.g., 077****123).
     */
    public function maskedPhone(): string
    {
        $phone = (string) $this->customer_phone;
        $length = strlen($phone);

        if ($length <= 6) {
            return str_repeat('*', $length);
        }

        $prefix = substr($phone, 0, 3);
        $suffix = substr($phone, -3);
        $maskLength = max(4, $length - 6);

        return $prefix.str_repeat('*', $maskLength).$suffix;
    }

    /**
     * Return full customer phone for admins, or masked phone for all other viewers.
     */
    public function displayPhoneFor(?User $user = null): string
    {
        if ($user !== null && $user->hasRole(Role::ADMIN)) {
            return (string) $this->customer_phone;
        }

        return $this->maskedPhone();
    }
}
