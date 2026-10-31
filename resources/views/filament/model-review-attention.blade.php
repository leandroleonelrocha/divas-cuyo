@php
    $record = $this->getRecord();
    $tasks = $this->reviewTasks();
    $total = array_sum(array_column($tasks, 'count'));
    $identityLabel = match ($record->identity_status) {
        'approved' => 'Aprobada', 'pending' => 'En revisión', 'rejected' => 'Rechazada', default => 'Incompleta',
    };
    $taskUrl = fn ($task) => \App\Filament\Resources\ModelProfiles\ModelProfileResource::getUrl('view', [
        'record' => $record, 'relation' => $task['relation'],
    ]).($task['anchor'] ? '#'.$task['anchor'] : '');
@endphp
<div class="flex flex-wrap items-center gap-2">
    <x-filament::badge :color="$record->identity_status === 'approved' ? 'success' : 'gray'">Identidad: {{ $identityLabel }}</x-filament::badge>
    <x-filament::badge :color="$record->is_published ? 'success' : 'gray'">{{ $record->is_published ? 'Publicada' : 'No publicada' }}</x-filament::badge>
    @if (!$tasks)<span class="text-sm text-gray-500 dark:text-gray-400">No hay tareas de revisión pendientes.</span>@endif
</div>
@if ($tasks)
    <div class="flex flex-wrap items-center gap-3 rounded-lg bg-amber-50 p-3 dark:bg-amber-950" role="status" aria-label="Requiere tu atención">
        <strong>{{ $total }} {{ $total === 1 ? 'pendiente' : 'pendientes' }}</strong>
        <span class="text-sm">{{ collect($tasks)->map(fn ($task) => $task['count'].' '.$task['label'])->join(' · ') }}</span>
        <x-filament::button tag="a" size="sm" color="warning" :href="$taskUrl($tasks[0])">Revisar</x-filament::button>
        @if (count($tasks) > 1)
            <details>
                <summary class="cursor-pointer text-sm">Otros pendientes</summary>
                <ul>
                    @foreach (array_slice($tasks, 1) as $task)
                        <li><a class="underline" href="{{ $taskUrl($task) }}">{{ $task['label'] }} · {{ $task['count'] }}</a></li>
                    @endforeach
                </ul>
            </details>
        @endif
    </div>
@endif
