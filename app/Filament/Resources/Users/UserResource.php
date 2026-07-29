<?php

declare(strict_types=1);

namespace App\Filament\Resources\Users;

use App\Application\Users\Actions\ChangeUserStatusAction;
use App\Domain\Users\Enums\UserStatus;
use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('display_name')->searchable()->sortable(),
                TextColumn::make('username')->searchable(),
                TextColumn::make('telegram_id')->searchable(),
                TextColumn::make('email')->searchable(),
                TextColumn::make('status')->badge(),
                IconColumn::make('is_verified')->boolean(),
                TextColumn::make('last_seen_at')->dateTime()->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                Action::make('changeStatus')
                    ->schema([
                        Select::make('status')
                            ->options(collect(UserStatus::cases())->mapWithKeys(
                                fn (UserStatus $status): array => [$status->value => str($status->value)->title()],
                            ))
                            ->required(),
                        Textarea::make('reason')->required()->minLength(3)->maxLength(2_000),
                    ])
                    ->action(fn (User $record, array $data): User => app(ChangeUserStatusAction::class)->execute(
                        auth()->user(),
                        $record,
                        UserStatus::from($data['status']),
                        $data['reason'],
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUsers::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('user.manage')
            || auth()->user()?->hasPermission('user.moderate')
            || false;
    }
}
