<?php

declare(strict_types=1);

namespace App\Filament\Resources\Translations\Pages;

use App\Application\Localization\Actions\UpdateTranslationAction;
use App\Filament\Resources\Translations\TranslationResource;
use App\Models\Translation;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageTranslations extends ManageRecords
{
    protected static string $resource = TranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->using(
                fn (array $data): Translation => app(UpdateTranslationAction::class)->execute(
                    auth()->user(),
                    $data['locale'],
                    $data['group'],
                    $data['key'],
                    $data['value'],
                ),
            ),
        ];
    }
}
