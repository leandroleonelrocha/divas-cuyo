<?php

namespace App\Filament\Resources\PaymentInstructions\Pages;

use App\Filament\Resources\PaymentInstructions\PaymentInstructionResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentInstruction extends CreateRecord
{
    protected static string $resource = PaymentInstructionResource::class;

    protected static bool $canCreateAnother = false;
}
