<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\Enums\ContentTabPosition;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use App\Models\ModelProfile;

class ViewModelProfile extends ViewRecord
{
    protected static string $resource = ModelProfileResource::class;

    public function mount(int|string $record): void
    {
        parent::mount($record);
        if (in_array((string) $this->activeRelationManager, ['1', '2', '3', '5'], true)) {
            $this->activeRelationManager = '0';
        }
    }

    protected function getAllRelationManagers(): array
    {
        $managers = ModelProfileResource::getRelations();

        return [
            0 => \Filament\Resources\RelationManagers\RelationGroup::make('Contenido', [
                $managers[0], $managers[1], $managers[5], $managers[3], $managers[2],
            ]),
            4 => \Filament\Resources\RelationManagers\RelationGroup::make('Pagos', [$managers[4]]),
            6 => \Filament\Resources\RelationManagers\RelationGroup::make('Historial', [$managers[6]]),
        ];
    }

    private function profileSections(): array
    {
        $schema = ModelProfileResource::infolist(\Filament\Schemas\Schema::make($this)->record($this->getRecord()));
        $sections = [];
        foreach ($schema->getComponents()[0]->getChildComponents() as $tab) {
            $sections[$tab->getLabel()] = $tab->getChildComponents();
        }

        return $sections;
    }

    public function infolist(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        $sections = $this->profileSections();

        return $schema->components([
            \Filament\Schemas\Components\Section::make('Contacto e inscripción')->compact()->columns(2)->schema([
                \Filament\Infolists\Components\TextEntry::make('name')->label('Nombre de registro'),
                \Filament\Infolists\Components\TextEntry::make('whatsapp')->label('WhatsApp')->copyable(),
                \Filament\Infolists\Components\TextEntry::make('user.email_verified_at')->label('Correo verificado')->dateTime('d/m/Y H:i')->placeholder('No verificado'),
                \Filament\Infolists\Components\TextEntry::make('user.profile_completion_status')->label('Inscripción')
                    ->formatStateUsing(fn ($state) => \App\Services\AccountEnrollmentState::labels()[$state] ?? $state),
                \Filament\Infolists\Components\TextEntry::make('created_at')->label('Fecha de registro')->dateTime('d/m/Y H:i'),
            ]),
            ...$sections['Datos privados'],
        ]);
    }

    public function getRelationManagersContentComponent(): \Filament\Schemas\Components\Component
    {
        $sections = $this->profileSections();
        $managers = ModelProfileResource::getRelations();
        // Identity state is already visible in the compact header.
        $verification = $sections['Identidad'][0];
        $verification->schema(array_values(array_filter($verification->getChildComponents(),
            fn ($entry) => $entry->getName() !== 'identity_status')))->compact();
        $content = [];
        foreach ([0 => 'Fotografías', 1 => 'Videos', 5 => 'Biografías', 3 => 'Servicios', 2 => 'Cambios físicos'] as $index => $label) {
            $manager = $managers[$index];
            if (! $manager::canViewForRecord($this->getRecord(), static::class)) {
                continue;
            }
            $content[] = \Filament\Schemas\Components\Section::make($label)
                ->id('review-'.class_basename($manager))->compact()->collapsible()
                ->collapsed(match ($index) {
                    0 => ! $this->getRecord()->pending_photos_count,
                    1 => ! $this->getRecord()->pending_videos_count,
                    5 => ! $this->getRecord()->pending_bios_count,
                    2 => ! $this->getRecord()->pending_physical_count,
                    default => true,
                })
                ->schema([
                    \Filament\Schemas\Components\Livewire::make($manager, [
                        'ownerRecord' => $this->getRecord(), 'pageClass' => static::class,
                    ])->key($manager),
                ]);
        }
        foreach (['Información pública', 'Publicación', 'Biografía'] as $label) {
            foreach ($sections[$label] as $section) {
                $content[] = $section->compact()->collapsible()->collapsed();
            }
        }
        $relation = function (int $index) use ($managers): array {
            $manager = $managers[$index];
            return $manager::canViewForRecord($this->getRecord(), static::class)
                ? [\Filament\Schemas\Components\Livewire::make($manager, [
                    'ownerRecord' => $this->getRecord(), 'pageClass' => static::class,
                ])->key($manager)] : [];
        };

        return \Filament\Schemas\Components\Tabs::make('Detalle')
            ->key('relationManagerTabs')->livewireProperty('activeRelationManager')->contained(false)
            ->tabs([
                '' => \Filament\Schemas\Components\Tabs\Tab::make('General')->schema([$this->getInfolistContentComponent()]),
                'identity' => \Filament\Schemas\Components\Tabs\Tab::make('Identidad')->schema($sections['Identidad']),
                '0' => \Filament\Schemas\Components\Tabs\Tab::make('Contenido')->schema($content),
                '4' => \Filament\Schemas\Components\Tabs\Tab::make('Pagos')->schema([
                    \Filament\Infolists\Components\TextEntry::make('payment_summary')->label('Estado del pago')
                        ->state(fn () => \App\Services\PublicationPaymentStatus::LABELS[app(\App\Services\PublicationPaymentStatus::class)->summary($this->getRecord())['status']])->badge(),
                    ...$relation(4),
                ]),
                '6' => \Filament\Schemas\Components\Tabs\Tab::make('Historial')->schema([
                    \Filament\Schemas\Components\View::make('filament.model-review-history'),
                    ...$relation(6),
                ]),
            ]);
    }

    public function reviewTasks(): array
    {
        $record = $this->getRecord();
        $tasks = [];
        foreach (\App\Filament\Resources\ModelProfiles\ModelReviewQueue::labels($record) as $label) {
            if (str_contains($label, 'Inscripción') || str_contains($label, 'Identidad')) {
                $tasks[] = ['label' => $label, 'count' => 1, 'relation' => str_contains($label, 'Identidad') ? 'identity' : '', 'anchor' => ''];
            }
        }
        if ($record->user?->profile_completion_status === \App\Services\AccountEnrollmentState::ACTIVE
            && $record->identity_status === 'approved' && $record->review_status === 'approved') {
            foreach ([
                'pending_photos_count' => ['Fotografías', 0],
                'pending_videos_count' => ['Videos', 1],
                'pending_bios_count' => ['Biografías', 5],
                'pending_physical_count' => ['Cambios físicos', 2],
            ] as $field => [$label, $index]) {
                if ($record->$field > 0) {
                    $tasks[] = ['label' => $label, 'count' => $record->$field, 'relation' => 0,
                        'anchor' => 'review-'.class_basename(ModelProfileResource::getRelations()[$index])];
                }
            }
        }

        return $tasks;
    }

    public function content(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema->record($this->getRecord())->components([
            \Filament\Schemas\Components\View::make('filament.model-review-attention'),
            $this->getRelationManagersContentComponent(),
        ]);
    }

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'General';
    }

    public function getContentTabIcon(): string|\BackedEnum|Htmlable|null
    {
        return Heroicon::OutlinedUserCircle;
    }

    public function getContentTabPosition(): ?ContentTabPosition
    {
        return ContentTabPosition::Before;
    }

    public function getHeading(): string|Htmlable
    {
        return (string) ($this->record->stage_name ?: $this->record->name ?: 'Perfil de modelo');
    }

    public function getSubheading(): string|Htmlable|null
    {
        $location = collect([$this->record->locality?->name, $this->record->province?->name])->filter()->unique()->join(', ');

        return ($this->record->user?->email ?? 'Sin email').' · '.($location ?: ($this->record->location ?: 'Sin ubicación'));
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('back')->label('Volver al listado')->url(ModelProfileResource::getUrl()),
            EditAction::make()
                ->label('Editar')
                ->icon(Heroicon::OutlinedPencilSquare),
            ActionGroup::make(array_map(function ($action) {
                return $action->hidden(fn () => (string) $this->activeRelationManager !== match ($action->getName()) {
                    'registerPayment' => '4',
                    'approveIdentity', 'rejectIdentity' => 'identity',
                    'publish', 'unpublish', 'reject' => '0',
                    default => '',
                });
            }, ModelProfileResource::moderationActions()))
                ->label('Moderación')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('gray')
                ->button(),
        ];
    }
}
