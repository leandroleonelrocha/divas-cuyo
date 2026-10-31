@props(['title', 'description', 'status', 'href', 'action', 'number', 'color' => 'muted', 'attention' => false, 'reason' => null])
<li class="{{ $attention ? 'task-needs-attention' : '' }}">
    <span class="profile-setup-number" aria-hidden="true">{{ $number }}</span>
    <div class="profile-setup-copy">
        <h3>{{ $title }}</h3><p>{{ $description }}</p>
        <span class="account-badge account-badge-{{ $color }}">{{ $status }}</span>
        {{ $slot }}
        @if ($reason)<p class="profile-setup-feedback">Motivo: {{ $reason }}</p>@endif
    </div>
    <a href="{{ $href }}">{{ $action }} <span aria-hidden="true">→</span></a>
</li>
