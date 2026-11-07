<div class="space-y-3">
    @forelse ($this->pendingApprovalItems as $item)
        <details class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
            <summary style="min-height:44px;cursor:pointer">{{ $item['label'] }} · Ver contenido</summary>
            @if ($item['type'] === 'photo')
                <a href="{{ $item['url'] }}" target="_blank" rel="noopener"><img src="{{ $item['url'] }}" alt="{{ $item['label'] }}" loading="lazy" style="max-height:320px;max-width:100%;object-fit:contain"></a>
            @elseif ($item['type'] === 'video')
                <video controls playsinline preload="none" src="{{ $item['url'] }}" aria-label="{{ $item['label'] }}"
                    x-data @close-modal.window="$el.pause()" style="width:100%;max-height:320px"></video>
            @else
                <p style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $item['text'] }}</p>
            @endif
        </details>
    @empty
        <p>No hay contenidos elegibles. Es posible que ya hayan sido revisados.</p>
    @endforelse
</div>
