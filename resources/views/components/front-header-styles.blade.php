<style>
  .dc-front-header-wrap { position:relative; z-index:20; }
  .dc-front-topbar { height:7px; background:#2b2b31; }
  .dc-front-header { width:100%; height:108px; background:#2b2b31; }
  .dc-front-header-inner { display:flex; align-items:center; width:min(1320px, calc(100% - 64px)); height:100%; margin:auto; }
  .dc-front-brand { display:flex; flex:0 0 auto; align-items:center; gap:10px; max-width:40%; height:56px; margin-right:auto; overflow:hidden; color:#fff; line-height:1; text-decoration:none; }
  .dc-front-brand img { display:block; width:76px; height:56px; object-fit:contain; }
  .dc-front-brand span { color:#fff; font-size:20px; font-weight:800; letter-spacing:.04em; white-space:nowrap; }
  .dc-front-navigation { display:flex; align-items:center; gap:42px; color:#fff; font-size:17px; font-weight:600; }
  .dc-front-nav-item { display:flex; align-items:center; gap:11px; color:inherit; text-decoration:none; }
  .dc-front-nav-icon { color:#d71920; font-size:25px; }
  .dc-front-create { padding:13px 22px; color:#fff; background:#d71920; border-radius:13px; text-decoration:none; }
  .dc-front-create:hover { color:#fff; background:#b9141a; }
  .dc-front-user-menu { position:relative; }
  .dc-front-user-menu summary { display:flex; align-items:center; gap:9px; color:#fff; cursor:pointer; list-style:none; }
  .dc-front-user-menu summary::-webkit-details-marker { display:none; }
  .dc-front-user-avatar { display:grid; place-items:center; width:38px; height:38px; overflow:hidden; color:#fff; background:#d71920; border:2px solid rgba(255,255,255,.75); border-radius:50%; font-size:14px; font-weight:800; }
  .dc-front-user-avatar img { width:100%; height:100%; object-fit:cover; }
  .dc-front-user-menu-panel { position:absolute; top:calc(100% + 12px); right:0; z-index:10; display:grid; min-width:180px; gap:4px; padding:8px; color:#111827; background:#fff; border:1px solid #d9dce1; border-radius:10px; box-shadow:0 12px 28px rgba(17,24,39,.18); }
  .dc-front-user-menu-panel a, .dc-front-user-menu-panel button { width:100%; padding:10px 12px; color:#111827; background:transparent; border:0; border-radius:7px; cursor:pointer; font-size:14px; text-align:left; }
  .dc-front-user-menu-panel a:hover, .dc-front-user-menu-panel button:hover { color:#b9141a; background:#fdebec; }
  .dc-front-star { color:#fff; font-size:26px; text-decoration:none; }
  .dc-front-menu { color:#fff; font-size:27px; text-decoration:none; }
  .dc-front-header a:focus-visible, .dc-front-user-menu summary:focus-visible, .dc-front-user-menu button:focus-visible { outline:3px solid #f04a50; outline-offset:4px; }
  .public-model-page .dc-front-header-wrap { margin-right:-16px; margin-left:-16px; }

  @media (max-width:900px) {
    .dc-front-header { height:82px; }
    .dc-front-header-inner { width:min(100% - 28px, 620px); }
    .dc-front-brand { max-width:45%; height:44px; }
    .dc-front-brand img { width:58px; height:44px; }
    .dc-front-brand span { font-size:14px; }
    .dc-front-navigation { gap:14px; font-size:0; }
    .dc-front-nav-icon, .dc-front-star, .dc-front-menu { font-size:23px; }
    .dc-front-create { padding:10px 13px; font-size:13px; }
  }

  @media (prefers-reduced-motion:reduce) {
    .dc-front-header a, .dc-front-user-menu button { transition:none; }
  }
</style>