<?php

declare(strict_types=1);

namespace App\Application\Notifications;

use App\Application\Telegram\Services\TelegramDeliveryService;
use App\Models\AuctionNotification;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class NotificationService
{
    public function __construct(private TelegramDeliveryService $telegram) {}

    /** @param array<string, mixed> $payload */
    public function send(
        User $user,
        string $type,
        string $title,
        string $message,
        array $payload = [],
    ): AuctionNotification {
        $notification = $user->auctionNotifications()->create([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'payload' => $payload,
        ]);

        if ($user->telegram_id !== null) {
            DB::afterCommit(fn () => $this->telegram->sendMessage(
                user: $user,
                chatId: $user->telegram_id,
                text: '<b>'.e($title).'</b>'.PHP_EOL.e($message),
            ));
        }

        return $notification;
    }
}
