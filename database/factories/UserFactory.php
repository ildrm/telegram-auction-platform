<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Domain\Users\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/** @extends Factory<User> */
final class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'telegram_id' => fake()->unique()->numberBetween(100_000, 9_999_999_999),
            'username' => fake()->unique()->userName(),
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'display_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'locale' => 'en',
            'timezone' => 'UTC',
            'status' => UserStatus::Active,
            'is_verified' => false,
            'last_seen_at' => now(),
        ];
    }

    public function suspended(): static
    {
        return $this->state(fn (): array => ['status' => UserStatus::Suspended]);
    }

    public function configure(): static
    {
        return $this->afterCreating(function (User $user): void {
            $roleId = Role::query()->where('slug', 'user')->value('id');

            if ($roleId !== null) {
                $user->roles()->syncWithoutDetaching([$roleId => ['created_at' => now()]]);
            }
        });
    }

    public function seller(): static
    {
        return $this->afterCreating(function (User $user): void {
            $roleId = Role::query()->where('slug', 'seller')->value('id');

            if ($roleId !== null) {
                $user->roles()->syncWithoutDetaching([$roleId => ['created_at' => now()]]);
            }
        });
    }

    public function moderator(): static
    {
        return $this->afterCreating(function (User $user): void {
            $roleId = Role::query()->where('slug', 'moderator')->value('id');

            if ($roleId !== null) {
                $user->roles()->syncWithoutDetaching([$roleId => ['created_at' => now()]]);
            }
        });
    }

    public function administrator(): static
    {
        return $this->afterCreating(function (User $user): void {
            $roleId = Role::query()->where('slug', 'administrator')->value('id');

            if ($roleId !== null) {
                $user->roles()->syncWithoutDetaching([$roleId => ['created_at' => now()]]);
            }
        });
    }
}
