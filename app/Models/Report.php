<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Moderation\Enums\ReportStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class Report extends Model
{
    protected $fillable = [
        'reporter_id',
        'subject_type',
        'subject_id',
        'reason',
        'description',
        'status',
        'resolved_by',
        'resolution',
        'resolved_at',
    ];

    /** @return BelongsTo<User, $this> */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /** @return MorphTo<Model, $this> */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    protected function casts(): array
    {
        return [
            'status' => ReportStatus::class,
            'resolved_at' => 'immutable_datetime',
        ];
    }
}
