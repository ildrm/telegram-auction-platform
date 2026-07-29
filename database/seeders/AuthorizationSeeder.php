<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

final class AuthorizationSeeder extends Seeder
{
    /** @var array<string, list<string>> */
    private const ROLE_PERMISSIONS = [
        'user' => [
            'auction.view',
            'bid.place',
            'report.submit',
        ],
        'seller' => [
            'auction.view',
            'auction.create',
            'auction.update',
            'auction.submit',
            'bid.place',
            'media.upload',
            'report.submit',
        ],
        'moderator' => [
            'auction.view',
            'auction.approve',
            'audit.view',
            'report.manage',
            'user.moderate',
        ],
        'administrator' => [
            'auction.view',
            'auction.approve',
            'audit.view',
            'category.manage',
            'report.manage',
            'role.assign',
            'settings.manage',
            'translation.manage',
            'user.manage',
            'backup.manage',
        ],
    ];

    public function run(): void
    {
        $permissionSlugs = collect(self::ROLE_PERMISSIONS)->flatten()->unique()->values();

        foreach ($permissionSlugs as $slug) {
            Permission::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => str($slug)->replace('.', ' ')->title()->toString(),
                    'group' => str($slug)->before('.')->toString(),
                ],
            );
        }

        foreach (self::ROLE_PERMISSIONS as $roleSlug => $permissions) {
            $role = Role::query()->updateOrCreate(
                ['slug' => $roleSlug],
                ['name' => str($roleSlug)->title()->toString(), 'is_system' => true],
            );
            $role->permissions()->sync(
                Permission::query()->whereIn('slug', $permissions)->pluck('id'),
            );
        }

        $superAdministrator = Role::query()->updateOrCreate(
            ['slug' => 'super-administrator'],
            ['name' => 'Super Administrator', 'is_system' => true],
        );
        $superAdministrator->permissions()->sync(Permission::query()->pluck('id'));
    }
}
