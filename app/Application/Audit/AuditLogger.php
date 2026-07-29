<?php

declare(strict_types=1);

namespace App\Application\Audit;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

final class AuditLogger
{
    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @param  array<string, mixed>|null  $metadata
     */
    public function record(
        ?User $actor,
        string $action,
        Model $subject,
        ?array $before,
        ?array $after,
        ?array $metadata,
    ): AuditLog {
        return AuditLog::query()->create([
            'actor_id' => $actor?->getKey(),
            'action' => $action,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'before' => $before,
            'after' => $after,
            'metadata' => $metadata,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
            'telegram_id' => $actor?->telegram_id,
        ]);
    }
}
