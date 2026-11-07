<div class="space-y-3">
    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-600 dark:text-gray-300">
        <span @class([
            'rounded-full px-2.5 py-0.5 text-xs font-semibold',
            'bg-warning-500/10 text-warning-600 dark:text-warning-400' => $bio->status === 'pending',
            'bg-success-500/10 text-success-600 dark:text-success-400' => $bio->status === 'approved',
            'bg-danger-500/10 text-danger-600 dark:text-danger-400' => $bio->status === 'rejected',
        ])>
            {{ match ($bio->status) { 'approved' => 'Aprobada', 'rejected' => 'Rechazada', default => 'Pendiente' } }}
        </span>
        <span>Enviada el {{ $bio->created_at->format('d/m/Y H:i') }}</span>
        @if ($bio->reviewed_at)
            <span>Revisada el {{ $bio->reviewed_at->format('d/m/Y H:i') }}</span>
        @endif
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-4 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-100" style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $bio->content }}</div>

    @if ($bio->status === 'rejected' && filled($bio->rejection_reason))
        <div class="rounded-lg border border-danger-200 bg-danger-50 p-3 text-sm text-danger-700 dark:border-danger-600 dark:bg-danger-950/40 dark:text-danger-400">
            <strong>Motivo del rechazo:</strong> <span style="white-space:pre-wrap;overflow-wrap:anywhere">{{ $bio->rejection_reason }}</span>
        </div>
    @endif

    @if ($bio->status === 'approved' && $bio->reviewedBy)
        <p class="text-xs text-gray-500 dark:text-gray-400">Aprobada por {{ $bio->reviewedBy->email }}</p>
    @endif
</div>
