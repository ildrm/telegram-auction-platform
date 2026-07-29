<?php

declare(strict_types=1);

namespace App\Application\Settings;

use App\Application\Audit\AuditLogger;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

final readonly class SettingService
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            "setting.{$key}",
            now()->addMinutes(10),
            fn (): mixed => SystemSetting::query()->where('key', $key)->value('value') ?? $default,
        );
    }

    public function set(User $actor, string $key, mixed $value, string $type, bool $isPublic): SystemSetting
    {
        if (! $actor->hasPermission('settings.manage')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $key, $value, $type, $isPublic): SystemSetting {
            $setting = SystemSetting::query()->firstOrNew(['key' => $key]);
            $before = $setting->exists ? ['value' => $setting->value] : null;
            $setting->fill([
                'value' => $value,
                'type' => $type,
                'is_public' => $isPublic,
                'updated_by' => $actor->getKey(),
            ])->save();

            Cache::forget("setting.{$key}");
            $this->auditLogger->record(
                actor: $actor,
                action: 'setting.updated',
                subject: $setting,
                before: $before,
                after: ['value' => $value],
                metadata: null,
            );

            return $setting;
        });
    }
}
