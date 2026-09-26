<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelProfileBio extends Model
{
    use HasFactory;

    protected $fillable = [
        'content',
        'status',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return ['reviewed_at' => 'datetime'];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
