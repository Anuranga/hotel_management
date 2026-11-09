<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'event_type' => 'discount_override',
            'auditable_type' => Order::class,
            'auditable_id' => 1,
            'old_values' => ['discount_percentage' => 0],
            'new_values' => ['discount_percentage' => 20],
            'ip_address' => '127.0.0.1',
        ];
    }
}
