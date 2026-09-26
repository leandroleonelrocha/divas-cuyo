<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelProfilePhysicalRevision extends Model
{
    use HasFactory;

    protected $fillable = [
        'height_cm',
        'weight_kg',
        'measurements',
        'eye_color',
        'hair_color',
        'skin_color',
        'body_type',
        'nationality',
        'status',
        'submitted_by',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'weight_kg' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
