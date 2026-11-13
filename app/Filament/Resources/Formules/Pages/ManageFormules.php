<?php

namespace App\Filament\Resources\Formules\Pages;

use App\Filament\Resources\Formules\FormuleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ManageRecords;

class ManageFormules extends ManageRecords
{
    protected static string $resource = FormuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
