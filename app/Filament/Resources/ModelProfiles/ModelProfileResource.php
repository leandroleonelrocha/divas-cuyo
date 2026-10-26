<?php

namespace App\Filament\Resources\ModelProfiles;

use App\Filament\Resources\ModelProfiles\Pages\EditModelProfile;
use App\Filament\Resources\ModelProfiles\Pages\ListModelProfiles;
use App\Filament\Resources\ModelProfiles\Pages\ViewModelProfile;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelPhotosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfileBiosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfilePhysicalRevisionsRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelProfileServicesRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\PublicationPaymentsRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\ModelVideosRelationManager;
use App\Filament\Resources\ModelProfiles\RelationManagers\PublicationTypeHistoryRelationManager;
use App\Models\ModelProfile;
use App\Services\AccountEnrollmentState;
use App\Services\IdentityDocumentService;
use App\Services\ModelProfileModerationService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ModelProfileResource extends Resource
{
    protected static ?string $model = ModelProfile::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $navigationLabel = 'Modelos';

    protected static ?string $modelLabel = 'modelo';

    protected static ?string $pluralModelLabel = 'modelos';

    public static function getNavigationBadge(): ?string
    {
        $count = static::pendingReviewCount();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return static::pendingReviewCount() > 0 ? 'warning' : null;
    }

    public static function getNavigationBadgeTooltip(): string|Htmlable|null
    {
        $count = static::pendingReviewCount();

        return $count > 0
            ? ($count === 1 ? '1 modelo pendiente de revisión' : "{$count} modelos pendientes de revisión")
            : null;
    }

    private static function pendingReviewCount(): int
    {
        return ModelProfile::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('review_status', 'pending')
                ->orWhere('identity_status', 'pending'))
            ->count();
    }

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
                Select::make('schedule_availability')
                    ->label('Disponibilidad horaria')
                    ->options([
                        'part_time' => 'Part Time',
                        'full_time' => 'Full Time',
                    ])
                    ->placeholder('Sin definir'),
                Select::make('gender')
                    ->label('Género')
                    ->options([
                        'female' => 'Femenino',
                        'trans' => 'Trans',
                    ])
                    ->placeholder('Sin definir'),
                Select::make('depilation')
                    ->label('Depilación')
                    ->options([
                        'full' => 'Full',
                        'partial' => 'Parcial',
                    ])
                    ->placeholder('Sin definir'),
                Select::make('rate_visibility')
                    ->label('Mostrar precios')
                    ->options([
                        'consult' => 'Consultar',
                        'published' => 'Publicar valores',
                    ])
                    ->placeholder('Consultar'),
                TextInput::make('rate_30min')
                    ->label('30 minutos')
                    ->numeric()
                    ->minValue(0),
                Select::make('rate_30min_currency')
                    ->label('Moneda 30 min')
                    ->options(['ARS' => 'ARS', 'USD' => 'USD'])
                    ->placeholder('Sin definir'),
                TextInput::make('rate_60min')
                    ->label('1 hora')
                    ->numeric()
                    ->minValue(0),
                Select::make('rate_60min_currency')
                    ->label('Moneda 1 hora')
                    ->options(['ARS' => 'ARS', 'USD' => 'USD'])
                    ->placeholder('Sin definir'),
            ]);
    }

    /** @return array<Component> */
    private static function summarySchema(): array
    {
        return [
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    Section::make('Verificación')
                        ->icon(Heroicon::OutlinedIdentification)
                        ->compact()
                        ->schema([
                            TextEntry::make('identity_status')
                                ->label('Identidad')
                                ->badge()
                                ->formatStateUsing(fn (string $state): string => static::identityStatusLabel($state))
                                ->color(fn (string $state): string => static::identityStatusColor($state)),
                            TextEntry::make('user.email_verified_at')
                                ->label('Email verificado')
                                ->state(fn (ModelProfile $record): string => $record->user?->email_verified_at ? 'Verificado' : 'No verificado')
                                ->badge()
                                ->color(fn (ModelProfile $record): string => $record->user?->email_verified_at ? 'success' : 'warning'),
                        ]),
                    Section::make('Revisión')
                        ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                        ->compact()
                        ->schema([
                            TextEntry::make('user.profile_completion_status')
                                ->label('Estado de inscripción')->badge()
                                ->formatStateUsing(fn (?string $state): string => AccountEnrollmentState::labels()[$state] ?? 'Inscripción habilitada'),
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
                        ]),
                    Section::make('Publicación')
                        ->icon(Heroicon::OutlinedGlobeAlt)
                        ->compact()
                        ->schema([
                            TextEntry::make('is_published')
                                ->label('Estado de publicación')
                                ->badge()
                                ->formatStateUsing(fn (bool $state): string => $state ? 'Publicado' : 'No publicado')
                                ->color(fn (bool $state): string => $state ? 'success' : 'gray'),
                        ]),
                ]),
            Grid::make(['default' => 1, 'lg' => 3])
                ->schema([
                    Section::make('Datos principales')
                        ->icon(Heroicon::OutlinedUserCircle)
                        ->compact()
                        ->columnSpan(['default' => 1, 'lg' => 2])
                        ->columns(['default' => 1, 'sm' => 2])
                        ->schema([
                            TextEntry::make('stage_name')->label('Nombre artístico')->placeholder('No cargado'),
                            TextEntry::make('name')->label('Nombre público'),
                            TextEntry::make('user.email')->label('Email')->copyable(),
                            TextEntry::make('whatsapp')->label('WhatsApp')->placeholder('No cargado')->copyable(),
                            TextEntry::make('location')->label('Ubicación')->placeholder('No cargada'),
                            TextEntry::make('province.name')->label('Provincia')->placeholder('No seleccionada'),
                            TextEntry::make('locality.name')->label('Localidad')->placeholder('No seleccionada'),
                        ]),
                    Section::make('Registro y revisión')
                        ->icon(Heroicon::OutlinedClock)
                        ->compact()
                        ->schema([
                            TextEntry::make('created_at')->label('Fecha de registro')->dateTime('d/m/Y H:i'),
                            TextEntry::make('reviewed_at')->label('Revisado el')->dateTime('d/m/Y H:i')->placeholder('Pendiente de revisión'),
                            TextEntry::make('reviewer.email')->label('Revisado por')->placeholder('Sin revisor'),
                        ]),
                ]),
            Section::make('Acciones pendientes')
                ->icon(Heroicon::OutlinedClipboardDocumentCheck)
                ->compact()
                ->schema([
                    TextEntry::make('pendingActions')
                        ->label('Seguimiento')
                        ->state(function (ModelProfile $record): string {
                            $pending = [];
                            if (! $record->user?->email_verified_at) $pending[] = 'Verificar email';
                            if ($record->identity_status !== 'approved') $pending[] = 'Completar o revisar identidad';
                            if ($record->review_status !== 'approved') $pending[] = 'Resolver revisión del perfil';
                            if (! $record->is_published) $pending[] = 'Publicación inactiva';
                            if ($record->payments()->whereDate('expires_at', '<', today())->exists()) $pending[] = 'Renovar pago vencido';

                            return $pending === [] ? 'No hay acciones pendientes.' : implode(' · ', $pending);
                        })
                        ->color(fn (string $state): string => $state === 'No hay acciones pendientes.' ? 'success' : 'warning'),
                ]),
        ];
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Información del perfil')
                    ->persistTabInQueryString('perfil-tab')
                    ->columnSpanFull()
                    ->tabs([
                        Tab::make('Resumen')
                            ->icon(Heroicon::OutlinedUserCircle)
                            ->schema(static::summarySchema()),
                        Tab::make('Identidad')
                            ->icon(Heroicon::OutlinedIdentification)
                            ->schema([
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
                                    ]),
                                Grid::make(['default' => 1, 'md' => 3])
                                    ->schema(static::identityDocumentEntries()),
                            ]),
                        Tab::make('Datos privados')
                            ->icon(Heroicon::OutlinedLockClosed)
                            ->schema([
                                Section::make('Datos privados administrativos')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('privateDetails.real_first_name')->label('Nombre real')->placeholder('No cargado'),
                                        TextEntry::make('privateDetails.real_last_name')->label('Apellido real')->placeholder('No cargado'),
                                        TextEntry::make('privateDetails.birth_date')->label('Fecha de nacimiento')->date('d/m/Y')->placeholder('No cargada'),
                                        TextEntry::make('privateDetailsAge')
                                            ->label('Edad real')
                                            ->state(fn (ModelProfile $record): string => $record->privateDetails ? (string) $record->privateDetails->realAge().' años' : 'No calculada'),
                                        TextEntry::make('privateDetails.private_phone')->label('Teléfono privado')->placeholder('No cargado'),
                                        TextEntry::make('privateDetails.real_height_cm')->label('Altura real')->suffix(' cm')->placeholder('No cargada'),
                                        TextEntry::make('privateDetails.real_weight_kg')->label('Peso real')->suffix(' kg')->placeholder('No cargado'),
                                        TextEntry::make('privateDetails.real_measurements')->label('Medidas reales')->placeholder('No cargadas'),
                                    ]),
                            ]),
                        Tab::make('Información pública')
                            ->icon(Heroicon::OutlinedGlobeAlt)
                            ->schema([
                                Section::make('Datos visibles en la publicación')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('stage_name')->label('Nombre artístico')->placeholder('No cargado'),
                                        TextEntry::make('nationality')->label('Nacionalidad/origen')->placeholder('No cargada'),
                                        TextEntry::make('availability_status')
                                            ->label('Disponibilidad')
                                            ->badge()
                                            ->formatStateUsing(fn (string $state): string => $state === 'unavailable' ? 'No disponible' : 'Disponible')
                                            ->color(fn (string $state): string => $state === 'unavailable' ? 'warning' : 'success'),
                                        TextEntry::make('schedule_availability')
                                            ->label('Disponibilidad horaria')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'part_time' => 'Part Time',
                                                'full_time' => 'Full Time',
                                                default => 'Sin definir',
                                            })
                                            ->placeholder('Sin definir'),
                                        TextEntry::make('gender')
                                            ->label('Género')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'female' => 'Femenino',
                                                'trans' => 'Trans',
                                                default => 'Sin definir',
                                            })
                                            ->placeholder('Sin definir'),
                                        TextEntry::make('depilation')
                                            ->label('Depilación')
                                            ->formatStateUsing(fn (?string $state): string => match ($state) {
                                                'full' => 'Full',
                                                'partial' => 'Parcial',
                                                default => 'Sin definir',
                                            })
                                            ->placeholder('Sin definir'),
                                        TextEntry::make('rate_visibility')
                                            ->label('Precios')
                                            ->formatStateUsing(fn (?string $state): string => $state === 'published' ? 'Publica valores' : 'Consultar')
                                            ->placeholder('Consultar'),
                                        TextEntry::make('rate_30min')
                                            ->label('30 minutos')
                                            ->formatStateUsing(fn ($state, ModelProfile $record): string => $state === null
                                                ? 'Sin definir'
                                                : ($record->rate_30min_currency === 'USD' ? 'USD ' : '$ ').number_format((float) $state, 0, ',', '.'))
                                            ->placeholder('Sin definir'),
                                        TextEntry::make('rate_60min')
                                            ->label('1 hora')
                                            ->formatStateUsing(fn ($state, ModelProfile $record): string => $state === null
                                                ? 'Sin definir'
                                                : ($record->rate_60min_currency === 'USD' ? 'USD ' : '$ ').number_format((float) $state, 0, ',', '.'))
                                            ->placeholder('Sin definir'),
                                        TextEntry::make('province.name')->label('Provincia')->placeholder('No seleccionada'),
                                        TextEntry::make('locality.name')->label('Localidad')->placeholder('No seleccionada'),
                                        TextEntry::make('approximate_location_text')->label('Zona aproximada')->placeholder('No cargada'),
                                        TextEntry::make('height_cm')->label('Altura pública')->suffix(' cm')->placeholder('No cargada'),
                                        TextEntry::make('weight_kg')->label('Peso público')->suffix(' kg')->placeholder('No cargado'),
                                        TextEntry::make('measurements')->label('Medidas públicas')->placeholder('No cargadas'),
                                        TextEntry::make('eye_color')->label('Color de ojos')->placeholder('No cargado'),
                                        TextEntry::make('hair_color')->label('Color de cabello')->placeholder('No cargado'),
                                        TextEntry::make('skin_color')->label('Color de piel')->placeholder('No cargado'),
                                        TextEntry::make('body_type')->label('Tipo de cuerpo')->placeholder('No cargado'),
                                    ]),
                            ]),
                        Tab::make('Publicación')
                            ->icon(Heroicon::OutlinedCog)
                            ->schema([
                                Section::make('Tipo y servicios')
                                    ->columns(2)
                                    ->schema([
                                        TextEntry::make('publicationType.name')->label('Tipo de publicación')->placeholder('No definido'),
                                        TextEntry::make('services_summary')
                                            ->label('Servicios seleccionados')
                                            ->state(fn (ModelProfile $record): string => $record->services->pluck('name')->join(', ') ?: 'Ninguno'),
                                    ]),
                            ]),
                        Tab::make('Biografía')
                            ->icon(Heroicon::OutlinedDocumentText)
                            ->schema([
                                Section::make('Biografía pública')
                                    ->schema([
                                        TextEntry::make('currentBio.content')
                                            ->label('Biografía pública actual')
                                            ->placeholder('No hay biografía aprobada'),
                                    ]),
                            ]),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->whereDoesntHave('user', fn (Builder $query): Builder => $query->where('is_admin', true))
            ->with([
                'user:id,email,email_verified_at,profile_completion_status',
                'reviewer:id,email',
                'documents',
                'privateDetails',
                'province',
                'locality',
                'publicationType',
                'services',
                'currentBio',
            ]);
    }

    public static function getRelations(): array
    {
        return [
            ModelPhotosRelationManager::class,
            ModelVideosRelationManager::class,
            ModelProfilePhysicalRevisionsRelationManager::class,
            ModelProfileServicesRelationManager::class,
            PublicationPaymentsRelationManager::class,
            ModelProfileBiosRelationManager::class,
            PublicationTypeHistoryRelationManager::class,
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
                TextColumn::make('province.name')
                    ->label('Provincia')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('locality.name')
                    ->label('Localidad')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('publicationType.name')
                    ->label('Tipo de publicación')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('user.email_verified_at')
                    ->label('Email verificado')
                    ->boolean(),
                TextColumn::make('user.profile_completion_status')
                    ->label('Inscripción')->badge()
                    ->formatStateUsing(fn (?string $state): string => AccountEnrollmentState::labels()[$state] ?? 'Inscripción habilitada'),
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
                TextColumn::make('currentPayment.expires_at')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->placeholder('Sin pago'),
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
                Filter::make('near_expiry')
                    ->label('Próximas a vencer')
                    ->query(function (Builder $query): Builder {
                        return $query->whereHas('payments', fn (Builder $payment): Builder => $payment
                            ->whereDate('expires_at', '>=', today())
                            ->whereDate('expires_at', '<=', today()->addDays((int) config('publication.expiry_warning_days', 7))));
                    }),
                Filter::make('expired')
                    ->label('Vencidas')
                    ->query(fn (Builder $query): Builder => $query->whereHas('payments', fn (Builder $payment): Builder => $payment->whereDate('expires_at', '<', today()))),
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
            Action::make('registerPayment')
                ->label('Registrar pago')
                ->color('warning')
                ->icon(Heroicon::OutlinedBanknotes)
                ->authorize('update')
                ->form([
                    TextInput::make('paid_at')->label('Fecha de pago')->required()->default(now()->toDateString()),
                    TextInput::make('amount')->label('Importe')->numeric()->minValue(0.01)->required(),
                    TextInput::make('period_days')->label('Período (días)')->integer()->minValue(1)->required()->default(30),
                    TextInput::make('starts_at')->label('Inicio')->required()->default(now()->toDateString()),
                    Textarea::make('internal_note')->label('Observación interna')->maxLength(5000),
                ])
                ->action(function (ModelProfile $record, array $data): void {
                    app(\App\Services\PublicationPaymentService::class)->register($record, auth()->user(), $data);
                })
                ->successNotificationTitle('Pago registrado'),
            Action::make('requireProfileCompletion')
                ->label('Solicitar completar perfil')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('La cuenta deberá completar el formulario de inscripción antes de volver a utilizar el panel.')
                ->authorize('update')
                ->visible(fn (ModelProfile $record): bool => $record->user?->profile_completion_status !== AccountEnrollmentState::INCOMPLETE)
                ->action(fn (ModelProfile $record) => app(AccountEnrollmentState::class)->requireCompletion($record->user, auth()->user()))
                ->successNotificationTitle('Se solicitó completar el perfil'),
            Action::make('finishEnrollmentReview')
                ->label('Finalizar revisión de inscripción')
                ->requiresConfirmation()
                ->authorize('update')
                ->visible(fn (ModelProfile $record): bool => $record->user?->profile_completion_status === AccountEnrollmentState::PENDING)
                ->action(fn (ModelProfile $record) => app(AccountEnrollmentState::class)->finishReview($record->user, auth()->user()))
                ->successNotificationTitle('Inscripción revisada'),
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
                    && app(\App\Services\PublicationPaymentService::class)->hasCurrentPayment($record)
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

    /** @return array<int, Section> */
    private static function identityDocumentEntries(): array
    {
        return collect(config('identity-documents.required_types'))->map(function (string $type): Section {
            return Section::make(static::documentTypeLabel($type))
                ->icon(Heroicon::OutlinedDocument)
                ->compact()
                ->schema([
                    TextEntry::make("document_{$type}")
                        ->label('Archivo')
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
                        ->placeholder('Falta cargar este documento'),
                ]);
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
