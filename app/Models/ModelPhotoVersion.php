<?php

namespace App\Models;

use App\Enums\ModelPhotoVersionStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ModelPhotoVersion extends Model
{
    protected static function booted(): void
    {
        static::creating(function (self $version): void {
            $version->public_token ??= (string) Str::uuid();
        });
    }

    protected $fillable = [
        'model_photo_id',
        'version',
        'supersedes_version_id',
        'original_path',
        'processed_path',
        'public_path',
        'thumbnail_path',
        'original_name',
        'mime_type',
        'file_size',
        'width',
        'height',
        'processed_width',
        'processed_height',
        'status',
        'rejection_reason',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $hidden = [
        'original_path',
        'processed_path',
        'public_path',
        'thumbnail_path',
        'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => ModelPhotoVersionStatus::class,
            'file_size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'processed_width' => 'integer',
            'processed_height' => 'integer',
            'reviewed_at' => 'datetime',
            'public_watermarked_at' => 'datetime',
        ];
    }

    public function photo(): BelongsTo
    {
        return $this->belongsTo(ModelPhoto::class, 'model_photo_id');
    }

    public function supersededVersion(): BelongsTo
    {
        return $this->belongsTo(self::class, 'supersedes_version_id');
    }

    public function successors(): HasMany
    {
        return $this->hasMany(self::class, 'supersedes_version_id');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
