@props([
    'type' => 'info',
    'title' => null,
    'message' => null,
    'variant' => 'inline',
    'dismissible' => true,
    'role' => null,
    'timeout' => 6000,
])

@php
    $type = in_array($type, ['success', 'error', 'warning', 'info'], true) ? $type : 'info';
    $role = $role ?? ($type === 'error' ? 'alert' : 'status');
    $live = $type === 'error' ? 'assertive' : 'polite';
    $key = $type.'|'.($title ?? '').'|'.($message ?? '');
    $icons = [
        'success' => '<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M7.6 12.4l2.9 2.9 5.9-6.4" fill="none" stroke="#fff" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'error' => '<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M8.6 8.6l6.8 6.8M15.4 8.6l-6.8 6.8" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/></svg>',
        'warning' => '<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><path d="M12 3.6l8.7 15.6H3.3L12 3.6z" fill="currentColor"/><path d="M12 9.8v4.1" stroke="#fff" stroke-width="2" stroke-linecap="round"/><circle cx="12" cy="16.5" r="1.2" fill="#fff"/></svg>',
        'info' => '<svg viewBox="0 0 24 24" width="20" height="20" focusable="false"><circle cx="12" cy="12" r="10" fill="currentColor"/><path d="M12 10.9v5.3" stroke="#fff" stroke-width="2.2" stroke-linecap="round"/><circle cx="12" cy="7.9" r="1.3" fill="#fff"/></svg>',
    ];
@endphp

<div {{ $attributes->class(['notify', 'notify--'.$type, 'notify--'.$variant]) }}
     data-notify
     data-notify-variant="{{ $variant }}"
     data-notify-key="{{ $key }}"
     @if ($variant === 'toast') data-notify-timeout="{{ (int) $timeout }}" @endif
     role="{{ $role }}"
     aria-live="{{ $live }}"
     aria-atomic="true">
    <span class="notify__icon" aria-hidden="true">{!! $icons[$type] !!}</span>
    <div class="notify__body">
        @if ($title)
            <p class="notify__title">{{ $title }}</p>
        @endif
        <div class="notify__text">
            @if ($message)
                <p>{{ $message }}</p>
            @endif
            {{ $slot }}
        </div>
    </div>
    @if ($dismissible)
        <button type="button" class="notify__close" data-notify-close aria-label="Cerrar notificación">
            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true" focusable="false"><path d="M6 6l12 12M18 6L6 18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
        </button>
    @endif
</div>
