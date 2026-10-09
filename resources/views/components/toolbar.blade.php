@php
    $toolbarProvinces = \App\Models\Province::query()->active()->orderBy('name')->get(['slug', 'name']);
    $toolbarProvinceSlug = request()->query('provincia', '');
    $toolbarCategories = [
        'escorts' => 'Escorts',
        'virtual' => 'Virtual',
        'masajistas' => 'Masajistas',
        'trans' => 'Trans',
        'bdsm' => 'BDSM',
    ];
    $toolbarCategory = request()->query('categoria', '');
    if (! array_key_exists($toolbarCategory, $toolbarCategories)) {
        $toolbarCategory = '';
    }
    $toolbarBase = static function (array $params) {
        $query = array_filter(array_merge(request()->query(), $params), fn ($v) => $v !== '' && $v !== null);
        return route('home').($query ? '?'.http_build_query($query) : '');
    };
@endphp

<div class="dc-toolbar">
    <div class="dc-toolbar-inner">
        <div class="dc-toolbar-row" role="toolbar" aria-label="Accesos y filtros">
            <div class="dc-toolbar-group" aria-label="Ubicación y acciones">
                <span class="dc-toolbar-loc">
                    <span aria-hidden="true">📍</span>
                    <label for="dc-toolbar-province" class="sr-only" style="position:absolute;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);">Ubicación</label>
                    <select id="dc-toolbar-province" aria-label="Ubicación" onchange="var u=this.getAttribute('data-home-url');window.location.href=this.value?u+'?provincia='+encodeURIComponent(this.value):u;" data-home-url="{{ route('home') }}">
                        <option value="">Argentina</option>
                        @foreach ($toolbarProvinces as $toolbarProvinceItem)
                            <option value="{{ $toolbarProvinceItem->slug }}" {{ $toolbarProvinceSlug === $toolbarProvinceItem->slug ? 'selected' : '' }}>{{ $toolbarProvinceItem->name }}</option>
                        @endforeach
                    </select>
                </span>
                <span class="dc-toolbar-divider" aria-hidden="true"></span>
                <button type="button" class="dc-toolbar-icon" aria-label="Mostrar filtros" aria-expanded="false" aria-controls="toolbar-filters" onclick="var f=document.getElementById('toolbar-filters');var open=f.hasAttribute('hidden');if(open){f.removeAttribute('hidden');}else{f.setAttribute('hidden','');}this.setAttribute('aria-expanded',open?'true':'false');"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18l-7 8v5l-4 2v-7L3 5z"/></svg></button>
                <a class="dc-toolbar-icon" href="#videos" aria-label="Buscar y ver perfiles"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="10" cy="8" r="4"/><path d="M2.5 20c0-3.8 3.4-5.7 7.5-5.7s7.5 1.9 7.5 5.7"/><path d="M16 16l4.5 4.5"/></svg></a>
            </div>
            <a class="dc-toolbar-live" href="#live">◉ &nbsp;LIVE SEX</a>
        </div>
        <nav class="dc-toolbar-cats" aria-label="Categorías">
            @foreach ($toolbarCategories as $toolbarSlug => $toolbarLabel)
                <a class="dc-toolbar-cat {{ $toolbarCategory === $toolbarSlug ? 'active' : '' }}" @if($toolbarCategory === $toolbarSlug) aria-current="true" @endif href="{{ $toolbarBase(['categoria' => $toolbarCategory === $toolbarSlug ? '' : $toolbarSlug]) }}">{{ $toolbarLabel }}</a>
            @endforeach
        </nav>
    </div>
</div>
