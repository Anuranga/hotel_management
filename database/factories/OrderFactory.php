<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\RestaurantTable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'restaurant_table_id' => RestaurantTable::factory(),
            'session_token' => (string) Str::uuid(),
            'customer_name' => fake()->name(),
            'customer_phone' => '077'.fake()->numerify('#######'),
            'order_status' => 'pending_confirmation',
            'gross_subtotal' => 2000.00,
            'discount_percentage' => 0.00,
            'discount_amount' => 0.00,
            'discount_approved_by' => null,
            'vat_rate' => 18.00,
            'vat_amount' => 360.00,
            'service_charge_rate' => 10.00,
            'service_charge_amount' => 200.00,
            'grand_total' => 2560.00,
            'payment_method' => 'unpaid',
            'settled_at' => null,
        ];
    }
}
