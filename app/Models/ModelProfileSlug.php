<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelProfileSlug extends Model
{
    protected $guarded = ['*'];

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }
}
