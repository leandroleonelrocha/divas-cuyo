<?php

namespace App\Filament\Resources\ModelProfiles\RelationManagers;

use App\Models\ModelVideo;
use App\Services\ModelVideoService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModelVideosRelationManager extends RelationManager
{
    protected static bool $isLazy = false;

    protected static string $relationship = 'videos';

    protected static ?string $title = 'Videos';

    public function table(Table $table): Table
    {
        return $table->extraAttributes(['id' => 'review-ModelVideosRelationManager'])->columns([
            TextColumn::make('original_name')->label('Archivo')->limit(50),
            TextColumn::make('preview')->label('Vista previa')
                ->state(fn (ModelVideo $record): string => $record->ready()
                    ? '<img src="'.e(route('admin.model-videos.file', [$record, 'poster'])).'" alt="Reproducir video" style="width:90px;height:120px;object-fit:cover;border-radius:8px"><span>▶ Ver video</span>'
                    : e($record->statusLabel()))
                ->html()
                ->action($this->previewAction('previewVideo')),
            TextColumn::make('processing_status')->label('Estado')->formatStateUsing(fn (ModelVideo $record): string => $record->statusLabel())->badge(),
            TextColumn::make('duration')->label('Segundos'),
            TextColumn::make('rejection_reason')->label('Motivo')->limit(80),
            TextColumn::make('created_at')->label('Subido')->dateTime('d/m/Y H:i'),
        ])->recordActions([
            $this->previewAction('watchVideo')->label('Ver video')->icon('heroicon-o-play'),
            Action::make('approve')->label('Aprobar')->color('success')->requiresConfirmation()
                ->visible(fn (ModelVideo $record): bool => $record->ready() && $record->moderation_status !== 'approved')
                ->authorize(fn (ModelVideo $record): bool => auth()->user()->can('moderate', $record))
                ->action(fn (ModelVideo $record) => app(ModelVideoService::class)->moderate(auth()->user(), $record, 'approved')),
            Action::make('reject')->label('Rechazar')->color('danger')
                ->schema([Textarea::make('reason')->label('Motivo')->required()->maxLength(5000)])
                ->visible(fn (ModelVideo $record): bool => $record->ready() && $record->moderation_status !== 'rejected')
                ->authorize(fn (ModelVideo $record): bool => auth()->user()->can('moderate', $record))
                ->action(fn (ModelVideo $record, array $data) => app(ModelVideoService::class)->moderate(auth()->user(), $record, 'rejected', $data['reason'])),
        ])->emptyStateHeading('Todavía no hay videos');
    }
    private function previewAction(string $name): Action
    {
        return Action::make($name)
            ->visible(fn (ModelVideo $record): bool => $record->ready())
            ->authorize(fn (ModelVideo $record): bool => auth()->user()?->can('moderate', $record) ?? false)
            ->modalHeading('Revisar videos')
            ->modalWidth('5xl')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->modalContent(function (ModelVideo $record) {
                $videos = $this->getOwnerRecord()->videos()
                    ->where('processing_status', 'ready')
                    ->whereNotNull('watermarked_at')->whereNotNull('resource_id')
                    ->orderBy('id')->get();
                $initialIndex = $videos->search(fn (ModelVideo $video): bool => $video->is($record));
                $items = $videos->map(fn (ModelVideo $video): array => [
                    'url' => route('admin.model-videos.file', [$video, 'video']),
                    'poster' => route('admin.model-videos.file', [$video, 'poster']),
                    'name' => $video->original_name,
                    'status' => $video->statusLabel(),
                ])->values();

                return view('filament.model-video-gallery', [
                    'items' => $items, 'initialIndex' => $initialIndex === false ? 0 : $initialIndex,
                ]);
            });
    }

}
