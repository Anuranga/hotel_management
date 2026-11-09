<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\OrderBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OrderBatch>
 */
class OrderBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'batch_number' => 1,
            'status' => 'pending',
        ];
    }
}
