<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicationPayment extends Model
{
    use HasFactory;

    protected $fillable = ['model_profile_id', 'registered_by', 'paid_at', 'amount', 'period_days', 'starts_at', 'expires_at', 'internal_note'];

    protected function casts(): array
    {
        return ['paid_at' => 'date', 'amount' => 'decimal:2', 'starts_at' => 'date', 'expires_at' => 'date'];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class, 'model_profile_id');
    }

    public function registeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'registered_by');
    }
}
