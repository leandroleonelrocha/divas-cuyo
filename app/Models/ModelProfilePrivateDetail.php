<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelProfilePrivateDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'real_first_name',
        'real_last_name',
        'birth_date',
        'real_height_cm',
        'real_weight_kg',
        'real_measurements',
        'nationality',
        'private_phone',
    ];

    protected $hidden = [
        'id',
        'model_profile_id',
        'created_at',
        'updated_at',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'real_weight_kg' => 'decimal:2',
        ];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function realAge(?CarbonInterface $today = null): int
    {
        return $this->birth_date->diffInYears($today ?: now());
    }
}
