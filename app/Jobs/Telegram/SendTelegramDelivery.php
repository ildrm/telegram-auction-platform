<?php

declare(strict_types=1);

namespace App\Jobs\Telegram;

use App\Domain\Telegram\Enums\TelegramDeliveryStatus;
use App\Infrastructure\Telegram\TelegramClient;
use App\Models\TelegramDelivery;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

final class SendTelegramDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [5, 15, 60, 300];

    public function __construct(public readonly int $deliveryId) {}

    public function handle(TelegramClient $client): void
    {
        $delivery = TelegramDelivery::query()->findOrFail($this->deliveryId);

        if ($delivery->status === TelegramDeliveryStatus::Delivered) {
            return;
        }

        $delivery->update([
            'status' => TelegramDeliveryStatus::Processing,
            'attempts' => $delivery->attempts + 1,
            'last_error' => null,
        ]);

        $response = $client->call($delivery->method, $delivery->payload);
        $messageId = data_get($response, 'result.message_id');

        $delivery->update([
            'status' => TelegramDeliveryStatus::Delivered,
            'telegram_message_id' => is_scalar($messageId) ? (string) $messageId : null,
            'delivered_at' => now(),
        ]);
    }

    public function failed(?Throwable $exception): void
    {
        TelegramDelivery::query()->whereKey($this->deliveryId)->update([
            'status' => TelegramDeliveryStatus::Failed,
            'last_error' => mb_substr($exception?->getMessage() ?? 'Unknown delivery failure.', 0, 2_000),
        ]);
    }
}
