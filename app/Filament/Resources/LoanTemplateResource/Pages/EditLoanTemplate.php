<?php

namespace App\Filament\Resources\LoanTemplateResource\Pages;

use App\Filament\Resources\LoanTemplateResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLoanTemplate extends EditRecord
{
    protected static string $resource = LoanTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
