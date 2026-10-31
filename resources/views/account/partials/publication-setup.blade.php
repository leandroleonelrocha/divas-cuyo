@php
    $photos = $profile->photos()->with(['latestVersion', 'currentVersion'])->get();
    $photoCounts = $photos->map(fn ($photo) => $photo->latestVersion?->status?->value)->countBy();
    $hasApprovedPhoto = $photos->contains(fn ($photo) => $photo->currentVersion?->status === \App\Enums\ModelPhotoVersionStatus::Approved);
    $primary = $photos->firstWhere('is_primary', true);
    $bio = $profile->bios()->latest('id')->first() ?? $profile->currentBio;
    $services = $profile->services()->active()->count();
    $videos = $profile->videos()->get();
    $photoSummary = $photos->isEmpty() ? 'Sin fotos' : $photos->count().($photos->count() === 1 ? ' foto cargada' : ' fotos cargadas');
    foreach (['pending' => 'en revisión', 'rejected' => 'para corregir', 'approved' => 'aprobadas'] as $status => $label) {
        if ($photoCounts->get($status, 0)) $photoSummary .= ' · '.$photoCounts[$status].' '.$label;
    }
    if ($hasApprovedPhoto && ! $primary) $photoSummary .= ' · Falta elegir la principal';
    elseif ($photos->isNotEmpty() && ! $primary) $photoSummary .= ' · Podrás elegir la principal cuando haya una foto aprobada';
    $bioSummary = match ($bio?->status) {
        'approved' => 'Aprobada',
        'pending' => 'En revisión',
        'rejected' => 'Requiere corrección',
        default => 'Pendiente de completar',
    };
    $videoSummary = $videos->isEmpty() ? 'Sin videos · Opcional'
        : $videos->groupBy(fn ($video) => $video->statusLabel())->map(fn ($items, $status) => $items->count().' · '.$status)->implode(' / ');
    $contentSteps = [
        ['title' => 'Fotos', 'description' => 'Subí tus fotos y elegí una como principal.', 'status' => $photoSummary, 'href' => route('account.photos.index'), 'link' => 'Gestionar fotos', 'reason' => null],
        ['title' => 'Biografía', 'description' => 'Contá sobre vos para presentar tu perfil.', 'status' => $bioSummary, 'href' => route('account.profile.edit'), 'link' => $bio?->status === 'pending' ? 'Ver biografía enviada' : 'Editar biografía', 'reason' => $bio?->status === 'rejected' ? $bio->rejection_reason : null],
        ['title' => 'Servicios', 'description' => 'Revisá los servicios que ofrecés según tu modalidad.', 'status' => $services ? 'Guardados · '.$services.($services === 1 ? ' servicio' : ' servicios') : 'Sin servicios seleccionados', 'href' => route('account.services.edit'), 'link' => 'Editar servicios', 'reason' => null],
        ['title' => 'Videos — opcional', 'description' => 'Podés sumar videos. No son necesarios para avanzar.', 'status' => $videoSummary, 'href' => route('account.videos.index'), 'link' => 'Gestionar videos', 'reason' => null],
    ];
    $stepHelp = [
        'Fotos' => match (true) {
            $photos->isEmpty() => 'Te toca a vos: subí tus fotos para enviarlas a revisión.',
            $photoCounts->get('rejected', 0) > 0 => 'Te toca a vos: revisá el motivo de rechazo y corregí las fotos indicadas.',
            $hasApprovedPhoto && ! $primary => 'Te toca a vos: elegí una de las fotos aprobadas como principal.',
            $photoCounts->get('pending', 0) > 0 => 'Esperando a la administración: tus fotos enviadas están pendientes de aprobación. No necesitás volver a subirlas.',
            default => 'Tus fotos aprobadas ya fueron revisadas por la administración.',
        },
        'Biografía' => match ($bio?->status) {
            'pending' => 'Esperando a la administración: tu biografía está pendiente de aprobación. No necesitás volver a enviarla.',
            'approved' => 'Tu biografía fue aprobada. No tenés tareas pendientes en este paso.',
            'rejected' => 'Te toca a vos: corregí el texto según el motivo indicado.',
            default => 'Te toca a vos: escribí y guardá tu biografía.',
        },
        'Servicios' => $services
            ? 'Tu selección está guardada. Los servicios no requieren una aprobación individual de la administración.'
            : 'Todavía no seleccionaste servicios. Podés elegir los que correspondan a tu modalidad.',
        'Videos — opcional' => $videos->isEmpty()
            ? 'No tenés que hacer nada en este paso si no querés agregar videos.'
            : 'Los videos se procesan primero y luego se revisan. Consultá cada video para ver si espera aprobación o necesita una corrección.',
    ];
    $stepColors = [
        'Fotos' => $photoCounts->get('rejected', 0) ? 'rejected' : ($photoCounts->get('pending', 0) ? 'pending' : ($hasApprovedPhoto ? 'success' : 'muted')),
        'Biografía' => match ($bio?->status) { 'approved' => 'success', 'pending' => 'pending', 'rejected' => 'rejected', default => 'muted' },
        'Servicios' => $services ? 'success' : 'muted',
        'Videos — opcional' => 'muted',
    ];
    $waitingFor = [];
    if ($photoCounts->get('pending', 0)) $waitingFor[] = 'fotos';
    if ($bio?->status === 'pending') $waitingFor[] = 'biografía';
    if ($videos->contains(fn ($video) => $video->ready() && $video->moderation_status === 'pending')) $waitingFor[] = 'videos';
    $contentAction = match (true) {
        $bio?->status === 'rejected' => ['label' => 'Corregir mi biografía', 'href' => route('account.profile.edit')],
        $photos->isEmpty() => ['label' => 'Subir mis fotos', 'href' => route('account.photos.index')],
        $photoCounts->get('rejected', 0) > 0 => ['label' => 'Corregir mis fotos', 'href' => route('account.photos.index')],
        $hasApprovedPhoto && ! $primary => ['label' => 'Elegir foto principal', 'href' => route('account.photos.index')],
        ! $bio || $bio->status === 'rejected' => ['label' => $bio ? 'Corregir mi biografía' : 'Completar mi biografía', 'href' => route('account.profile.edit')],
        default => null,
    };
    $visibleNow = app(\App\Services\PublicModelProfileVisibility::class)->publiclyVisible($profile);
    $contentMessage = match (true) {
        $visibleNow => 'Tu perfil está publicado. Los cambios enviados se revisan por separado.',
        $waitingFor !== [] => 'Esperando revisión de la administración: '.implode(', ', $waitingFor).'. Lo enviado no necesita reenviarse; si hay algo para corregir, lo verás indicado arriba.',
        default => 'El contenido cargado no publica tu perfil automáticamente. La administración verificará los requisitos restantes para continuar.',
    };
@endphp
<x-account.stage title="Prepará tu publicación" id="publication-setup-title">
    <ol class="profile-setup-steps">
        @foreach ($contentSteps as $step)
            <x-account.task :title="$step['title']" :description="$stepHelp[$step['title']]"
                :status="$step['status']" :color="$stepColors[$step['title']]" :href="$step['href']"
                :action="$step['link']" :number="$loop->iteration" :reason="$step['reason']"
                :attention="($contentAction['href'] ?? null) === $step['href']" />
        @endforeach
    </ol>
    <div class="profile-setup-footer">
        @if ($contentAction)
            <x-account.primary-action :href="$contentAction['href']">{{ $contentAction['label'] }}</x-account.primary-action>
        @endif
        <x-account.notice>{{ $contentMessage }}</x-account.notice>
    </div>
</x-account.stage>
