<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions;

use App\Application\Moderation\Actions\ReviewAuctionAction;
use App\Domain\Auctions\Enums\AuctionStatus;
use App\Domain\Moderation\Enums\ModerationDecision;
use App\Filament\Resources\Auctions\Pages\ManageAuctions;
use App\Models\Auction;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AuctionResource extends Resource
{
    protected static ?string $model = Auction::class;

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
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('seller.display_name')->searchable(),
                TextColumn::make('category.name')->sortable(),
                TextColumn::make('type')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('current_price_minor')->numeric()->sortable(),
                TextColumn::make('currency'),
                IconColumn::make('is_private')->boolean(),
                TextColumn::make('starts_at')->dateTime()->sortable(),
                TextColumn::make('ends_at')->dateTime()->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                Action::make('approve')
                    ->requiresConfirmation()
                    ->visible(fn (Auction $record): bool => $record->status === AuctionStatus::PendingApproval)
                    ->action(fn (Auction $record): Auction => app(ReviewAuctionAction::class)->execute(
                        $record,
                        auth()->user(),
                        ModerationDecision::Approved,
                        null,
                    )),
                Action::make('reject')
                    ->schema([
                        Textarea::make('reason')->required()->minLength(3)->maxLength(2_000),
                    ])
                    ->visible(fn (Auction $record): bool => $record->status === AuctionStatus::PendingApproval)
                    ->action(fn (Auction $record, array $data): Auction => app(ReviewAuctionAction::class)->execute(
                        $record,
                        auth()->user(),
                        ModerationDecision::Rejected,
                        $data['reason'],
                    )),
            ]);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasPermission('auction.approve') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAuctions::route('/'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
