<?php

namespace App\Models;

use Database\Factories\MenuItemFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MenuItem extends Model
{
    /** @use HasFactory<MenuItemFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'name',
        'description',
        'price',
        'cost_price',
        'is_available',
        'is_vat_exempt',
        'current_stock',
        'image_path',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'cost_price' => 0.00,
        'is_available' => true,
        'is_vat_exempt' => false,
        'current_stock' => -1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'is_available' => 'boolean',
            'is_vat_exempt' => 'boolean',
            'current_stock' => 'integer',
        ];
    }

    /**
     * Category this menu item belongs to.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Order items referencing this menu item.
     *
     * @return HasMany<OrderItem, $this>
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Order batches containing this menu item via order_items pivot.
     *
     * @return BelongsToMany<OrderBatch, $this>
     */
    public function orderBatches(): BelongsToMany
    {
        return $this->belongsToMany(OrderBatch::class, 'order_items')
            ->withPivot(['id', 'quantity', 'unit_price', 'total_price', 'notes', 'item_status'])
            ->withTimestamps();
    }

    /**
     * Inventory movement logs for this menu item.
     *
     * @return HasMany<InventoryLog, $this>
     */
    public function inventoryLogs(): HasMany
    {
        return $this->hasMany(InventoryLog::class);
    }

    /**
     * Audit logs recorded for this menu item (e.g., price updates).
     *
     * @return MorphMany<AuditLog, $this>
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'auditable');
    }

    /**
     * Scope a query to only include available items in stock.
     *
     * @param  Builder<MenuItem>  $query
     * @return Builder<MenuItem>
     */
    public function scopeAvailable(Builder $query): Builder
    {
        return $query->where('is_available', true)
            ->where(function (Builder $q): void {
                $q->where('current_stock', -1)
                    ->orWhere('current_stock', '>', 0);
            });
    }

    /**
     * Determine whether this item can fulfill the requested quantity.
     */
    public function hasSufficientStock(int $quantity): bool
    {
        if (! $this->is_available) {
            return false;
        }

        if ($this->current_stock === -1) {
            return true;
        }

        return $this->current_stock >= $quantity;
    }
}
