<?php

declare(strict_types=1);

namespace App\Application\Telegram\Actions;

use App\Domain\Telegram\Enums\TelegramUpdateStatus;
use App\Jobs\Telegram\ProcessTelegramUpdate;
use App\Models\TelegramUpdate;

final class IngestTelegramUpdateAction
{
    /** @param array<string, mixed> $payload */
    public function execute(array $payload): TelegramUpdate
    {
        $updateId = filter_var($payload['update_id'] ?? null, FILTER_VALIDATE_INT);

        if ($updateId === false || $updateId < 0) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'update_id' => ['A valid Telegram update_id is required.'],
            ]);
        }

        $update = TelegramUpdate::query()->firstOrCreate(
            ['update_id' => $updateId],
            [
                'payload' => $payload,
                'status' => TelegramUpdateStatus::Pending,
            ],
        );

        if ($update->wasRecentlyCreated) {
            ProcessTelegramUpdate::dispatch($update->getKey())->afterResponse();
        }

        return $update;
    }
}
