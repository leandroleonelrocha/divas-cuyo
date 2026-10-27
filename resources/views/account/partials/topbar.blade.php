<header class="account-dashboard-topbar">
    <div class="account-notif" data-notif data-read-url="{{ route('account.notifications.read') }}" data-csrf="{{ csrf_token() }}">
        <button type="button" class="account-notif-toggle" data-notif-toggle aria-haspopup="dialog" aria-expanded="false" aria-label="Notificaciones{{ $unreadNotifications > 0 ? ' ('.$unreadNotifications.' sin leer)' : '' }}">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M18 8a6 6 0 1 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"></path>
                <path d="M13.7 21a2 2 0 0 1-3.4 0"></path>
            </svg>
            @if ($unreadNotifications > 0)
                <span class="account-notif-badge" data-notif-badge>{{ $unreadNotifications > 99 ? '99+' : $unreadNotifications }}</span>
            @endif
        </button>
        <div class="account-notif-panel" data-notif-panel hidden aria-label="Notificaciones">
            <div class="account-notif-head">
                <strong>Notificaciones</strong>
                @if ($unreadNotifications > 0)
                    <span class="account-notif-new" data-notif-new>{{ $unreadNotifications }} nueva{{ $unreadNotifications === 1 ? '' : 's' }}</span>
                @endif
            </div>
            @if ($notifications->isEmpty())
                <p class="account-notif-empty">Todavía no hay notificaciones. Acá van a aparecer las aprobaciones y revisiones de tu perfil.</p>
            @else
                <ul class="account-notif-list">
                    @foreach ($notifications as $notification)
                        <li class="account-notif-item{{ $notification->read_at ? '' : ' is-unread' }}" data-notif-item>
                            <span class="account-notif-icon account-notif-icon--{{ $notification->type->isRejection() ? 'error' : 'success' }}" aria-hidden="true">{{ $notification->type->isRejection() ? '✕' : '✓' }}</span>
                            <div class="account-notif-body">
                                <strong>{{ $notification->type->label() }}</strong>
                                <p>{{ $notification->type->message($notification->detail) }}</p>
                                <time datetime="{{ $notification->created_at->toIso8601String() }}">{{ $notification->created_at->diffForHumans() }}</time>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
    <span class="account-avatar">{{ mb_strtoupper(mb_substr($label, 0, 1)) }}</span>
    <div class="account-topbar-menu" data-topbar-menu>
        <button type="button" class="account-topbar-toggle" data-topbar-toggle aria-haspopup="menu" aria-expanded="false">
            <strong>{{ $label }}</strong><span class="account-topbar-caret" aria-hidden="true">⌄</span>
        </button>
        <div class="account-topbar-dropdown" data-topbar-dropdown role="menu" hidden>
            <a role="menuitem" href="{{ route('account.dashboard') }}">Mi perfil</a>
            <form method="POST" action="{{ route('logout') }}">@csrf<button type="submit" role="menuitem">Cerrar sesión</button></form>
        </div>
    </div>
</header>
<script defer>
(() => {
    const menus = [...document.querySelectorAll('[data-topbar-menu]')];

    const closeMenu = (menu) => {
        const dropdown = menu.querySelector('[data-topbar-dropdown]');
        const toggle = menu.querySelector('[data-topbar-toggle]');
        if (!dropdown || !toggle) return;
        dropdown.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
        menu.classList.remove('is-open');
    };

    menus.forEach((menu) => {
        const toggle = menu.querySelector('[data-topbar-toggle]');
        const dropdown = menu.querySelector('[data-topbar-dropdown]');
        if (!toggle || !dropdown) return;

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = dropdown.hidden;
            menus.forEach(closeMenu);
            dropdown.hidden = !willOpen;
            toggle.setAttribute('aria-expanded', String(willOpen));
            menu.classList.toggle('is-open', willOpen);
        });

        document.addEventListener('click', (event) => {
            if (!menu.contains(event.target)) closeMenu(menu);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMenu(menu);
                toggle.focus();
            }
        });
    });
})();
</script>
<script defer>
(() => {
    const roots = [...document.querySelectorAll('[data-notif]')];

    const closePanel = (root) => {
        const panel = root.querySelector('[data-notif-panel]');
        const toggle = root.querySelector('[data-notif-toggle]');
        if (!panel || !toggle) return;
        panel.hidden = true;
        toggle.setAttribute('aria-expanded', 'false');
    };

    const markAllRead = (root) => {
        if (!root.querySelector('[data-notif-badge]')) return;

        root.querySelectorAll('[data-notif-item]').forEach((item) => item.classList.remove('is-unread'));
        root.querySelector('[data-notif-badge]')?.remove();
        root.querySelector('[data-notif-new]')?.remove();

        fetch(root.dataset.readUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': root.dataset.csrf,
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).catch(() => {});
    };

    roots.forEach((root) => {
        const toggle = root.querySelector('[data-notif-toggle]');
        const panel = root.querySelector('[data-notif-panel]');
        if (!toggle || !panel) return;

        toggle.addEventListener('click', (event) => {
            event.stopPropagation();
            const willOpen = panel.hidden;
            closePanel(root);
            if (willOpen) {
                panel.hidden = false;
                toggle.setAttribute('aria-expanded', 'true');
                markAllRead(root);
            }
        });

        document.addEventListener('click', (event) => {
            if (!root.contains(event.target)) closePanel(root);
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                const wasOpen = !panel.hidden;
                closePanel(root);
                if (wasOpen) toggle.focus();
            }
        });
    });
})();
</script>
