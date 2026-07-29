<?php

declare(strict_types=1);

namespace App\Filament\Resources\AuditLogs;

use App\Filament\Resources\AuditLogs\Pages\ManageAuditLogs;
use App\Models\AuditLog;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

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
                TextColumn::make('id')->sortable(),
                TextColumn::make('actor_id')->sortable(),
                TextColumn::make('action')->searchable()->sortable(),
                TextColumn::make('subject_type')->searchable(),
                TextColumn::make('subject_id'),
                TextColumn::make('ip_address'),
                TextColumn::make('telegram_id'),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([

            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuditLogs::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('audit.view') ?? false;
    }
}
