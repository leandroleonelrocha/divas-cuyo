@props(['primaryPhoto', 'photos', 'online' => false, 'stageName' => ''])

<section class="account-card public-model-gallery" aria-labelledby="gallery-title">
    <div class="dv-viewer" data-gallery-viewer>
        @if ($online)
            <span class="dv-online-badge"><span class="dv-dot" aria-hidden="true"></span>En línea</span>
        @endif
        <a class="photo-preview profile-main-photo" href="{{ $primaryPhoto['url'] }}" target="_blank" rel="noopener" aria-label="Ampliar fotografía" data-gallery-link>
            <img src="{{ $primaryPhoto['url'] }}" alt="{{ $primaryPhoto['alt'] }}"
                 width="{{ $primaryPhoto['width'] }}" height="{{ $primaryPhoto['height'] }}" loading="eager" fetchpriority="high" data-gallery-main>
        </a>
        @if (count($photos) > 1)
            <button type="button" class="dv-arrow dv-arrow-prev" aria-label="Fotografía anterior" data-gallery-prev><span aria-hidden="true">‹</span></button>
            <button type="button" class="dv-arrow dv-arrow-next" aria-label="Fotografía siguiente" data-gallery-next><span aria-hidden="true">›</span></button>
        @endif
        <span class="dv-counter" aria-live="polite"><span data-gallery-index>1</span> / {{ count($photos) }}</span>
    </div>
    <div class="dv-gallery-head">
        <h2 id="gallery-title">Galería de {{ $stageName !== '' ? $stageName : 'fotografías' }}</h2>
        <span class="dv-gallery-total">Ver todas las fotos ({{ count($photos) }})</span>
    </div>
    <p class="gallery-instruction">Seleccioná una miniatura para verla. Tocá la foto principal para ampliarla.</p>
    <ul class="photos-grid" aria-label="Galería de fotografías">
        @foreach ($photos as $photo)
            <li class="photo-card">
                <button type="button" class="photo-preview profile-thumbnail" data-gallery-thumb="{{ $photo['url'] }}" aria-label="Ver fotografía {{ $loop->iteration }}" aria-pressed="{{ $photo['url'] === $primaryPhoto['url'] ? 'true' : 'false' }}">
                    <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}"
                         width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                         loading="{{ $photo['url'] === $primaryPhoto['url'] ? 'eager' : 'lazy' }}">
                </button>
            </li>
        @endforeach
    </ul>
</section>
