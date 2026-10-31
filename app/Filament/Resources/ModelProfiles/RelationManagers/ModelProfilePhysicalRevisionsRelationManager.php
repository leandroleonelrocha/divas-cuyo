<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use App\Models\ModelProfilePhysicalRevision;
use App\Services\ModelProfilePhysicalModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;

class ModelProfilePhysicalRevisionsRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'physicalRevisions';

    protected static ?string $title = 'Cambios físicos';

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
            ? ($count === 1 ? '1 cambio físico pendiente de revisión' : "{$count} cambios físicos pendientes de revisión")
            : null;
    }

    private static function pendingCount(Model $ownerRecord): int
    {
        return $ownerRecord->physicalRevisions()->where('status', 'pending')->count();
    }

    public function table(Table $table): Table
    {
        return $table->extraAttributes(['id' => 'review-ModelProfilePhysicalRevisionsRelationManager'])
            ->heading('Revisiones de características físicas')
            ->columns([
                TextColumn::make('created_at')
                    ->label('Enviado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('height_cm')
                    ->label('Altura')
                    ->suffix(' cm'),
                TextColumn::make('weight_kg')
                    ->label('Peso')
                    ->suffix(' kg'),
                TextColumn::make('measurements')
                    ->label('Medidas'),
                TextColumn::make('eye_color')
                    ->label('Ojos'),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                        default => 'Pendiente',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'rejected' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('rejection_reason')
                    ->label('Motivo')
                    ->placeholder('—')
                    ->limit(60),
                TextColumn::make('reviewer.email')
                    ->label('Revisado por')
                    ->placeholder('—'),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->authorize('approve')
                    ->visible(fn (ModelProfilePhysicalRevision $record): bool => $record->status === 'pending')
                    ->action(function (ModelProfilePhysicalRevision $record): void {
                        app(ModelProfilePhysicalModerationService::class)->approve($record, auth()->user());
                    })
                    ->successNotificationTitle('Cambio físico aprobado'),
                Action::make('reject')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->authorize('reject')
                    ->visible(fn (ModelProfilePhysicalRevision $record): bool => $record->status === 'pending')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->action(function (ModelProfilePhysicalRevision $record, array $data): void {
                        app(ModelProfilePhysicalModerationService::class)->reject($record, auth()->user(), $data['reason']);
                    })
                    ->successNotificationTitle('Cambio físico rechazado'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay cambios físicos')
            ->emptyStateDescription('Las modificaciones enviadas por la modelo aparecerán aquí para revisión.');
    }
}
