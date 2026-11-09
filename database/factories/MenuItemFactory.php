<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\MenuItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MenuItem>
 */
class MenuItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => ucwords(fake()->words(3, true)),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 450, 4500),
            'cost_price' => fake()->randomFloat(2, 150, 1800),
            'is_available' => true,
            'is_vat_exempt' => false,
            'current_stock' => -1,
            'image_path' => null,
        ];
    }
}
