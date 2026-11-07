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

@php
    $steps = app(\App\Services\AdminProfileProcess::class)->steps($record);
    $priority = $tasks[0] ?? null;
    $priorityLabel = $priority ? match ((string) $priority['relation']) {
        'identity' => 'Revisar identidad', '0' => 'Revisar contenido', default => 'Revisar inscripción',
    } : null;
@endphp
<section class="admin-process-strip" aria-labelledby="admin-process-heading" wire:poll.15s="$refresh">
    <style>
        .model-detail-tabs > .fi-tabs { margin-inline:0; align-self:flex-start; max-width:100%; }
        .admin-process-strip { padding:14px 16px; border:1px solid #e5e7eb; border-radius:14px; background:#fff; box-shadow:0 1px 3px #0001; }
        .dark .admin-process-strip { background:#18181b; border-color:#3f3f46; }
        .admin-process-strip h2 { font-size:15px; font-weight:700; margin-bottom:10px; }
        .admin-process-track { display:flex; flex-wrap:wrap; align-items:center; gap:12px; }
        .admin-process-steps { display:flex; flex:1; align-items:center; gap:8px; min-width:0; }
        .admin-process-step { display:flex; align-items:center; gap:8px; padding:8px 10px; border-radius:12px; background:#f3f4f6; color:#4b5563; text-decoration:none; min-height:44px; }
        .admin-process-step strong { display:block; font-size:13px; }
        .admin-process-step small { display:block; font-size:11px; line-height:1.3; }
        .admin-process-step[data-state="success"] { background:#f0fdf4; color:#166534; }
        .admin-process-step[data-state="warning"] { background:#fffbeb; color:#92400e; }
        .admin-process-step[data-state="danger"] { background:#fef2f2; color:#991b1b; }
        .admin-process-step[data-state="info"] { background:#eff6ff; color:#1e40af; }
        .dark .admin-process-step { background:#27272a; color:#d4d4d8; }
        .dark .admin-process-step[data-state="success"] { background:#052e16; color:#86efac; }
        .dark .admin-process-step[data-state="warning"] { background:#451a03; color:#fcd34d; }
        .dark .admin-process-step[data-state="danger"] { background:#450a0a; color:#fca5a5; }
        .dark .admin-process-step[data-state="info"] { background:#172554; color:#93c5fd; }
        .admin-process-symbol { display:grid; place-items:center; width:22px; height:22px; border:1px solid currentColor; border-radius:50%; flex-shrink:0; font-size:13px; }
        .admin-process-line { height:1px; min-width:12px; flex:1; background:#9ca3af; }
        .admin-process-step:focus-visible { outline:2px solid #f59e0b; outline-offset:3px; }
        @media(max-width:1100px) { .admin-process-steps { flex-wrap:wrap; } .admin-process-line { display:none; } }
        @media(max-width:640px) { .admin-process-steps { display:grid; grid-template-columns:1fr 1fr; width:100%; flex-basis:100%; } .admin-process-step { min-width:0; overflow-wrap:anywhere; } }
    </style>
    <h2 id="admin-process-heading">Estado del proceso</h2>
    <div class="admin-process-track">
        <div class="admin-process-steps">
            @foreach ($steps as $step)
                <a class="admin-process-step" data-state="{{ $step['state'] }}"
                    href="{{ \App\Filament\Resources\ModelProfiles\ModelProfileResource::getUrl('view', ['record' => $record, 'relation' => $step['relation']]) }}"
                    aria-label="{{ $step['title'] }}: {{ $step['detail'] }}">
                    <span class="admin-process-symbol" aria-hidden="true">{{ match ($step['state']) { 'success' => '✓', 'warning' => '◷', 'danger' => '!', default => $loop->iteration } }}</span>
                    <span><strong>{{ $step['title'] }}</strong><small>{{ $step['detail'] }}</small></span>
                </a>
                @unless ($loop->last)<span class="admin-process-line" aria-hidden="true"></span>@endunless
            @endforeach
        </div>
        @if ($priority)
            <x-filament::button tag="a" color="warning" :href="$taskUrl($priority)" aria-label="Requiere tu atención">
                {{ $priorityLabel }}
            </x-filament::button>
        @endif
    </div>
</section>
