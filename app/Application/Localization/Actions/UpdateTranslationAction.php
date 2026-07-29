<?php

declare(strict_types=1);

namespace App\Application\Localization\Actions;

use App\Application\Audit\AuditLogger;
use App\Models\Translation;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class UpdateTranslationAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function execute(
        User $actor,
        string $locale,
        string $group,
        string $key,
        string $value,
    ): Translation {
        if (! $actor->hasPermission('translation.manage')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $locale, $group, $key, $value): Translation {
            $translation = Translation::query()->firstOrNew(compact('locale', 'group', 'key'));
            $before = $translation->exists ? ['value' => $translation->value] : null;
            $translation->fill([
                'value' => $value,
                'is_custom' => true,
                'updated_by' => $actor->getKey(),
            ])->save();

            Cache::forget('database_translations');
            $this->auditLogger->record(
                actor: $actor,
                action: 'translation.updated',
                subject: $translation,
                before: $before,
                after: ['value' => $value],
                metadata: compact('locale', 'group', 'key'),
            );

            return $translation;
        });
    }
}
