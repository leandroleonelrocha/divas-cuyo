<div x-data="{ images: @js($images), index: @js($initialIndex), move(step) { this.index = (this.index + step + this.images.length) % this.images.length; } }"
    @keydown.right.prevent.stop="move(1)" @keydown.left.prevent.stop="move(-1)">
    @if ($images !== [])
        <div style="position:relative; background:#09090b; border-radius:12px; overflow:hidden">
            <img :src="images[index].url" :alt="images[index].label"
                style="width:100%; height:65dvh; object-fit:contain">
            @if (count($images) > 1)
                <button type="button" @click="move(-1)" aria-label="Fotografía anterior"
                    style="position:absolute; left:8px; top:calc(50% - 24px); width:48px; height:48px; border-radius:50%; background:#fff; color:#18181b; font-size:26px; cursor:pointer">←</button>
                <button type="button" @click="move(1)" aria-label="Fotografía siguiente"
                    style="position:absolute; right:8px; top:calc(50% - 24px); width:48px; height:48px; border-radius:50%; background:#fff; color:#18181b; font-size:26px; cursor:pointer">→</button>
            @endif
        </div>
        <p aria-live="polite" style="margin-top:12px; text-align:center">
            <span x-text="images[index].label"></span>
            · <span x-text="(index + 1) + ' de ' + images.length"></span>
        </p>
    @else
        <p>No hay fotografías disponibles.</p>
    @endif
</div>
