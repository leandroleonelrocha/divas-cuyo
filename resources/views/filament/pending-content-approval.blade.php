@php
    $pending = $this->reviewTasks();
    $contentTasks = array_filter($pending, fn ($task) => (string) $task['relation'] === '0');
@endphp
@if ($contentTasks)
    <x-filament::section heading="Contenido pendiente">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <p>{{ collect($contentTasks)->map(fn ($task) => $task['count'].' '.$task['label'])->join(' · ') }}</p>
            <p class="text-sm text-gray-500">Para aprobar en conjunto, abrí Moderación → Aprobar pendientes.</p>
        </div>
    </x-filament::section>
@endif
@if ($this->pendingApprovalResults)
    <x-filament::section heading="Resultado de la aprobación">
        <ul role="status" class="space-y-2">
            @foreach ($this->pendingApprovalResults as $result)
                <li><x-filament::badge :color="$result['approved'] ? 'success' : 'warning'">{{ $result['label'] }}</x-filament::badge> {{ $result['message'] }}</li>
            @endforeach
        </ul>
    </x-filament::section>
@endif
