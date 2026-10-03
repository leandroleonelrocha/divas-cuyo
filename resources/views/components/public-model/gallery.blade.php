@props(['primaryPhoto', 'photos'])

<section class="account-card public-model-gallery" aria-labelledby="gallery-title">
    <h2 id="gallery-title">Fotografías</h2>
    <div class="photos-layout">
        <div class="photo-card">
            <div class="photo-preview">
                <img src="{{ $primaryPhoto['url'] }}" alt="{{ $primaryPhoto['alt'] }}"
                     width="{{ $primaryPhoto['width'] }}" height="{{ $primaryPhoto['height'] }}" loading="eager" fetchpriority="high">
            </div>
        </div>
        <ul class="photos-grid" aria-label="Galería de fotografías">
            @foreach ($photos as $photo)
                <li class="photo-card">
                    <div class="photo-preview">
                        <img src="{{ $photo['url'] }}" alt="{{ $photo['alt'] }}"
                             width="{{ $photo['width'] }}" height="{{ $photo['height'] }}"
                             loading="{{ $photo['url'] === $primaryPhoto['url'] ? 'eager' : 'lazy' }}">
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</section>
