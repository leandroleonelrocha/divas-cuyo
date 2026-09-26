<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PublicationType extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'allows_in_person_services',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'allows_in_person_services' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function modelProfiles(): HasMany
    {
        return $this->hasMany(ModelProfile::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
