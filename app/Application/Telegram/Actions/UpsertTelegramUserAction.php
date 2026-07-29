<?php

declare(strict_types=1);

namespace App\Application\Telegram\Actions;

use App\Application\Audit\AuditLogger;
use App\Domain\Users\Enums\UserStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class UpsertTelegramUserAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $telegramUser */
    public function execute(array $telegramUser): User
    {
        $telegramId = filter_var($telegramUser['id'] ?? null, FILTER_VALIDATE_INT);

        if ($telegramId === false || $telegramId < 1) {
            throw new \InvalidArgumentException('Telegram user id is required.');
        }

        return DB::transaction(function () use ($telegramUser, $telegramId): User {
            $user = User::query()->where('telegram_id', $telegramId)->lockForUpdate()->first();
            $created = $user === null;
            $before = $user?->only(['username', 'first_name', 'last_name', 'display_name', 'locale']);

            $attributes = [
                'username' => $this->nullableString($telegramUser['username'] ?? null),
                'first_name' => $this->nullableString($telegramUser['first_name'] ?? null),
                'last_name' => $this->nullableString($telegramUser['last_name'] ?? null),
                'display_name' => $this->displayName($telegramUser),
                'locale' => $this->locale($telegramUser['language_code'] ?? null),
                'last_seen_at' => now(),
            ];

            if ($created) {
                $user = User::query()->create([
                    'telegram_id' => $telegramId,
                    'status' => UserStatus::Active,
                    'timezone' => 'UTC',
                    ...$attributes,
                ]);
            } else {
                $user->update($attributes);
            }

            $this->auditLogger->record(
                actor: $user,
                action: $created ? 'user.registered' : 'user.telegram_profile_synced',
                subject: $user,
                before: $before,
                after: $user->only(['username', 'first_name', 'last_name', 'display_name', 'locale']),
                metadata: ['source' => 'telegram'],
            );

            return $user;
        });
    }

    /** @param array<string, mixed> $telegramUser */
    private function displayName(array $telegramUser): string
    {
        $parts = array_filter([
            $this->nullableString($telegramUser['first_name'] ?? null),
            $this->nullableString($telegramUser['last_name'] ?? null),
        ]);

        return mb_substr(implode(' ', $parts) ?: 'Telegram User', 0, 160);
    }

    private function locale(mixed $locale): string
    {
        return in_array($locale, ['en', 'fa'], true) ? $locale : 'en';
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) && trim($value) !== '' ? mb_substr(trim($value), 0, 160) : null;
    }
}
