<?php

declare(strict_types=1);

namespace App\Filament\Resources\Categories\Pages;

use App\Application\Categories\Actions\SaveCategoryAction;
use App\Filament\Resources\Categories\CategoryResource;
use App\Models\Category;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageCategories extends ManageRecords
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(
                fn (array $data): Category => app(SaveCategoryAction::class)
                    ->execute(auth()->user(), null, $data),
            ),
        ];
    }
}
