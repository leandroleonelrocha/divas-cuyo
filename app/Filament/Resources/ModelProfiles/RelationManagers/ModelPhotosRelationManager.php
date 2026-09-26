<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use App\Enums\ModelPhotoVersionStatus;
use App\Models\ModelPhoto;
use App\Services\ModelPhotoModerationService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModelPhotosRelationManager extends RelationManager
{
    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotografías';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Fotografías de la modelo')
            ->description('Las variantes mostradas pasan por autorización privada y no exponen rutas físicas.')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['currentVersion', 'latestVersion']))
            ->columns([
                TextColumn::make('preview')
                    ->label('Vista')
                    ->state(fn (ModelPhoto $record): string => $this->previewState($record))
                    ->html(),
                TextColumn::make('position')
                    ->label('Posición')
                    ->sortable(),
                TextColumn::make('is_primary')
                    ->label('Principal')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Principal' : '—')
                    ->color(fn (bool $state): string => $state ? 'danger' : 'gray'),
                TextColumn::make('latestVersion.status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (?ModelPhotoVersionStatus $state): string => $this->statusLabel($state))
                    ->color(fn (?ModelPhotoVersionStatus $state): string => $this->statusColor($state)),
                TextColumn::make('versions_summary')
                    ->label('Versiones')
                    ->state(fn (ModelPhoto $record): string => $this->versionSummary($record))
                    ->html(),
                TextColumn::make('latestVersion.created_at')
                    ->label('Cargada')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                TextColumn::make('latestVersion.rejection_reason')
                    ->label('Motivo de rechazo')
                    ->placeholder('—')
                    ->limit(60),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('Aprobar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aprobar fotografía')
                    ->modalDescription('La versión pendiente pasará a ser la versión vigente. La foto no se marcará como principal automáticamente.')
                    ->modalSubmitActionLabel('Aprobar fotografía')
                    ->visible(fn (ModelPhoto $record): bool => $this->isPending($record))
                    ->authorize(fn (ModelPhoto $record): bool => auth()->user()?->can('approve', $record) ?? false)
                    ->action(function (ModelPhoto $record): void {
                        app(ModelPhotoModerationService::class)->approve($record, auth()->user());
                    })
                    ->successNotificationTitle('Fotografía aprobada'),
                Action::make('reject')
                    ->label('Rechazar')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Rechazar fotografía')
                    ->modalDescription('La modelo verá este motivo y podrá corregir la fotografía.')
                    ->modalSubmitActionLabel('Rechazar fotografía')
                    ->form([
                        Textarea::make('reason')
                            ->label('Motivo del rechazo')
                            ->required()
                            ->maxLength(5000),
                    ])
                    ->visible(fn (ModelPhoto $record): bool => $this->isPending($record))
                    ->authorize(fn (ModelPhoto $record): bool => auth()->user()?->can('reject', $record) ?? false)
                    ->action(function (ModelPhoto $record, array $data): void {
                        app(ModelPhotoModerationService::class)->reject($record, auth()->user(), $data['reason']);
                    })
                    ->successNotificationTitle('Fotografía rechazada'),
            ])
            ->emptyStateHeading('No hay fotografías cargadas')
            ->emptyStateDescription('Las fotografías aparecerán aquí cuando la modelo las cargue desde su cuenta.');
    }

    private function isPending(ModelPhoto $photo): bool
    {
        return $photo->latestVersion?->status === ModelPhotoVersionStatus::Pending;
    }

    private function statusLabel(?ModelPhotoVersionStatus $status): string
    {
        return match ($status) {
            ModelPhotoVersionStatus::Approved => 'Aprobada',
            ModelPhotoVersionStatus::Rejected => 'Rechazada',
            ModelPhotoVersionStatus::Pending => 'Pendiente',
            default => 'Sin estado',
        };
    }

    private function statusColor(?ModelPhotoVersionStatus $status): string
    {
        return match ($status) {
            ModelPhotoVersionStatus::Approved => 'success',
            ModelPhotoVersionStatus::Rejected => 'danger',
            ModelPhotoVersionStatus::Pending => 'warning',
            default => 'gray',
        };
    }

    private function previewState(ModelPhoto $photo): string
    {
        $version = $photo->latestVersion ?? $photo->currentVersion;
        if (! $version) {
            return '—';
        }

        $url = route('admin.model-photos.file', [
            'photo' => $photo,
            'version' => $version,
            'variant' => 'thumbnail',
        ]);

        return '<img src="'.e($url).'" alt="Vista previa de fotografía" style="width: 72px; height: 72px; object-fit: cover; border-radius: 8px;">';
    }

    private function versionSummary(ModelPhoto $photo): string
    {
        $current = $photo->currentVersion
            ? '<div><strong>Versión actual aprobada</strong>'.$this->versionImage($photo, $photo->currentVersion).'</div>'
            : '';
        $pending = $this->isPending($photo) && $photo->latestVersion?->isNot($photo->currentVersion)
            ? '<div class="text-warning-600"><strong>Nueva versión pendiente</strong>'.$this->versionImage($photo, $photo->latestVersion).'</div>'
            : '';

        return ($current.$pending) ?: '<span>Sin versión aprobada</span>';
    }

    private function versionImage(ModelPhoto $photo, object $version): string
    {
        $url = route('admin.model-photos.file', [
            'photo' => $photo,
            'version' => $version,
            'variant' => 'thumbnail',
        ]);

        return '<img src="'.e($url).'" alt="" style="display: inline-block; width: 44px; height: 44px; margin: 4px 8px 0 0; object-fit: cover; border-radius: 6px; vertical-align: middle;">';
    }
}
