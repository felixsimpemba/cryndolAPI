<?php

namespace App\Filament\Resources\BusinessProfileResource\Pages;

use App\Filament\Resources\BusinessProfileResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBusinessProfiles extends ListRecords
{
    protected static string $resource = BusinessProfileResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
