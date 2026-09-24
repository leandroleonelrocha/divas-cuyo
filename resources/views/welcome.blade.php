<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Divas Cuyo — Versión 3</title>
  <style>
    :root { --bg:#f7f7f8; --panel:#ffffff; --panel-2:#edf2fa; --text:#111827; --muted:#667085; --line:#d9dce1; --red:#d71920; --red-dark:#b9141a; --red-soft:#fdebec; --header:#2b2b31; --font:"DM Sans","Helvetica Neue",Arial,sans-serif; }
    * { box-sizing:border-box; }
    html { scroll-behavior:smooth; }
    body { margin:0; background:var(--bg); color:var(--text); font-family:var(--font); }
    a { color:inherit; text-decoration:none; }
    a:hover { color:var(--red-dark); }
    .topbar { height:7px; background:var(--header); }
    .shell, .header-inner { width:min(1320px, calc(100% - 64px)); margin:auto; }
    header { position:relative; z-index:20; width:100%; height:108px; background:var(--header); }
    .header-inner { position:relative; z-index:21; display:flex; align-items:center; height:100%; }
    .site-logo { display:flex; flex:0 0 auto; align-items:center; gap:10px; max-width:40%; height:56px; margin-right:auto; overflow:hidden; line-height:1; }
    .site-logo img { display:block; width:76px; height:56px; object-fit:contain; }
    .site-logo span { color:#fff; font-size:20px; font-weight:800; letter-spacing:.04em; white-space:nowrap; }
    .main-nav { display:flex; align-items:center; gap:42px; color:#fff; font-size:17px; font-weight:600; }
    .nav-item { display:flex; align-items:center; gap:11px; }
    .nav-icon { color:var(--red); font-size:25px; }
    .create { padding:13px 22px; color:#fff; background:var(--red); border-radius:13px; }
    .create:hover { color:#fff; background:var(--red-dark); }
    .user-menu { position:relative; }
    .user-menu summary { display:flex; align-items:center; gap:9px; color:#fff; cursor:pointer; list-style:none; }
    .user-menu summary::-webkit-details-marker { display:none; }
    .user-avatar { display:grid; place-items:center; width:38px; height:38px; overflow:hidden; color:#fff; background:var(--red); border:2px solid rgba(255,255,255,.75); border-radius:50%; font-size:14px; font-weight:800; }
    .user-avatar img { width:100%; height:100%; object-fit:cover; }
    .user-menu-panel { position:absolute; top:calc(100% + 12px); right:0; z-index:10; display:grid; min-width:180px; gap:4px; padding:8px; color:var(--text); background:var(--panel); border:1px solid var(--line); border-radius:10px; box-shadow:0 12px 28px rgba(17,24,39,.18); }
    .user-menu-panel a, .user-menu-panel button { width:100%; padding:10px 12px; color:var(--text); background:transparent; border:0; border-radius:7px; cursor:pointer; font-size:14px; text-align:left; }
    .user-menu-panel a:hover, .user-menu-panel button:hover { color:var(--red-dark); background:var(--red-soft); }
    .star { font-size:26px; }
    .hamburger { font-size:27px; }
    .intro { display:flex; align-items:end; justify-content:space-between; padding:22px 0 16px; }
    h1 { margin:0; font-size:28px; letter-spacing:-.5px; }
    .live { padding:10px 18px; color:var(--red); border:1px solid var(--line); border-radius:12px; font-weight:700; }
    .live:hover { color:var(--red-dark); background:var(--red-soft); }
    .location { display:flex; align-items:center; gap:18px; margin-bottom:17px; color:var(--muted); font-size:18px; }
    .location strong { color:var(--text); }
    .location button { width:44px; height:44px; color:var(--muted); background:#f1f2f4; border:0; border-radius:50%; }
    .filters { display:flex; gap:8px; margin-bottom:12px; }
    .filter { padding:12px 20px; color:var(--red-dark); background:var(--panel-2); border-radius:12px; font-size:16px; font-weight:600; }
    .category-row { display:flex; align-items:center; gap:25px; padding:0 0 12px; color:var(--red); font-size:24px; }
    .category-row .selected { padding:10px 22px; color:var(--text); background:#f1f2f4; border-radius:11px; }
    .category-row span { font-size:14px; color:var(--muted); }
    .seo-links { padding:0 0 17px 80px; color:var(--muted); font-size:16px; line-height:1.65; }
    .seo-links a:not(:last-child)::after { content:" · "; color:var(--muted); }
    .group { margin:0 auto 36px; text-align:center; }
    .group p { margin:0 0 6px; font-weight:600; }
    .group a { color:var(--red); font-size:23px; }
    .avatar-scroller { display:flex; gap:18px; overflow-x:auto; padding:0 0 27px; scrollbar-color:var(--header) var(--bg); }
    .avatar-card { flex:0 0 112px; text-align:center; }
    .avatar-card img { display:block; width:112px; height:112px; object-fit:cover; border:4px solid #d9dce1; border-radius:50%; }
    .avatar-card span { display:block; overflow:hidden; margin-top:12px; color:var(--muted); font-size:16px; text-overflow:ellipsis; white-space:nowrap; }
    .section-title { margin:2px 0 14px; font-size:18px; }
    .section-title em { color:var(--red); font-style:normal; }
    .card-grid { display:grid; grid-template-columns:repeat(6, 1fr); gap:9px; padding-bottom:70px; }
    .profile-card { position:relative; overflow:hidden; height:330px; background:var(--panel); border-radius:11px; }
    .profile-card img { width:100%; height:100%; display:block; object-fit:cover; transition:transform .35s ease; }
    .profile-card:hover img { transform:scale(1.04); }
    .virtual { position:absolute; top:10px; right:8px; padding:6px 9px; color:#fff; background:var(--red); border-radius:9px; font-size:12px; font-weight:700; }
    .profile-card strong { position:absolute; right:13px; bottom:12px; left:13px; color:#fff; font-size:18px; text-shadow:0 1px 4px #000; }
    footer { padding:25px 0; border-top:1px solid var(--line); color:var(--muted); background:var(--panel-2); text-align:center; font-size:13px; }
    @media (max-width:900px) {
      .shell, .header-inner { width:min(100% - 28px, 620px); }
      header { height:82px; }
      .site-logo { max-width:45%; height:44px; }
      .site-logo img { width:58px; height:44px; }
      .site-logo span { font-size:14px; }
      .main-nav { gap:14px; font-size:0; }
      .nav-item .nav-icon, .star, .hamburger { font-size:23px; }
      .create { padding:10px 13px; font-size:13px; }
      .intro { align-items:start; flex-direction:column; gap:16px; }
      .seo-links { padding-left:0; }
      .card-grid { grid-template-columns:repeat(2, 1fr); }
      .profile-card { height:300px; }
    }
    @media (max-width:500px) {
      .filters { overflow-x:auto; }
      .filter { flex:0 0 auto; font-size:13px; }
      .category-row { gap:13px; overflow-x:auto; }
      .category-row .selected { padding:9px 14px; }
      .avatar-card, .avatar-card img { width:92px; }
      .avatar-card img { height:92px; }
      .profile-card { height:260px; }
    }
  </style>
</head>
<body>
  <div class="topbar"></div>
  <header>
    <div class="header-inner">
      <a href="{{ url('/') }}" class="site-logo" aria-label="Divas Cuyo">
          <img src="{{ asset('images/logo-divas-cuyo.webp') }}" alt="Divas Cuyo">
          <span>DIVAS CUYO</span>
      </a>
      <nav class="main-nav" aria-label="Navegación principal">
        <a class="nav-item" href="#novedades"><span class="nav-icon">ϟ</span><span>Novedades</span></a>
        <a class="nav-item" href="#videos"><span class="nav-icon">▶</span><span>Videos</span></a>
        <a class="nav-item" href="#llamadas"><span class="nav-icon">▣</span><span>VideoLlamadas</span></a>
        @guest
          <a class="create" href="{{ route('register.show') }}">Crear perfil</a>
        @else
          @php
            $frontUser = auth()->user();
            $frontProfile = $frontUser->modelProfile;
            $frontAvatar = $frontProfile?->currentApprovedPhotos()->first();
          @endphp
          <details class="user-menu">
            <summary aria-label="Abrir menú de usuario">
              <span class="user-avatar">
                @if ($frontAvatar)
                  <img src="{{ route('account.photos.file', [$frontAvatar, 'thumbnail']) }}" alt="">
                @else
                  {{ strtoupper(substr($frontUser->name, 0, 1)) }}
                @endif
              </span>
              <span>{{ $frontUser->name }}</span>
            </summary>
            <div class="user-menu-panel">
              <a href="{{ route('account.dashboard') }}">Mi cuenta</a>
              <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit">Cerrar sesión</button>
              </form>
            </div>
          </details>
        @endguest
        <a class="star" href="#favoritos" aria-label="Favoritos">★</a><a class="hamburger" href="#menu" aria-label="Menú">☰</a>
      </nav>
    </div>
  </header>

  <div class="shell">
    <main id="inicio">
      <div class="intro"><h1>ESCORTS ARGENTINA</h1><a class="live" href="#live">◉ &nbsp; LIVE SEX</a></div>
      <div class="location"><span>◉</span><span>›</span><strong>Argentina</strong><button aria-label="Seleccionar ubicación">⌄</button></div>
      <div class="filters"><a class="filter" href="#nombre">♙ × Nombre</a><a class="filter" href="#zona">♧ × Zona</a><a class="filter" href="#categoria">▣ × Categoría</a></div>
     

      <div class="avatar-scroller" aria-label="Perfiles destacados">
        <a class="avatar-card" href="#more"><img src="https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=300&q=80" alt="More"><span>More</span></a>
        <a class="avatar-card" href="#karen"><img src="https://images.unsplash.com/photo-1512316609839-ce289d3eba0a?auto=format&fit=crop&w=300&q=80" alt="Karen"><span>Karen</span></a>
        <a class="avatar-card" href="#amaniki"><img src="https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80" alt="Ama Niki"><span>Ama Niki</span></a>
        <a class="avatar-card" href="#luna"><img src="https://images.unsplash.com/photo-1524504388940-b1c1722653e1?auto=format&fit=crop&w=300&q=80" alt="Luna"><span>Luna</span></a>
        <a class="avatar-card" href="#cami"><img src="https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=300&q=80" alt="Cami"><span>Cami</span></a>
        <a class="avatar-card" href="#perli"><img src="https://images.unsplash.com/photo-1529139574466-a303027c1d8b?auto=format&fit=crop&w=300&q=80" alt="Perli"><span>Perli</span></a>
        <a class="avatar-card" href="#lilith"><img src="https://images.unsplash.com/photo-1508214751196-bcfd4ca60f91?auto=format&fit=crop&w=300&q=80" alt="Lilith"><span>Lilith</span></a>
        <a class="avatar-card" href="#lorraine"><img src="https://images.unsplash.com/photo-1490481651871-ab68de25d43d?auto=format&fit=crop&w=300&q=80" alt="Lorraine"><span>Lorraine</span></a>
      </div>

      <section id="videos"><h2 class="section-title">ESCORTS ARGENTINA <em>🌹</em> BLACK ROSE</h2><div class="card-grid">
        <a class="profile-card" href="#perfil-1"><img src="https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Perfil destacado</strong></a>
        <a class="profile-card" href="#perfil-2"><img src="https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Novedad</strong></a>
        <a class="profile-card" href="#perfil-3"><img src="https://images.unsplash.com/photo-1485230895905-ec40ba36b9bc?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Conocé el perfil</strong></a>
        <a class="profile-card" href="#perfil-4"><img src="https://images.unsplash.com/photo-1524250502761-1ac6f2e30d43?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Disponible online</strong></a>
        <a class="profile-card" href="#perfil-5"><img src="https://images.unsplash.com/photo-1496747611176-843222e1e57c?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Perfil destacado</strong></a>
        <a class="profile-card" href="#perfil-6"><img src="https://images.unsplash.com/photo-1502823403499-6ccfcf4fb453?auto=format&fit=crop&w=700&q=80" alt="Perfil destacado"><span class="virtual">VIRTUAL</span><strong>Ver perfil</strong></a>
      </div></section>
    </main>
  </div>
  <footer>© 2026 Divas Cuyo · Comunidad online de la región</footer>
</body>
</html>
