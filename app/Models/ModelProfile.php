<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ModelProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp',
        'location',
        'review_status',
        'is_published',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'reviewed_at' => 'datetime',
            'identity_reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function identityReviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'identity_reviewed_by');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(ModelDocument::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(ModelPhoto::class)->orderBy('position');
    }

    public function currentApprovedPhotos(): HasMany
    {
        return $this->hasMany(ModelPhoto::class)
            ->whereNotNull('current_version_id')
            ->whereHas('currentVersion', fn ($query) => $query->where('status', 'approved'))
            ->orderBy('position');
    }
}
