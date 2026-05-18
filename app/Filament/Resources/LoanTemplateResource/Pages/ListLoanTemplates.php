<?php

namespace App\Filament\Resources\LoanTemplateResource\Pages;

use App\Filament\Resources\LoanTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLoanTemplates extends ListRecords
{
    protected static string $resource = LoanTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
