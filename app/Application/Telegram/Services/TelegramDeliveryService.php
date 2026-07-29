<?php

declare(strict_types=1);

namespace App\Application\Telegram\Services;

use App\Jobs\Telegram\SendTelegramDelivery;
use App\Models\TelegramDelivery;
use App\Models\User;

final class TelegramDeliveryService
{
    /** @param array<string, mixed>|null $replyMarkup */
    public function sendMessage(
        ?User $user,
        int $chatId,
        string $text,
        ?array $replyMarkup = null,
    ): TelegramDelivery {
        $payload = [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ];

        if ($replyMarkup !== null) {
            $payload['reply_markup'] = $replyMarkup;
        }

        $delivery = TelegramDelivery::query()->create([
            'user_id' => $user?->getKey(),
            'chat_id' => $chatId,
            'method' => 'sendMessage',
            'payload' => $payload,
        ]);

        SendTelegramDelivery::dispatch($delivery->getKey());

        return $delivery;
    }
}
