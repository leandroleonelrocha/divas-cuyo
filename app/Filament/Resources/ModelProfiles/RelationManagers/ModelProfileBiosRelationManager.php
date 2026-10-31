<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use App\Models\ModelProfileBio;
use App\Services\ModelProfileBioModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ModelProfileBiosRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'bios';

    protected static ?string $title = 'Biografías';

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        $count = static::pendingCount($ownerRecord);

        return $count > 0 ? (string) $count : null;
    }

    public static function getBadgeColor(Model $ownerRecord, string $pageClass): ?string
    {
        return static::pendingCount($ownerRecord) > 0 ? 'warning' : null;
    }

    public static function getBadgeTooltip(Model $ownerRecord, string $pageClass): string|Htmlable|null
    {
        $count = static::pendingCount($ownerRecord);

        return $count > 0
            ? ($count === 1 ? '1 biografía pendiente de revisión' : "{$count} biografías pendientes de revisión")
            : null;
    }

    private static function pendingCount(Model $ownerRecord): int
    {
        return $ownerRecord->bios()->where('status', 'pending')->count();
    }

    public function table(Table $table): Table
    {
        return $table->extraAttributes(['id' => 'review-ModelProfileBiosRelationManager'])
            ->heading('Versiones de biografía')
            ->columns([
                TextColumn::make('content')
                    ->label('Contenido')
                    ->limit(100),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobada',
                        'rejected' => 'Rechazada',
                        default => 'Pendiente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('created_at')
                    ->label('Creada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('reviewed_at')
                    ->label('Revisada')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('—'),
                TextColumn::make('reviewedBy.email')
                    ->label('Revisada por')
                    ->placeholder('—'),
                TextColumn::make('rejection_reason')
                    ->label('Motivo de rechazo')
                    ->placeholder('—')
                    ->limit(80),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (ModelProfileBio $record): bool => $record->status === 'pending')
                    ->action(function (ModelProfileBio $record): void {
                        app(ModelProfileBioModerationService::class)->approve($record, auth()->user());
                    })
                    ->successNotificationTitle('Biografía aprobada'),
                Action::make('reject')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('reject')
                    ->visible(fn (ModelProfileBio $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->action(function (ModelProfileBio $record, array $data): void {
                        app(ModelProfileBioModerationService::class)->reject($record, auth()->user(), $data['reason']);
                    })
                    ->successNotificationTitle('Biografía rechazada'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay biografías')
            ->emptyStateDescription('Las versiones enviadas por la modelo aparecerán aquí.');
    }
}
