<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ModelProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'whatsapp',
        'location',
        'stage_name',
        'public_age',
        'show_age',
        'height_cm',
        'weight_kg',
        'measurements',
        'eye_color',
        'hair_color',
        'skin_color',
        'body_type',
        'nationality',
        'availability_status',
        'publication_type_id',
        'current_bio_id',
        'province_id',
        'locality_id',
        'approximate_location_text',
        'approximate_latitude',
        'approximate_longitude',
        'review_status',
        'is_published',
    ];

    protected $hidden = [
        'privateDetails',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'show_age' => 'boolean',
            'public_age' => 'integer',
            'height_cm' => 'integer',
            'weight_kg' => 'decimal:2',
            'availability_status' => 'string',
            'publication_type_id' => 'integer',
            'current_bio_id' => 'integer',
            'province_id' => 'integer',
            'locality_id' => 'integer',
            'approximate_latitude' => 'decimal:7',
            'approximate_longitude' => 'decimal:7',
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

    public function privateDetails(): HasOne
    {
        return $this->hasOne(ModelProfilePrivateDetail::class);
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function locality(): BelongsTo
    {
        return $this->belongsTo(Locality::class);
    }

    public function publicationType(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class);
    }

    public function publicationTypeHistory(): HasMany
    {
        return $this->hasMany(ModelProfilePublicationTypeHistory::class)->latest('changed_at');
    }

    public function services(): BelongsToMany
    {
        return $this->belongsToMany(Service::class, 'model_profile_service')->withTimestamps();
    }

    public function bios(): HasMany
    {
        return $this->hasMany(ModelProfileBio::class)->latest();
    }

    public function currentBio(): BelongsTo
    {
        return $this->belongsTo(ModelProfileBio::class, 'current_bio_id');
    }

    public function physicalRevisions(): HasMany
    {
        return $this->hasMany(ModelProfilePhysicalRevision::class)->latest();
    }

    public function pendingPhysicalRevision(): HasOne
    {
        return $this->hasOne(ModelProfilePhysicalRevision::class)->where('status', 'pending');
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
