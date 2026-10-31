<?php

namespace App\Filament\Resources\ModelProfiles\Pages;

use App\Filament\Resources\ModelProfiles\ModelProfileResource;
use App\Models\ModelProfile;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListModelProfiles extends ListRecords
{
    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('import')
                ->label('Importar Excel')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(\App\Filament\Pages\ImportModelProfiles::getUrl()),
        ];
    }

    protected static string $resource = ModelProfileResource::class;

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->whereDoesntHave('user', fn (Builder $query): Builder => $query->where('is_admin', true));
    }

    /**
     * @return array<string, Tab>
     */
    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todas'),
            'pending' => Tab::make('Pendientes')
                ->badgeTooltip('Inscripciones, identidades enviadas y contenido listo para moderar. No incluye perfiles incompletos.')
                ->modifyQueryUsing(fn (Builder $query) => \App\Filament\Resources\ModelProfiles\ModelReviewQueue::filter($query, 'pending')),
            'published' => Tab::make('Publicadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', true)),
            'unpublished' => Tab::make('No publicadas')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_published', false)),
        ];
    }

    // Preserve existing dashboard links without adding visible tabs.
    protected function modifyQueryWithActiveTab(Builder $query, bool $isResolvingRecord = false): Builder
    {
        if (in_array($this->activeTab, ['pending_enrollment', 'pending_identity', 'pending_content', 'pending_photos', 'pending_bios', 'pending_review'], true)) {
            return \App\Filament\Resources\ModelProfiles\ModelReviewQueue::filter($query, $this->activeTab);
        }

        return parent::modifyQueryWithActiveTab($query, $isResolvingRecord);
    }
}
