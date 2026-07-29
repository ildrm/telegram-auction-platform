<?php

declare(strict_types=1);

namespace App\Application\Authorization\Actions;

use App\Application\Audit\AuditLogger;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class AssignRoleAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(User $actor, User $user, Role $role): void
    {
        if (! $actor->hasPermission('role.assign')) {
            throw new AuthorizationException;
        }

        DB::transaction(function () use ($actor, $user, $role): void {
            if ($user->roles()->whereKey($role->getKey())->exists()) {
                return;
            }

            $user->roles()->attach($role->getKey(), [
                'assigned_by' => $actor->getKey(),
                'created_at' => now(),
            ]);

            $this->auditLogger->record(
                actor: $actor,
                action: 'role.assigned',
                subject: $user,
                before: null,
                after: ['role' => $role->slug],
                metadata: null,
            );
        });
    }
}
