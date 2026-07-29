<?php

declare(strict_types=1);

namespace App\Filament\Resources\Translations;

use App\Application\Localization\Actions\UpdateTranslationAction;
use App\Filament\Resources\Translations\Pages\ManageTranslations;
use App\Models\Translation;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('locale')->options(['en' => 'English', 'fa' => 'Persian'])->required(),
                TextInput::make('group')->required()->maxLength(100),
                TextInput::make('key')->required()->maxLength(180),
                Textarea::make('value')->required()->rows(5),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('locale')->badge()->sortable(),
                TextColumn::make('group')->searchable()->sortable(),
                TextColumn::make('key')->searchable(),
                TextColumn::make('value')->limit(80),
                IconColumn::make('is_custom')->boolean(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                EditAction::make()->using(
                    fn (Translation $record, array $data): Translation => app(UpdateTranslationAction::class)
                        ->execute(
                            auth()->user(),
                            $data['locale'],
                            $data['group'],
                            $data['key'],
                            $data['value'],
                        ),
                ),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTranslations::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('translation.manage') ?? false;
    }
}
