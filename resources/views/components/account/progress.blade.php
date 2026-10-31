@props(['progressStages'])
<nav class="dashboard-panel publication-progress" aria-label="Etapas de tu publicación">
    <h2>Tu camino para publicar</h2>
    @php($currentStage = collect($progressStages)->search(fn ($step) => ! $step[2]))
    <ol>
        @foreach ($progressStages as [$stage, $state, $done])
            <li class="{{ $done ? 'stage-complete' : ($loop->index === $currentStage ? 'stage-current' : '') }}" @if ($loop->index === $currentStage) aria-current="step" @endif><span class="publication-step-icon {{ $done ? 'is-complete' : '' }}" aria-hidden="true">{{ $done ? '✓' : $loop->iteration }}</span><div><strong>{{ $stage }}</strong><span>{{ $state }}</span></div></li>
        @endforeach
    </ol>
</nav>
