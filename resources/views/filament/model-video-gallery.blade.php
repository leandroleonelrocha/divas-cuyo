<div x-data="{
    items: @js($items), index: @js($initialIndex), failed: false,
    move(step) {
        this.$refs.player.pause();
        this.index = (this.index + step + this.items.length) % this.items.length;
        this.failed = false;
        this.$nextTick(() => this.$refs.player.load());
    },
    destroy() { this.$refs.player?.pause(); }
}" @close-modal.window="$refs.player.pause()">
    <p aria-live="polite" style="margin-bottom:12px;overflow-wrap:anywhere">
        <strong x-text="items[index].name"></strong> · <span x-text="items[index].status"></span>
    </p>
    <video x-ref="player" controls playsinline preload="metadata"
        :src="items[index].url" :poster="items[index].poster" :aria-label="'Vista previa de ' + items[index].name"
        x-on:error="failed = true"
        style="width:100%;max-height:65dvh;background:#09090b;border-radius:12px">
        Tu navegador no permite reproducir este video.
    </video>
    <p x-show="failed" x-cloak role="alert">No se pudo reproducir el video. Revisá la conexión o la disponibilidad del archivo.</p>
    <div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-top:12px">
        <x-filament::button color="gray" type="button" @click="move(-1)" x-bind:disabled="items.length < 2" aria-label="Video anterior">← Anterior</x-filament::button>
        <span x-text="(index + 1) + ' de ' + items.length" aria-live="polite"></span>
        <x-filament::button color="gray" type="button" @click="move(1)" x-bind:disabled="items.length < 2" aria-label="Video siguiente">Siguiente →</x-filament::button>
    </div>
    <p style="margin-top:12px;font-size:13px">Cerrá el visor para aprobar o rechazar el video desde su fila.</p>
</div>
