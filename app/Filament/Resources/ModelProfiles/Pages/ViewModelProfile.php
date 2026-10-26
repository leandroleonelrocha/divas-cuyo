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

    public function hasCombinedRelationManagerTabsWithContent(): bool
    {
        return true;
    }

    public function getContentTabLabel(): ?string
    {
        return 'Perfil';
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
        $email = $this->record->user?->email ?? 'Sin email';
        $review = match ($this->record->review_status) {
            'approved' => 'Aprobado',
            'rejected' => 'Rechazado',
            default => 'Pendiente',
        };
        $publication = $this->record->is_published ? 'Publicado' : 'No publicado';

        return $email.' · Revisión: '.$review.' · Publicación: '.$publication;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Editar')
                ->icon(Heroicon::OutlinedPencilSquare),
            ActionGroup::make(ModelProfileResource::moderationActions())
                ->label('Moderación')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('gray')
                ->button(),
        ];
    }
}
