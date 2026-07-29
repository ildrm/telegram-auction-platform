<?php

declare(strict_types=1);

namespace App\Application\Users\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class ChangeUserStatusAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(User $actor, User $user, UserStatus $status, string $reason): User
    {
        if (! $actor->hasPermission('user.moderate') && ! $actor->hasPermission('user.manage')) {
            throw new AuthorizationException;
        }

        if ($actor->is($user) && $status !== UserStatus::Active) {
            throw new \DomainException('You cannot suspend or ban your own account.');
        }

        return DB::transaction(function () use ($actor, $user, $status, $reason): User {
            /** @var User $locked */
            $locked = User::query()->lockForUpdate()->findOrFail($user->getKey());
            $before = ['status' => $locked->status->value];
            $locked->update(['status' => $status]);

            $this->auditLogger->record(
                actor: $actor,
                action: 'user.status_changed',
                subject: $locked,
                before: $before,
                after: ['status' => $status->value],
                metadata: ['reason' => trim($reason)],
            );

            return $locked->refresh();
        });
    }
}
