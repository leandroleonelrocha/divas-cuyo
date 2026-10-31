@php($record = $this->getRecord())
<x-filament::section heading="Últimas moderaciones registradas">
    <dl class="grid gap-4 sm:grid-cols-2">
        <div><dt class="text-sm text-gray-500 dark:text-gray-400">Identidad</dt><dd>{{ match ($record->identity_status) { 'approved' => 'Aprobada', 'rejected' => 'Rechazada', 'pending' => 'Pendiente', default => 'Incompleta' } }} · {{ $record->identity_reviewed_at?->format('d/m/Y H:i') ?? 'Sin revisión' }}</dd>
            @if ($record->identity_rejection_reason)<p>{{ $record->identity_rejection_reason }}</p>@endif
        </div>
        <div><dt class="text-sm text-gray-500 dark:text-gray-400">Perfil</dt><dd>{{ match ($record->review_status) { 'approved' => 'Aprobado', 'rejected' => 'Rechazado', default => 'Pendiente' } }} · {{ $record->reviewed_at?->format('d/m/Y H:i') ?? 'Sin revisión' }}</dd><p>{{ $record->reviewer?->email }}</p></div>
    </dl>
    <p class="text-sm text-gray-500 dark:text-gray-400">Las revisiones de fotos, biografías y cambios físicos se conservan en sus tablas de Contenido.</p>
</x-filament::section>
