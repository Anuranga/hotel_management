<?php

namespace App\Models;

use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_batch_id',
        'menu_item_id',
        'quantity',
        'unit_price',
        'total_price',
        'notes',
        'item_status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'item_status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    /**
     * Batch this item belongs to.
     *
     * @return BelongsTo<OrderBatch, $this>
     */
    public function batch(): BelongsTo
    {
        return $this->belongsTo(OrderBatch::class, 'order_batch_id');
    }

    /**
     * Menu item ordered.
     *
     * @return BelongsTo<MenuItem, $this>
     */
    public function menuItem(): BelongsTo
    {
        return $this->belongsTo(MenuItem::class);
    }

    /**
     * Polymorphic audit logs for this order item (e.g., item_cancellation).
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Scope for non-cancelled items.
     *
     * @param  Builder<OrderItem>  $query
     * @return Builder<OrderItem>
     */
    public function scopeBillable(Builder $query): Builder
    {
        return $query->where('item_status', '!=', 'cancelled');
    }
}
