<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Application\Telegram\Actions\UpsertTelegramUserAction;
use App\Application\Telegram\Services\TelegramBotRouter;
use App\Domain\Telegram\Enums\TelegramUpdateStatus;
use App\Models\TelegramUpdate;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

final class ProcessTelegramUpdate implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [5, 30, 120];

    public function __construct(public readonly int $telegramUpdateId) {}

    public function handle(
        UpsertTelegramUserAction $upsertUser,
        TelegramBotRouter $router,
    ): void {
        /** @var TelegramUpdate|null $update */
        $update = DB::transaction(function (): ?TelegramUpdate {
            $locked = TelegramUpdate::query()->lockForUpdate()->find($this->telegramUpdateId);

            if ($locked === null || $locked->status === TelegramUpdateStatus::Processed) {
                return null;
            }

            $locked->update([
                'status' => TelegramUpdateStatus::Processing,
                'failure_reason' => null,
            ]);

            return $locked;
        });

        if ($update === null) {
            return;
        }

        try {
            $telegramUser = data_get($update->payload, 'message.from')
                ?? data_get($update->payload, 'callback_query.from');

            if (! is_array($telegramUser)) {
                throw new \InvalidArgumentException('Telegram update does not contain a supported user.');
            }

            $user = $upsertUser->execute($telegramUser);
            $router->route($user, $update->payload);

            $update->update([
                'status' => TelegramUpdateStatus::Processed,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $update->update([
                'status' => TelegramUpdateStatus::Failed,
                'failure_reason' => mb_substr($exception->getMessage(), 0, 2_000),
            ]);

            throw $exception;
        }
    }
}
