<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'role_id' => fn () => Role::firstOrCreate(
                ['name' => Role::STAFF],
                ['label' => 'Sales Assistant / Staff']
            )->id,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'pin_code' => null,
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Assign a specific role name to the user.
     */
    public function withRole(string $roleName, ?string $pin = null): static
    {
        return $this->state(function () use ($roleName, $pin): array {
            $role = Role::firstOrCreate(
                ['name' => $roleName],
                ['label' => ucfirst($roleName)]
            );

            return [
                'role_id' => $role->id,
                'pin_code' => $pin,
            ];
        });
    }
}
