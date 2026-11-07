<div class="profile-stats">
    <style>
        .profile-stats { display:grid; gap:16px; min-width:0; }
        .profile-stats-totals { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:10px; }
        .profile-stat-box { border:1px solid #e5e7eb; border-radius:12px; padding:16px; min-width:0; }
        .profile-stat-box strong { display:block; font-size:24px; }
        .profile-stat-box span { font-size:13px; }
        .profile-stats-chart { display:grid; gap:6px; }
        .profile-stats-day { display:grid; grid-template-columns:44px minmax(0,1fr) 65px; align-items:center; gap:8px; font-size:12px; }
        .profile-stats-bars { display:grid; gap:2px; }
        .profile-stats-bar { height:7px; background:#c8102e; border-radius:4px; }
        .profile-stats-bar.clicks { background:#15803d; }
        .dark .profile-stat-box { border-color:#3f3f46; }
        @media(max-width:500px) { .profile-stat-box { padding:10px; } .profile-stat-box strong { font-size:20px; } }
    </style>
    <p>{{ $stats['range'] }} · Horario de Argentina</p>
    <div class="profile-stats-totals">
        <div class="profile-stat-box"><strong>{{ number_format($stats['visits'], 0, ',', '.') }}</strong><span>Visitas al perfil</span></div>
        <div class="profile-stat-box"><strong>{{ number_format($stats['clicks'], 0, ',', '.') }}</strong><span>Clics en WhatsApp</span></div>
        <div class="profile-stat-box"><strong>{{ $stats['rate'] === null ? '—' : number_format($stats['rate'], 1, ',', '.').'%' }}</strong><span>Clics / visitas</span></div>
    </div>
    @if (!$stats['visits'] && !$stats['clicks'])
        <p>Todavía no registramos actividad en este período.</p>
    @else
        <h2>Evolución diaria</h2>
        <p>Rojo: visitas · Verde: clics en WhatsApp</p>
        <div class="profile-stats-chart" role="list" aria-label="Visitas y clics por día">
            @foreach ($stats['days'] as $day)
                <div class="profile-stats-day" role="listitem" aria-label="{{ $day['date'] }}: {{ $day['visits'] }} visitas y {{ $day['clicks'] }} clics">
                    <span>{{ $day['date'] }}</span>
                    <div class="profile-stats-bars" aria-hidden="true">
                        <div class="profile-stats-bar" style="width:{{ $day['visits'] / $stats['max'] * 100 }}%"></div>
                        <div class="profile-stats-bar clicks" style="width:{{ $day['clicks'] / $stats['max'] * 100 }}%"></div>
                    </div>
                    <span aria-hidden="true">{{ $day['visits'] }} / {{ $day['clicks'] }}</span>
                </div>
            @endforeach
        </div>
    @endif
    <p class="profile-help">Visitas y clics deduplicados por sesión durante 30 minutos; no representan personas únicas. Los clics no confirman mensajes enviados ni contrataciones. Solo se registra actividad desde la implementación; los bloqueadores y navegadores sin JavaScript pueden impedir el registro.</p>
</div>
