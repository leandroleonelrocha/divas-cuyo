<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelDocument extends Model
{
    use HasFactory;

    protected $fillable = [];

    protected $hidden = [
        'storage_path',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
