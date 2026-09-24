<?php

namespace App\Filament\Resources\ModelProfiles;

use App\Filament\Resources\ModelProfiles\Pages\EditModelProfile;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelPhotosRelationManager;
use App\Models\ModelProfile;
use App\Services\IdentityDocumentService;
use App\Services\ModelProfileModerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModelProfileResource extends Resource
{
    protected static ?string $model = ModelProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Modelos';

    protected static ?string $modelLabel = 'modelo';

    protected static ?string $pluralModelLabel = 'modelos';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre público')
                    ->required()
                    ->maxLength(255),
                TextInput::make('whatsapp')
                    ->label('WhatsApp')
                    ->required()
                    ->maxLength(50),
                TextInput::make('location')
                    ->label('Ubicación')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('name')
                    ->label('Nombre público'),
                TextEntry::make('user.email')
                    ->label('Email'),
                TextEntry::make('whatsapp')
                    ->label('WhatsApp'),
                TextEntry::make('location')
                    ->label('Ubicación'),
                TextEntry::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i'),
                TextEntry::make('user.email_verified_at')
                    ->label('Email verificado')
                    ->state(fn (ModelProfile $record): string => $record->user->email_verified_at ? 'Verificado' : 'No verificado')
                    ->badge()
                    ->color(fn (ModelProfile $record): string => $record->user->email_verified_at ? 'success' : 'warning'),
                TextEntry::make('review_status')
                    ->label('Estado de revisión')
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
                TextEntry::make('is_published')
                    ->label('Publicación')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Publicado' : 'No publicado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                Section::make('Verificación de identidad')
                    ->schema([
                        TextEntry::make('identity_status')
                            ->label('Estado de identidad')
                            ->badge()
                            ->formatStateUsing(fn (string $state): string => static::identityStatusLabel($state))
                            ->color(fn (string $state): string => static::identityStatusColor($state)),
                        TextEntry::make('identity_rejection_reason')
                            ->label('Motivo de rechazo de identidad')
                            ->placeholder('Sin motivo'),
                        ...static::identityDocumentEntries(),
                    ])
                    ->columnSpanFull(),
                TextEntry::make('reviewed_at')
                    ->label('Revisado el')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Pendiente de revisión'),
                TextEntry::make('reviewer.email')
                    ->label('Revisado por')
                    ->placeholder('Sin revisor'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with([
            'user:id,email,email_verified_at',
            'reviewer:id,email',
            'documents',
        ]);
    }

    public static function getRelations(): array
    {
        return [
            ModelPhotosRelationManager::class,
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre público')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('whatsapp')
                    ->label('WhatsApp')
                    ->searchable(),
                TextColumn::make('location')
                    ->label('Ubicación')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('user.email_verified_at')
                    ->label('Email verificado')
                    ->boolean(),
                TextColumn::make('review_status')
                    ->label('Revisión')
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
                TextColumn::make('is_published')
                    ->label('Publicación')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'Publicado' : 'No publicado')
                    ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                TextColumn::make('created_at')
                    ->label('Fecha de registro')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('email_verified')
                    ->label('Email verificado')
                    ->trueLabel('Verificado')
                    ->falseLabel('No verificado')
                    ->placeholder('Todos')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereNotNull('email_verified_at')),
                        false: fn (Builder $query): Builder => $query->whereHas('user', fn (Builder $userQuery): Builder => $userQuery->whereNull('email_verified_at')),
                        blank: fn (Builder $query): Builder => $query,
                    ),
                SelectFilter::make('review_status')
                    ->label('Estado de revisión')
                    ->options([
                        'pending' => 'Pendiente',
                        'approved' => 'Aprobado',
                        'rejected' => 'Rechazado',
                    ]),
                TernaryFilter::make('is_published')
                    ->label('Estado de publicación')
                    ->trueLabel('Publicado')
                    ->falseLabel('No publicado')
                    ->placeholder('Todos'),
            ])
            ->searchable(['name', 'whatsapp', 'location', 'user.email'])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('No hay modelos registradas')
            ->emptyStateDescription('Las modelos aparecerán aquí cuando completen el registro público.')
            ->recordActions([
                EditAction::make()
                    ->label('Editar'),
                ...static::moderationActions(),
            ])
            ->recordUrl(fn (ModelProfile $record): string => static::getUrl('view', ['record' => $record]));
    }

    /**
     * @return array<Action>
     */
    public static function moderationActions(): array
    {
        return [
            Action::make('approve')
                ->label('Aprobar')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar perfil')
                ->modalDescription('El perfil quedará aprobado, pero no se publicará automáticamente.')
                ->modalSubmitActionLabel('Aprobar perfil')
                ->authorize('approve')
                ->visible(fn (ModelProfile $record): bool => $record->identity_status === 'approved'
                    && in_array($record->review_status, ['pending', 'rejected'], true))
                ->action(function (ModelProfile $record): void {
                    app(ModelProfileModerationService::class)->approve($record, auth()->user());
                })
                ->successNotificationTitle('Perfil aprobado'),
            Action::make('reject')
                ->label('Rechazar')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rechazar perfil')
                ->modalDescription('El perfil quedará rechazado y se retirará de la publicación.')
                ->modalSubmitActionLabel('Rechazar perfil')
                ->authorize('reject')
                ->visible(fn (ModelProfile $record): bool => in_array($record->review_status, ['pending', 'approved'], true))
                ->action(function (ModelProfile $record): void {
                    app(ModelProfileModerationService::class)->reject($record, auth()->user());
                })
                ->successNotificationTitle('Perfil rechazado'),
            Action::make('approveIdentity')
                ->label('Aprobar identidad')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Aprobar identidad')
                ->modalDescription('La documentación quedará aprobada. Esto no aprobará ni publicará el perfil automáticamente.')
                ->modalSubmitActionLabel('Aprobar identidad')
                ->authorize('approveIdentity')
                ->visible(fn (ModelProfile $record): bool => $record->identity_status === 'pending')
                ->action(function (ModelProfile $record): void {
                    app(IdentityDocumentService::class)->approveIdentity($record, auth()->user());
                })
                ->successNotificationTitle('Identidad aprobada'),
            Action::make('rejectIdentity')
                ->label('Rechazar identidad')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Rechazar identidad')
                ->modalDescription('Indicá el motivo que verá la modelo para poder corregir la documentación.')
                ->modalSubmitActionLabel('Rechazar identidad')
                ->form([
                    Textarea::make('reason')
                        ->label('Motivo del rechazo')
                        ->required()
                        ->maxLength(5000),
                ])
                ->authorize('rejectIdentity')
                ->visible(fn (ModelProfile $record): bool => $record->identity_status === 'pending')
                ->action(function (ModelProfile $record, array $data): void {
                    app(IdentityDocumentService::class)->rejectIdentity($record, auth()->user(), $data['reason']);
                })
                ->successNotificationTitle('Identidad rechazada'),
            Action::make('publish')
                ->label('Publicar')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Publicar perfil')
                ->modalDescription('El perfil aprobado quedará visible en las superficies públicas previstas.')
                ->modalSubmitActionLabel('Publicar perfil')
                ->authorize('publish')
                ->visible(fn (ModelProfile $record): bool => $record->identity_status === 'approved'
                    && $record->review_status === 'approved'
                    && ! $record->is_published)
                ->action(function (ModelProfile $record): void {
                    app(ModelProfileModerationService::class)->publish($record);
                })
                ->successNotificationTitle('Perfil publicado'),
            Action::make('unpublish')
                ->label('Despublicar')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Despublicar perfil')
                ->modalDescription('El perfil dejará de estar publicado, pero conservará su aprobación.')
                ->modalSubmitActionLabel('Despublicar perfil')
                ->authorize('unpublish')
                ->visible(fn (ModelProfile $record): bool => $record->is_published)
                ->action(function (ModelProfile $record): void {
                    app(ModelProfileModerationService::class)->unpublish($record);
                })
                ->successNotificationTitle('Perfil despublicado'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModelProfiles::route('/'),
            'view' => ViewModelProfile::route('/{record}'),
            'edit' => EditModelProfile::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<int, TextEntry>
     */
    private static function identityDocumentEntries(): array
    {
        return collect(config('identity-documents.required_types'))->map(function (string $type): TextEntry {
            return TextEntry::make("document_{$type}")
                ->label(static::documentTypeLabel($type))
                ->state(function (ModelProfile $record) use ($type): ?string {
                    $document = $record->documents->firstWhere('type', $type);

                    return $document
                        ? sprintf('%s · %s KB', $document->original_name ?: 'Archivo cargado', number_format($document->file_size / 1024, 1, ',', '.'))
                        : null;
                })
                ->url(function (ModelProfile $record) use ($type): ?string {
                    $document = $record->documents->firstWhere('type', $type);

                    return $document
                        ? route('identity.documents.download', [$record->user, $document])
                        : null;
                })
                ->openUrlInNewTab()
                ->placeholder('No cargado');
        })->all();
    }

    private static function documentTypeLabel(string $type): string
    {
        return match ($type) {
            'dni_front' => 'DNI frente',
            'dni_back' => 'DNI dorso',
            'selfie' => 'Selfie',
            default => ucfirst(str_replace('_', ' ', $type)),
        };
    }

    private static function identityStatusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'Pendiente',
            'approved' => 'Aprobada',
            'rejected' => 'Rechazada',
            default => 'Incompleta',
        };
    }

    private static function identityStatusColor(string $status): string
    {
        return match ($status) {
            'approved' => 'success',
            'rejected' => 'danger',
            'pending' => 'warning',
            default => 'gray',
        };
    }
}
