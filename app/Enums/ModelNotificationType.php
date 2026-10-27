<?php

namespace App\Enums;

enum ModelNotificationType: string
{
    case IdentityApproved = 'identity_approved';
    case IdentityRejected = 'identity_rejected';
    case ProfileApproved = 'profile_approved';
    case ProfileRejected = 'profile_rejected';
    case PhotoApproved = 'photo_approved';
    case PhotoRejected = 'photo_rejected';
    case BioApproved = 'bio_approved';
    case BioRejected = 'bio_rejected';
    case PhysicalApproved = 'physical_approved';
    case PhysicalRejected = 'physical_rejected';
    case VideoApproved = 'video_approved';
    case VideoRejected = 'video_rejected';

    public function label(): string
    {
        return match ($this) {
            self::IdentityApproved => 'Identidad aprobada',
            self::IdentityRejected => 'Identidad rechazada',
            self::ProfileApproved => 'Perfil aprobado',
            self::ProfileRejected => 'Perfil rechazado',
            self::PhotoApproved => 'Foto aprobada',
            self::PhotoRejected => 'Foto rechazada',
            self::BioApproved => 'Biografía aprobada',
            self::BioRejected => 'Biografía rechazada',
            self::PhysicalApproved => 'Modificación física aprobada',
            self::PhysicalRejected => 'Modificación física rechazada',
            self::VideoApproved => 'Video aprobado',
            self::VideoRejected => 'Video rechazado',
        };
    }

    public function isRejection(): bool
    {
        return str_ends_with($this->value, 'rejected');
    }

    public function message(?string $detail = null): string
    {
        $base = match ($this) {
            self::IdentityApproved => 'Tu documentación de identidad fue aprobada por el equipo.',
            self::IdentityRejected => 'Tu documentación de identidad fue rechazada en la revisión.',
            self::ProfileApproved => 'Tu perfil fue aprobado por el equipo.',
            self::ProfileRejected => 'Tu perfil fue rechazado en la revisión.',
            self::PhotoApproved => 'Tu fotografía fue aprobada y ya está disponible en tu perfil.',
            self::PhotoRejected => 'Tu fotografía fue rechazada en la revisión.',
            self::BioApproved => 'Tu biografía fue aprobada y ya se muestra en tu perfil.',
            self::BioRejected => 'Tu biografía fue rechazada en la revisión.',
            self::PhysicalApproved => 'La modificación física que enviaste fue aprobada.',
            self::PhysicalRejected => 'La modificación física que enviaste fue rechazada.',
            self::VideoApproved => 'Tu video fue aprobado y ya está disponible en tu perfil.',
            self::VideoRejected => 'Tu video fue rechazado en la revisión.',
        };

        $detail = trim((string) $detail);

        if ($detail === '') {
            return $base;
        }

        return $this->isRejection() ? $base.' Motivo: '.$detail : $base.' '.$detail;
    }
}
