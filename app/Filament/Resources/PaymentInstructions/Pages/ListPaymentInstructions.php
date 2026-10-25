<?php

namespace App\Filament\Resources\PaymentInstructions\Pages;

use App\Filament\Resources\PaymentInstructions\PaymentInstructionResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaymentInstructions extends ListRecords
{
    protected static string $resource = PaymentInstructionResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Nueva instrucción')];
    }
}
