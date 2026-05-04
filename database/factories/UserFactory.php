<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'mobile' => '9'.fake()->unique()->numerify('#########'),
            'mobile_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
            'mobile_verified_at' => null,
        ]);
    }

    public function admin(): static
    {
        return $this->withRole(User::ROLE_ADMIN);
    }

    public function vendorUser(): static
    {
        return $this->withRole(User::ROLE_VENDOR);
    }

    public function trainerUser(): static
    {
        return $this->withRole(User::ROLE_TRAINER);
    }

    public function withRole(string $role): static
    {
        return $this->afterCreating(function (User $user) use ($role): void {
            Role::findOrCreate($role, 'web');
            $user->assignRole($role);
        });
    }
}
