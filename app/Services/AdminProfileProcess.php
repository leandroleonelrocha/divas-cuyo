<?php

namespace App\Services;

use App\Models\ModelProfile;

class AdminProfileProcess
{
    public function steps(ModelProfile $profile): array
    {
        $profile->loadMissing(['user', 'currentBio', 'photos.currentVersion', 'payments']);
        $active = $profile->user?->profile_completion_status === AccountEnrollmentState::ACTIVE;
        $registered = $active && $profile->user?->hasVerifiedEmail();
        $enrollmentPending = $profile->user?->profile_completion_status === AccountEnrollmentState::PENDING;
        $pending = collect([
            'pending_photos_count' => 'fotografías', 'pending_bios_count' => 'biografías',
            'pending_videos_count' => 'videos', 'pending_physical_count' => 'cambios físicos',
        ])->filter(fn ($label, $field) => $profile->$field > 0)
            ->map(fn ($label, $field) => $profile->$field.' '.$label)->join(' · ');
        $contentApproved = $profile->review_status === 'approved'
            && $profile->currentBio?->status === 'approved'
            && $profile->photos->contains(fn ($photo) => $photo->is_primary && $photo->currentVersion?->status?->value === 'approved');
        $payment = app(PublicationPaymentStatus::class)->summary($profile);
        $visible = app(PublicModelProfileVisibility::class)->publiclyVisible($profile);
        return [
            ['title' => 'Cuenta registrada', 'state' => $registered ? 'success' : ($enrollmentPending ? 'warning' : 'gray'),
                'detail' => $registered ? 'Correo verificado · Inscripción habilitada' : ($enrollmentPending ? 'Inscripción por habilitar' : 'Registro incompleto'), 'relation' => ''],
            ['title' => 'Identidad', 'state' => match ($profile->identity_status) { 'approved' => 'success', 'pending' => 'warning', 'rejected' => 'danger', default => 'gray' },
                'detail' => match ($profile->identity_status) { 'approved' => 'Aprobada', 'pending' => 'En revisión', 'rejected' => 'Rechazada: requiere corrección', default => 'Falta documentación' }, 'relation' => 'identity'],
            ['title' => 'Contenido', 'state' => $pending ? 'warning' : ($profile->review_status === 'rejected' ? 'danger' : ($contentApproved ? 'success' : 'gray')),
                'detail' => $pending ? 'Pendientes: '.$pending : ($contentApproved ? 'Aprobado' : 'Revisar requisitos del contenido'), 'relation' => '0'],
            ['title' => 'Pagos', 'state' => match ($payment['status']) { 'current' => 'success', 'near_expiry' => 'warning', 'expired' => 'danger', 'scheduled' => 'info', default => 'gray' },
                'detail' => PublicationPaymentStatus::LABELS[$payment['status']].($payment['expires_at'] ? ' · '.$payment['expires_at']->format('d/m/Y') : ''), 'relation' => '4'],
            ['title' => 'Publicación', 'state' => $visible ? 'success' : 'gray',
                'detail' => $visible ? 'Visible en el sitio' : ($profile->is_published ? 'Habilitada, sin visibilidad pública' : 'No publicada'), 'relation' => '0'],
        ];
    }
}
