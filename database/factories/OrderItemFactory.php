<?php

namespace Database\Factories;

use App\Models\MenuItem;
use App\Models\OrderBatch;
use App\Models\OrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderItem>
 */
class OrderItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $qty = fake()->numberBetween(1, 3);
        $unitPrice = fake()->randomFloat(2, 500, 2500);

        return [
            'order_batch_id' => OrderBatch::factory(),
            'menu_item_id' => MenuItem::factory(),
            'quantity' => $qty,
            'unit_price' => $unitPrice,
            'total_price' => round($qty * $unitPrice, 2),
            'notes' => null,
            'item_status' => 'pending',
        ];
    }
}
