<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Telegram\Enums\TelegramDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TelegramDelivery extends Model
{
    protected $fillable = [
        'user_id',
        'chat_id',
        'method',
        'payload',
        'status',
        'attempts',
        'telegram_message_id',
        'last_error',
        'delivered_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return [
            'chat_id' => 'integer',
            'payload' => 'array',
            'status' => TelegramDeliveryStatus::class,
            'attempts' => 'integer',
            'delivered_at' => 'immutable_datetime',
        ];
    }
}
