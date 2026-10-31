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
            TextColumn::make('preview')->label('Vista previa')->state(fn (ModelVideo $record): string => $record->ready()
                ? '<video controls playsinline preload="none" style="width:240px;max-height:300px" poster="'.e(route('admin.model-videos.file', [$record, 'poster'])).'" src="'.e(route('admin.model-videos.file', [$record, 'video'])).'"></video>'
                : 'Procesamiento pendiente')->html(),
            TextColumn::make('processing_status')->label('Estado')->formatStateUsing(fn (ModelVideo $record): string => $record->statusLabel())->badge(),
            TextColumn::make('duration')->label('Segundos'),
            TextColumn::make('rejection_reason')->label('Motivo')->limit(80),
            TextColumn::make('created_at')->label('Subido')->dateTime('d/m/Y H:i'),
        ])->recordActions([
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
}
