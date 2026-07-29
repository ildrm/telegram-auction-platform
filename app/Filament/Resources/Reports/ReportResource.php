<?php

declare(strict_types=1);

namespace App\Filament\Resources\Reports;

use App\Application\Moderation\Actions\ResolveReportAction;
use App\Domain\Moderation\Enums\ReportStatus;
use App\Filament\Resources\Reports\Pages\ManageReports;
use App\Models\Report;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReportResource extends Resource
{
    protected static ?string $model = Report::class;

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
                TextColumn::make('reporter.display_name')->searchable(),
                TextColumn::make('subject_type')->searchable(),
                TextColumn::make('subject_id'),
                TextColumn::make('reason')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([

            ])
            ->recordActions([
                Action::make('resolve')
                    ->schema([
                        Select::make('status')->options([
                            ReportStatus::Resolved->value => 'Resolved',
                            ReportStatus::Dismissed->value => 'Dismissed',
                        ])->required(),
                        Textarea::make('resolution')->required()->minLength(3)->maxLength(2_000),
                    ])
                    ->action(fn (Report $record, array $data): Report => app(ResolveReportAction::class)->execute(
                        auth()->user(),
                        $record,
                        ReportStatus::from($data['status']),
                        $data['resolution'],
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageReports::route('/'),
        ];
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('report.manage') ?? false;
    }
}
