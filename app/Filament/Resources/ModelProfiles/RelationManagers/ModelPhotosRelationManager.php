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
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ModelPhotosRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'photos';

    protected static ?string $title = 'Fotografías';

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
            ? ($count === 1 ? '1 fotografía pendiente de revisión' : "{$count} fotografías pendientes de revisión")
            : null;
    }

    private static function pendingCount(Model $ownerRecord): int
    {
        return $ownerRecord->photos()
            ->whereHas('latestVersion', fn (Builder $query): Builder => $query->where('status', ModelPhotoVersionStatus::Pending))
            ->count();
    }

    public function table(Table $table): Table
    {
        return $table->extraAttributes(['id' => 'review-ModelPhotosRelationManager'])
            ->heading('Fotografías de la modelo')
            ->description('Las variantes mostradas pasan por autorización privada y no exponen rutas físicas.')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['currentVersion', 'latestVersion']))
            ->columns([
                TextColumn::make('preview')
                    ->label('Vista')
                    ->state(fn (ModelPhoto $record): string => $this->previewState($record))
                    ->action($this->galleryAction('previewGallery'))
                    ->tooltip('Ampliar y recorrer fotografías')
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
                    ->action($this->galleryAction('versionsGallery'))
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
                    ->modalDescription('La versión pendiente pasará a ser la versión vigente. Si todavía no hay una foto principal, se elegirá automáticamente. La modelo podrá cambiarla después.')
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

    private function galleryAction(string $name): Action
    {
        return Action::make($name)
            ->label('Ampliar fotografías')
            ->modalHeading('Fotografías de la modelo')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (ModelPhoto $record) {
                $photos = $this->getOwnerRecord()->photos()
                    ->with(['latestVersion', 'currentVersion'])
                    ->orderBy('position')->orderBy('id')->get();
                $images = [];
                $initialIndex = 0;
                foreach ($photos as $photo) {
                    foreach (collect([$photo->latestVersion, $photo->currentVersion])->filter()->unique('id') as $version) {
                        if ($photo->is($record) && $version->is($photo->latestVersion ?? $photo->currentVersion)) {
                            $initialIndex = count($images);
                        }
                        $images[] = [
                            'url' => route('admin.model-photos.file', [
                                'photo' => $photo, 'version' => $version, 'variant' => 'processed',
                            ]),
                            'label' => 'Foto '.$photo->position.' · '.$this->statusLabel($version->status),
                        ];
                    }
                }

                return view('filament.model-photo-gallery', compact('images', 'initialIndex'));
            });
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
