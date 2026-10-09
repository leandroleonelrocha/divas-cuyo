<style>
  .dc-toolbar { background:transparent; border:0; margin:16px 0 12px; color:#111827; }
  .dc-toolbar-inner { width:100%; margin:0; padding:0; }
  .dc-toolbar-row { display:flex; align-items:center; gap:8px; }
  .dc-toolbar-group { display:flex; align-items:center; gap:8px; min-width:0; }
  .dc-toolbar-divider { flex:0 0 auto; width:1px; height:28px; background:#d9dce1; }
  .dc-toolbar-loc { flex:0 0 auto; display:inline-flex; align-items:center; gap:8px; min-height:42px; padding:0 16px 0 12px; color:#111827; background:#fff; border:1px solid #d9dce1; border-radius:999px; font-size:15px; font-weight:700; font-family:inherit; }
  .dc-toolbar-loc select { appearance:none; -webkit-appearance:none; padding:10px 2px 10px 0; color:inherit; background:transparent; border:0; font-size:inherit; font-weight:inherit; font-family:inherit; cursor:pointer; }
  .dc-toolbar-loc select:focus-visible { outline:3px solid #f04a50; outline-offset:2px; border-radius:8px; }
  .dc-toolbar-icon { flex:0 0 auto; display:grid; place-items:center; width:42px; height:42px; padding:0; color:#3a3a40; background:#fff; border:1px solid #d9dce1; border-radius:50%; cursor:pointer; text-decoration:none; transition:background .2s, border-color .2s; }
  .dc-toolbar-icon:hover { background:#fdebec; border-color:rgba(215,25,32,.35); color:#b9141a; }
  .dc-toolbar-live { flex:0 0 auto; display:inline-flex; align-items:center; gap:9px; margin-left:auto; padding:10px 20px; color:#fff; background:linear-gradient(135deg, #e0202c 0%, #a80f1a 100%); border:0; border-radius:999px; font-size:14px; font-weight:800; letter-spacing:.05em; white-space:nowrap; }
  .dc-toolbar-live:hover { color:#fff; filter:brightness(1.08); }
  .dc-toolbar-cats { display:flex; align-items:center; gap:8px; margin-top:10px; overflow-x:auto; padding-bottom:2px; scrollbar-width:thin; }
  .dc-toolbar-cat { flex:0 0 auto; padding:10px 20px; color:#3a3a40; background:#fff; border:1px solid #d9dce1; border-radius:999px; font-size:14px; font-weight:600; white-space:nowrap; transition:background .2s, color .2s, border-color .2s; }
  .dc-toolbar-cat:hover { background:#fdebec; border-color:rgba(215,25,32,.35); color:#b9141a; }
  .dc-toolbar-cat.active { color:#fff; background:#1a1a1e; border-color:#1a1a1e; }
  @media (max-width:900px) {
    .dc-toolbar-row { flex-wrap:wrap; }
    .dc-toolbar-loc { font-size:14px; min-height:42px; }
    .dc-toolbar-icon { width:42px; height:42px; }
    .dc-toolbar-live { font-size:13px; padding:10px 18px; }
    .dc-toolbar-cat { font-size:14px; padding:10px 18px; }
  }
</style>
