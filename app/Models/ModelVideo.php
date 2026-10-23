<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ModelVideo extends Model
{
    use SoftDeletes;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['hosting_history' => 'array', 'watermarked_at' => 'datetime', 'reviewed_at' => 'datetime', 'duration' => 'float'];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function ready(): bool
    {
        return $this->processing_status === 'ready' && $this->watermarked_at !== null && $this->resource_id !== null;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->processing_status === 'failed' => 'Error de procesamiento',
            ! $this->ready() => 'Procesando',
            $this->moderation_status === 'approved' => 'Aprobado',
            $this->moderation_status === 'rejected' => 'Rechazado',
            default => 'Pendiente de aprobación',
        };
    }
}
