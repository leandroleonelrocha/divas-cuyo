@php
    $profile = $getRecord();
    $labels = ['dni_front' => 'DNI frente', 'dni_back' => 'DNI dorso', 'selfie' => 'Selfie'];
    $documents = collect(config('identity-documents.required_types'))->map(function ($type) use ($profile, $labels) {
        $document = $profile->documents->firstWhere('type', $type);
        return $document ? [
            'label' => $labels[$type] ?? $type,
            'url' => route('identity.documents.download', [$profile->user, $document, 'preview' => 1]),
            'download' => route('identity.documents.download', [$profile->user, $document]),
            'image' => str_starts_with($document->mime_type, 'image/'),
        ] : null;
    })->filter()->values();
@endphp
<div class="identity-gallery" x-data="{
    documents: @js($documents), index: 0,
    open(index) { this.index = index; this.$refs.viewer.showModal(); },
    move(direction) { this.index = (this.index + direction + this.documents.length) % this.documents.length; }
}">
    <style>
        .identity-gallery-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px }
        .identity-gallery-preview { width:100%; border:1px solid #6b728066; border-radius:12px; padding:12px; text-align:left }
        .identity-gallery-preview img { width:100%; height:150px; object-fit:contain; margin-bottom:10px }
        .identity-gallery-preview:focus-visible { outline:3px solid #ef4444; outline-offset:3px }
        .identity-viewer { margin:auto; width:min(1000px,94vw); max-height:92dvh; padding:16px; border:1px solid #6b7280; border-radius:16px; background:#fff; color:#111827 }
        .dark .identity-viewer { background:#18181b; color:#f4f4f5 }
        .identity-viewer::backdrop { background:rgb(0 0 0 / .8) }
        .identity-viewer header, .identity-viewer footer { display:flex; align-items:center; justify-content:space-between; gap:12px }
        .identity-viewer button, .identity-viewer a { min-height:44px; min-width:44px; padding:10px; cursor:pointer }
        .identity-viewer img { width:100%; height:65dvh; object-fit:contain }
        .identity-viewer iframe { width:100%; height:65dvh; border:0 }
    </style>
    <div class="identity-gallery-grid">
        @foreach ($documents as $index => $document)
            <button type="button" class="identity-gallery-preview" @click="open({{ $index }})" aria-label="Ampliar {{ $document['label'] }}" aria-haspopup="dialog">
                @if ($document['image'])
                    <img src="{{ $document['url'] }}" alt="{{ $document['label'] }}" loading="lazy">
                @else
                    <span>Documento PDF · Abrir vista previa</span><br>
                @endif
                <strong>{{ $document['label'] }}</strong>
            </button>
        @endforeach
    </div>
    @foreach (config('identity-documents.required_types') as $type)
        @unless ($profile->documents->contains('type', $type))
            <p>{{ $labels[$type] ?? $type }}: falta cargar este documento.</p>
        @endunless
    @endforeach
    <dialog x-ref="viewer" class="identity-viewer" aria-label="Documentos de identidad"
        @keydown.right.prevent="move(1)" @keydown.left.prevent="move(-1)"
        @click="if ($event.target === $refs.viewer) $refs.viewer.close()">
        <template x-if="documents.length">
            <div>
                <header>
                    <strong x-text="documents[index].label" aria-live="polite"></strong>
                    <button type="button" @click="$refs.viewer.close()" aria-label="Cerrar visor" autofocus>✕</button>
                </header>
                <template x-if="documents[index].image">
                    <img :src="documents[index].url" :alt="documents[index].label">
                </template>
                <template x-if="!documents[index].image">
                    <iframe :src="documents[index].url" :title="documents[index].label"></iframe>
                </template>
                <footer>
                    <button type="button" @click="move(-1)" :disabled="documents.length < 2" aria-label="Documento anterior">←</button>
                    <span x-text="(index + 1) + ' de ' + documents.length" aria-live="polite"></span>
                    <a :href="documents[index].download">Descargar</a>
                    <button type="button" @click="move(1)" :disabled="documents.length < 2" aria-label="Documento siguiente">→</button>
                </footer>
            </div>
        </template>
    </dialog>
</div>
