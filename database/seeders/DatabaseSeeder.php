<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Auction;
use App\Models\Category;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use RuntimeException;

final class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AuthorizationSeeder::class,
            SystemSettingSeeder::class,
        ]);

        $this->seedAdministrator();

        if (! app()->environment('local')) {
            return;
        }

        $seller = User::factory()->create([
            'display_name' => 'Demo Seller',
            'email' => 'seller@example.com',
        ]);
        $seller->roles()->attach(
            \App\Models\Role::query()->where('slug', 'seller')->value('id'),
            ['created_at' => now()],
        );

        $bidder = User::factory()->create([
            'display_name' => 'Demo Bidder',
            'email' => 'bidder@example.com',
        ]);
        $bidder->roles()->attach(
            \App\Models\Role::query()->where('slug', 'user')->value('id'),
            ['created_at' => now()],
        );

        $category = Category::factory()->create([
            'name' => 'Collectibles',
            'slug' => 'collectibles',
        ]);

        Auction::factory()
            ->for($seller, 'seller')
            ->for($category)
            ->active()
            ->create([
                'title' => 'Vintage Telegram Collectible',
                'slug' => 'vintage-telegram-collectible',
            ]);
    }

    private function seedAdministrator(): void
    {
        $email = env('ADMIN_EMAIL');

        if (! is_string($email) || $email === '') {
            return;
        }

        $password = env('ADMIN_PASSWORD');

        if (! is_string($password) || strlen($password) < 12) {
            throw new RuntimeException('ADMIN_PASSWORD must contain at least 12 characters when ADMIN_EMAIL is configured.');
        }

        $administrator = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'display_name' => 'Platform Administrator',
                'password' => $password,
                'locale' => config('app.locale'),
                'timezone' => config('app.timezone'),
                'status' => \App\Domain\Users\Enums\UserStatus::Active,
                'is_verified' => true,
            ],
        );
        $administrator->roles()->syncWithoutDetaching([
            Role::query()->where('slug', 'super-administrator')->value('id') => ['created_at' => now()],
        ]);
    }
}
