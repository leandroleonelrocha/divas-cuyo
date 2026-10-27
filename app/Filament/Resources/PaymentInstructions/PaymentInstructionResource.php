<?php

namespace App\Filament\Resources\PaymentInstructions;

use App\Filament\Resources\PaymentInstructions\Pages\CreatePaymentInstruction;
use App\Filament\Resources\PaymentInstructions\Pages\EditPaymentInstruction;
use App\Filament\Resources\PaymentInstructions\Pages\ListPaymentInstructions;
use App\Models\PaymentInstruction;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentInstructionResource extends Resource
{
    protected static ?string $model = PaymentInstruction::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Instrucciones de pago';

    protected static ?string $modelLabel = 'instrucción de pago';

    protected static ?string $pluralModelLabel = 'instrucciones de pago';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('alias')->label('Alias')->required()->maxLength(255),
            Textarea::make('transfer_details')->label('Datos de transferencia')->required()->maxLength(5000),
            TextInput::make('receipt_whatsapp')->label('WhatsApp para comprobantes')->maxLength(50),
            TextInput::make('default_amount')->label('Importe de referencia (publicación)')->numeric()->minValue(0),
            TextInput::make('default_amount_virtual')->label('Importe de referencia (virtual)')->numeric()->minValue(0),
            Toggle::make('is_active')->label('Instrucciones activas')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('alias')->label('Alias')->placeholder('No configurado'),
            TextColumn::make('receipt_whatsapp')->label('WhatsApp')->placeholder('No configurado'),
            TextColumn::make('default_amount')->label('Importe publicación')->money('ARS')->placeholder('—'),
            TextColumn::make('default_amount_virtual')->label('Importe virtual')->money('ARS')->placeholder('—'),
            IconColumn::make('is_active')->label('Activa')->boolean(),
            TextColumn::make('updated_at')->label('Actualizada')->dateTime('d/m/Y H:i')->sortable(),
        ])->actions([
            EditAction::make(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaymentInstructions::route('/'),
            'create' => CreatePaymentInstruction::route('/create'),
            'edit' => EditPaymentInstruction::route('/{record}/edit'),
        ];
    }
}
