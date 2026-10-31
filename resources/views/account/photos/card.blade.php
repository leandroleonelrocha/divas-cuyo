@php
    $currentVersion = $photo->currentVersion;
    $latestVersion = $photo->latestVersion;
    $version = $currentVersion ?? $latestVersion;
    $statusVersion = $latestVersion ?? $currentVersion;
    $status = $statusVersion?->status?->value ?? 'pending';
    $hasApprovedCurrent = $currentVersion?->status?->value === 'approved';
    $hasNewVersion = $currentVersion && $latestVersion && $currentVersion->id !== $latestVersion->id;
    $statusLabel = $hasNewVersion && $status === 'pending'
        ? 'Nueva versión en revisión'
        : (['pending' => 'En revisión', 'approved' => 'Aprobada', 'rejected' => 'Rechazada'][$status] ?? 'En revisión');
    $statusClass = (['pending' => 'pending', 'approved' => 'approved', 'rejected' => 'rejected'][$status] ?? 'pending');
    $position = $index + 1;
@endphp
<article class="photo-card" data-photo-id="{{ $photo->id }}" data-photo-card>
    <button type="button" class="photo-preview" data-photo-view="{{ route('account.photos.file', [$photo, 'processed']) }}" aria-label="Ampliar fotografía {{ $position }}">
        @if ($version)
            <img src="{{ route('account.photos.file', [$photo, 'thumbnail']) }}" alt="Fotografía {{ $position }} de {{ $profile->name }}" loading="lazy">
        @else
            <span aria-hidden="true">Sin vista previa</span>
        @endif
    </button>
    <div class="photo-card-body">
        <div class="photo-card-heading">
            <span class="photo-position" data-photo-position>Foto {{ $position }}</span>
            <span class="account-badge account-badge-{{ $statusClass }}"><span class="photo-status-dot" aria-hidden="true"></span>{{ $statusLabel }}</span>
        </div>

        @if ($photo->is_primary)
            <span class="photo-primary-badge">Principal</span>
        @endif
        <div class="photo-tools"><button type="button" data-photo-drag aria-label="Arrastrar para ordenar fotografía">↕</button>
        <details class="photo-options"><summary aria-label="Opciones de fotografía {{ $position }}">⋯</summary><div class="photo-options-panel">
        @if ($hasApprovedCurrent)
            <form class="photo-primary-form" @if ($photo->is_primary) hidden @endif method="POST" action="{{ route('account.photos.primary', $photo) }}">
                @csrf
                <button class="photo-primary-action" type="submit">Usar como principal</button>
            </form>
        @endif

        @if ($status === 'rejected' && $statusVersion?->rejection_reason)
            <p class="photo-rejection"><strong>Motivo:</strong> {{ $statusVersion->rejection_reason }}</p>
        @elseif ($hasNewVersion && $status === 'pending')
            <p class="photo-card-help">La versión aprobada actual sigue vigente mientras revisamos esta nueva versión.</p>
        @elseif (! $photo->is_primary)
            <p class="photo-card-help">La revisión protege la calidad de tu perfil.</p>
        @endif

        <div class="photo-card-actions">

            <form class="photo-replace-form" method="POST" action="{{ route('account.photos.replace', $photo) }}" enctype="multipart/form-data" data-replace-form>
                @csrf
                <button type="button" class="photo-action photo-action-replace" onclick="this.form.querySelector('[data-replace-input]').click()">Reemplazar</button>
                <input id="replace-photo-{{ $photo->id }}" class="photo-file-input" type="file" name="photo" accept="image/jpeg,image/png,image/webp" data-replace-input>
            </form>
            <form class="photo-delete-form" method="POST" action="{{ route('account.photos.destroy', $photo) }}" data-delete-form>
                @csrf
                @method('DELETE')
                <button class="photo-action photo-action-delete" type="submit">Eliminar</button>
            </form>
        </div>

        <div class="photo-order-controls" aria-label="Cambiar orden de la foto">
            <button class="photo-order-button" type="button" data-order-move="up" aria-label="Mover foto hacia arriba">↑</button>
            <button class="photo-order-button" type="button" data-order-move="down" aria-label="Mover foto hacia abajo">↓</button>
        </div>
        </div></details></div>
    </div>
</article>
