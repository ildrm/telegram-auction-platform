<?php

declare(strict_types=1);

namespace App\Application\Categories\Actions;

use App\Application\Audit\AuditLogger;
use App\Models\Category;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class SaveCategoryAction
{
    public function __construct(private AuditLogger $auditLogger) {}

    /** @param array<string, mixed> $attributes */
    public function execute(User $actor, ?Category $category, array $attributes): Category
    {
        if (! $actor->hasPermission('category.manage')) {
            throw new AuthorizationException;
        }

        return DB::transaction(function () use ($actor, $category, $attributes): Category {
            $category ??= new Category;
            $before = $category->exists ? $category->attributesToArray() : null;
            $attributes['slug'] = Str::slug((string) ($attributes['slug'] ?: $attributes['name']));
            $category->fill($attributes)->save();

            $this->auditLogger->record(
                actor: $actor,
                action: $before === null ? 'category.created' : 'category.updated',
                subject: $category,
                before: $before,
                after: $category->attributesToArray(),
                metadata: null,
            );

            return $category;
        });
    }
}
