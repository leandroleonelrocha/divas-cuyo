<?php

namespace App\Models;

use App\Enums\ModelNotificationType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelNotification extends Model
{
    protected $fillable = [
        'model_profile_id',
        'type',
        'detail',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'type' => ModelNotificationType::class,
            'read_at' => 'datetime',
        ];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }
}
