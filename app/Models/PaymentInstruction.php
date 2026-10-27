<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentInstruction extends Model
{
    use HasFactory;

    protected $fillable = ['alias', 'transfer_details', 'receipt_whatsapp', 'default_amount', 'default_amount_virtual', 'is_active'];

    protected function casts(): array
    {
        return ['default_amount' => 'decimal:2', 'default_amount_virtual' => 'decimal:2', 'is_active' => 'boolean'];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
