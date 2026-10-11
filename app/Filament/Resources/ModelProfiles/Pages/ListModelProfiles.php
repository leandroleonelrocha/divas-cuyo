<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Models\ModelProfile;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListModelProfiles extends ListRecords
{
    protected static string $resource = ModelProfileResource::class;

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas'),
            'pending_review' => Tab::make('Pendientes de revisión')
                ->badge(fn (): ?string => $this->tabBadge(ModelProfile::query()->where('review_status', 'pending')))
                ->badgeColor('warning')
                ->badgeTooltip('Modelos esperando aprobación del perfil')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('review_status', 'pending')),
            'pending_identity' => Tab::make('Identidad pendiente')
                ->badge(fn (): ?string => $this->tabBadge(ModelProfile::query()->where('identity_status', 'pending')))
                ->badgeColor('warning')
                ->badgeTooltip('Modelos con documentación por verificar')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('identity_status', 'pending')),
            'published' => Tab::make('Publicadas')
                ->badge(fn (): ?string => $this->tabBadge(ModelProfile::query()->where('is_published', true)))
                ->badgeColor('success')
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('is_published', true)),
        ];
    }

    private function tabBadge(Builder $query): ?string
    {
        $count = $query->count();

        return $count > 0 ? (string) $count : null;
    }
}
