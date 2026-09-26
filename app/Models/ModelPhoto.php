<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModelPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'model_profile_id',
        'current_version_id',
        'position',
        'is_primary',
    ];

    protected $hidden = [
        'model_profile_id',
        'current_version_id',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_primary' => 'boolean',
        ];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function currentVersion(): BelongsTo
    {
        return $this->belongsTo(ModelPhotoVersion::class, 'current_version_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(ModelPhotoVersion::class);
    }

    public function latestVersion(): HasOne
    {
        return $this->hasOne(ModelPhotoVersion::class)->latestOfMany('version');
    }
}
