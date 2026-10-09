@props(['primaryPhoto', 'photos'])

<section class="account-card public-model-gallery" aria-labelledby="gallery-title">
    <h2 id="gallery-title">Fotografías</h2>
    <div class="photos-layout">
        <div class="photo-card">
            <a class="photo-preview profile-main-photo" href="{{ $primaryPhoto['url'] }}" target="_blank" rel="noopener" aria-label="Ampliar fotografía">
                <img src="{{ $primaryPhoto['url'] }}" alt="{{ $primaryPhoto['alt'] }}"
                     width="{{ $primaryPhoto['width'] }}" height="{{ $primaryPhoto['height'] }}" loading="eager" fetchpriority="high">
            </a>
        </div>
        <p class="gallery-instruction">Seleccioná una miniatura para verla. Tocá la foto principal para ampliarla.</p>
        <ul class="photos-grid" aria-label="Galería de fotografías">
            @foreach ($photos as $photo)
                <li class="photo-card">
                    <button type="button" class="photo-preview profile-thumbnail" aria-label="Ver fotografía {{ $loop->iteration }}" aria-pressed="{{ $photo['url'] === $primaryPhoto['url'] ? 'true' : 'false' }}">
                        <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}"
                             width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                             loading="{{ $photo['url'] === $primaryPhoto['url'] ? 'eager' : 'lazy' }}">
                    </button>
                </li>
            @endforeach
        </ul>
    </div>
</section>
