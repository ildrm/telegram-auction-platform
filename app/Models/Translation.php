<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class Translation extends Model
{
    protected $fillable = ['locale', 'group', 'key', 'value', 'is_custom', 'updated_by'];

    protected function casts(): array
    {
        return ['is_custom' => 'boolean'];
    }
}
