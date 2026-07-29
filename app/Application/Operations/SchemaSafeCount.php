<?php

declare(strict_types=1);

namespace App\Application\Operations;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class SchemaSafeCount
{
    public static function table(string $table): int
    {
        return Schema::hasTable($table) ? DB::table($table)->count() : 0;
    }
}
