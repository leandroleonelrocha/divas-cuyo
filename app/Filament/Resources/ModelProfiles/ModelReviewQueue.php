<?php

namespace App\Filament\Resources\ModelProfiles;

use App\Filament\Widgets\PendingContent;
use App\Filament\Widgets\PendingEnrollments;
use App\Filament\Widgets\PendingIdentities;
use App\Models\ModelProfile;
use App\Services\AccountEnrollmentState;
use Illuminate\Database\Eloquent\Builder;

class ModelReviewQueue
{
    public static function filter(Builder $query, string $kind): Builder
    {
        return match ($kind) {
            'pending_enrollment' => PendingEnrollments::filter($query),
            'pending_identity' => PendingIdentities::filter($query),
            'pending_content' => PendingContent::filter($query),
            'pending_photos' => $query->whereHas('photos.latestVersion', fn (Builder $q) => $q->where('status', 'pending')),
            'pending_bios' => $query->whereHas('bios', fn (Builder $q) => $q->where('status', 'pending')),
            'pending_review' => $query->where('review_status', 'pending'),
            'pending' => $query->where(fn (Builder $q) => $q
                ->where(fn (Builder $q) => PendingEnrollments::filter($q))
                ->orWhere(fn (Builder $q) => PendingIdentities::filter($q))
                ->orWhere(fn (Builder $q) => PendingContent::filter($q))),
            default => $query,
        };
    }

    public static function labels(ModelProfile $record): array
    {
        if ($record->user?->profile_completion_status === AccountEnrollmentState::PENDING) {
            return ['Inscripción por habilitar'];
        }
        if ($record->user?->profile_completion_status !== AccountEnrollmentState::ACTIVE) {
            return [];
        }
        if ($record->identity_status === 'pending') {
            return ['Identidad por verificar'];
        }
        if ($record->identity_status !== 'approved' || $record->review_status !== 'approved') {
            return [];
        }

        $labels = [];
        foreach (['pending_photos_count' => 'fotos', 'pending_bios_count' => 'biografías',
            'pending_videos_count' => 'videos', 'pending_physical_count' => 'cambios físicos'] as $field => $label) {
            if ($record->$field > 0) {
                $labels[] = $record->$field.' '.$label;
            }
        }

        return $labels;
    }
}
