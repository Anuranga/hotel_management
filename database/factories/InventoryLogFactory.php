<?php

namespace Database\Factories;

use App\Models\InventoryLog;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<InventoryLog>
 */
class InventoryLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'menu_item_id' => MenuItem::factory(),
            'order_id' => null,
            'quantity_change' => -1,
            'reason' => 'sale',
        ];
    }
}
