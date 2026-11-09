<?php

namespace App\Models;

use Database\Factories\OrderBatchFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrderBatch extends Model
{
    /** @use HasFactory<OrderBatchFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'batch_number',
        'status',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'batch_number' => 1,
        'status' => 'pending',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'batch_number' => 'integer',
        ];
    }

    /**
     * Master order this batch belongs to.
     *
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Line items within this batch.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Menu items belonging to this batch through order_items.
     *
     * @return BelongsToMany<MenuItem, $this>
     */
    public function menuItems(): BelongsToMany
    {
        return $this->belongsToMany(MenuItem::class, 'order_items')
            ->withPivot(['id', 'quantity', 'unit_price', 'total_price', 'notes', 'item_status'])
            ->withTimestamps();
    }
}
