@php
    $progressPayment = app(\App\Services\PublicationPaymentStatus::class)->summary($profile);
    $progressBio = $profile->bios()->latest('id')->first();
    $progressPhotos = $profile->photos()->with('latestVersion', 'currentVersion')->get();
    $progressRejected = $progressBio?->status === 'rejected'
        || $progressPhotos->contains(fn ($photo) => $photo->latestVersion?->status === \App\Enums\ModelPhotoVersionStatus::Rejected);
    $progressPending = $progressBio?->status === 'pending'
        || $progressPhotos->contains(fn ($photo) => $photo->latestVersion?->status === \App\Enums\ModelPhotoVersionStatus::Pending);
    $approvedContent = $profile->currentBio?->status === 'approved'
        && $progressPhotos->contains(fn ($photo) => $photo->is_primary && $photo->currentVersion?->status === \App\Enums\ModelPhotoVersionStatus::Approved);
    $contentLabel = match (true) {
        $progressRejected => $approvedContent ? 'Aprobado · cambios rechazados' : 'Requiere corrección',
        $progressPending => $approvedContent ? 'Aprobado · cambios en revisión' : 'En revisión',
        $approvedContent => 'Aprobado',
        default => 'Por completar',
    };
    $progressStages = [
        ['Identidad', match ($profile->identity_status) { 'approved' => 'Aprobada', 'pending' => 'En revisión', 'rejected' => 'Requiere corrección', default => 'Por completar' }, $profile->identity_status === 'approved'],
        ['Contenido', $contentLabel, $approvedContent && ! $progressPending && ! $progressRejected],
        ['Pago', \App\Services\PublicationPaymentStatus::LABELS[$progressPayment['status']], in_array($progressPayment['status'], ['current', 'near_expiry'], true)],
        ['Publicación', $dashboardVisible ? 'Visible' : ($profile->is_published ? 'Habilitada · no visible' : 'No habilitada'), $dashboardVisible],
    ];
@endphp
<x-account.progress :progress-stages="$progressStages" />
