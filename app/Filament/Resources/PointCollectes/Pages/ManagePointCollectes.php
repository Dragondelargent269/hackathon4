<?php

namespace App\Filament\Resources\PointCollectes\Pages;

use App\Filament\Resources\PointCollectes\PointCollecteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManagePointCollectes extends ManageRecords
{
    protected static string $resource = PointCollecteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
