<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ModelProfilePublicationTypeHistory extends Model
{
    use HasFactory;

    protected $table = 'model_profile_publication_type_history';

    protected $fillable = [
        'from_publication_type_id',
        'to_publication_type_id',
        'changed_by_user_id',
        'source',
        'reason',
        'changed_at',
    ];

    protected function casts(): array
    {
        return ['changed_at' => 'datetime'];
    }

    public function modelProfile(): BelongsTo
    {
        return $this->belongsTo(ModelProfile::class);
    }

    public function fromPublicationType(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class, 'from_publication_type_id');
    }

    public function toPublicationType(): BelongsTo
    {
        return $this->belongsTo(PublicationType::class, 'to_publication_type_id');
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
