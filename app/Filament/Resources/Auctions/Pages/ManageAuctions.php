<?php

declare(strict_types=1);

namespace App\Filament\Resources\Auctions\Pages;

use App\Filament\Resources\Auctions\AuctionResource;
use Filament\Resources\Pages\ManageRecords;

class ManageAuctions extends ManageRecords
{
    protected static string $resource = AuctionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
