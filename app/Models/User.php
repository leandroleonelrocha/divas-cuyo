<?php

namespace App\Models;

use App\Services\EmailVerificationService;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasMany as EloquentHasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements CanResetPasswordContract, FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use CanResetPassword, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'whatsapp',
        'location',
        'is_published',
        'is_admin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'is_published',
        'is_admin',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_published' => 'boolean',
            'is_admin' => 'boolean',
        ];
    }

    public function policyAcceptances(): HasMany
    {
        return $this->hasMany(PolicyAcceptance::class);
    }

    public function emailVerificationToken(): HasOne
    {
        return $this->hasOne(EmailVerificationToken::class);
    }

    public function modelProfile(): HasOne
    {
        return $this->hasOne(ModelProfile::class);
    }

    public function modelDocuments(): HasManyThrough
    {
        return $this->hasManyThrough(ModelDocument::class, ModelProfile::class);
    }

    public function reviewedPhotoVersions(): EloquentHasMany
    {
        return $this->hasMany(ModelPhotoVersion::class, 'reviewed_by');
    }

    public function isAdmin(): bool
    {
        return (bool) $this->is_admin;
    }

    public function isVerifiedAdmin(): bool
    {
        return $this->isAdmin() && $this->hasVerifiedEmail();
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $panel->getId() === 'admin'
            && $this->isVerifiedAdmin();
    }

    public function sendEmailVerificationNotification(): void
    {
        app(EmailVerificationService::class)->send($this);
    }
}
