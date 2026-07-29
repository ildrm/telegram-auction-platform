<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Telegram\Enums\TelegramUpdateStatus;
use Illuminate\Database\Eloquent\Model;

final class TelegramUpdate extends Model
{
    protected $fillable = [
        'update_id',
        'payload',
        'status',
        'failure_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'update_id' => 'integer',
            'payload' => 'array',
            'status' => TelegramUpdateStatus::class,
            'processed_at' => 'immutable_datetime',
        ];
    }
}
