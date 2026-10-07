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
    .shell { width:min(1320px, calc(100% - 64px)); margin:auto; }
    .fuego-banner { position:relative; height:220px; overflow:hidden; background:#160b07; }
    #fuego { display:block; width:100%; height:100%; }
    .fuego-title { position:absolute; inset:0; display:grid; place-items:center; margin:0; padding:16px; color:#fff; font-size:clamp(24px, 4vw, 48px); font-weight:800; text-align:center; text-shadow:0 2px 16px #000, 0 0 28px rgba(255,92,0,.8); animation:fuego-title-enter 2s cubic-bezier(.2,.75,.25,1) both; }
    @keyframes fuego-title-enter { from { opacity:0; transform:scale(.9); } to { opacity:1; transform:scale(1.05); } }
    @media (prefers-reduced-motion:reduce) { .fuego-title { animation:none; } }
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
      .shell { width:min(100% - 28px, 620px); }
      .fuego-banner { height:160px; }
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
  @include('components.front-header-styles')
  <x-front-header :on-home="true" />

  <div class="fuego-banner">
    <canvas id="fuego"></canvas>
    <h2 class="fuego-title">LAS MÁS CALIENTES DE LA REGIÓN</h2>
  </div>

  <div class="shell">
    <main id="inicio">
      <div class="intro"><h1>ESCORTS ARGENTINA</h1><a class="live" href="#live">◉ &nbsp; LIVE SEX</a></div>
      <div class="location"><span>◉</span><span>›</span><strong>Argentina</strong><button aria-label="Seleccionar ubicación">⌄</button></div>
      <div class="filters"><a class="filter" href="#nombre">♙ × Nombre</a><a class="filter" href="#zona">♧ × Zona</a><a class="filter" href="#categoria">▣ × Categoría</a></div>
     

      <div class="avatar-scroller" aria-label="Perfiles destacados">
        <a class="avatar-card" href="#more"><img src="https://images.unsplash.com/photo-1488426862026-3ee34a7d66df?auto=format&fit=crop&w=300&q=80" alt="More"><span>More</span></a>
      </div>

      <section id="videos">
        <h2 class="section-title">ESCORTS ARGENTINA <em>🌹</em> BLACK ROSE</h2>
        <div class="card-grid">
          @forelse ($publicProfiles as $profile)
            <a class="profile-card" href="{{ $profile['url'] }}">
              <img src="{{ $profile['photoUrl'] }}" alt="{{ $profile['photoAlt'] }}" loading="lazy">
              <strong>{{ $profile['name'] }}</strong>
            </a>
          @empty
            <p class="profiles-empty">No hay perfiles publicados.</p>
          @endforelse
        </div>
      </section>
    </main>
  </div>
  <footer>© 2026 Divas Cuyo · Comunidad online de la región</footer>
  <script>
    function initFuego(canvas, opts) {
      opts = Object.assign({ height: 1, speed: 1, sparks: 0.6 }, opts || {});
      var gl = canvas.getContext('webgl', { antialias: false, premultipliedAlpha: false });
      if (!gl) return null;

      var vs = 'attribute vec2 a;void main(){gl_Position=vec4(a,0.,1.);}';
      var fs = [
        'precision highp float;',
        'uniform vec2 R;uniform float T,H,S;',
        'float h1(vec2 p){p=fract(p*vec2(123.34,456.21));p+=dot(p,p+45.32);return fract(p.x*p.y);}',
        'vec2 h2(vec2 p){float n=h1(p);return vec2(n,h1(p+vec2(n)));}',
        'float ns(vec2 p){vec2 i=floor(p),f=fract(p);f=f*f*(3.-2.*f);',
        ' return mix(mix(h1(i),h1(i+vec2(1,0)),f.x),mix(h1(i+vec2(0,1)),h1(i+vec2(1.)),f.x),f.y);}',
        'float fbm(vec2 p){float v=0.,a=.5;mat2 m=mat2(1.6,1.2,-1.2,1.6);',
        ' for(int i=0;i<5;i++){v+=a*ns(p);p=m*p;a*=.5;}return v;}',
        'vec3 ramp(float h){vec3 c=vec3(0.);',
        ' c=mix(c,vec3(.45,.03,.0),smoothstep(.02,.3,h));',
        ' c=mix(c,vec3(1.,.32,.02),smoothstep(.25,.55,h));',
        ' c=mix(c,vec3(1.,.72,.22),smoothstep(.5,.8,h));',
        ' c=mix(c,vec3(1.,.95,.8),smoothstep(.82,1.,h));return c;}',
        'void main(){',
        ' vec2 uv=gl_FragCoord.xy/R;float ar=R.x/R.y;',
        ' vec2 p=vec2(uv.x*ar,uv.y);float t=T;',
        ' vec2 q=vec2(p.x*2.2,p.y*1.6-t*1.1);',
        ' float d=fbm(q+vec2(0.,-t*.4));',
        ' float w=fbm(q*1.8+vec2(d*1.8,d*1.2)-vec2(0.,t*1.6));',
        ' float y=uv.y/H;',
        ' float heat=w*1.4-y*1.15+.02;',
        ' heat+=.1*(1.-smoothstep(0.,.2,y));',
        ' heat=clamp(heat,0.,1.);heat=pow(heat,1.6);',
        ' vec3 col=ramp(heat);',
        ' col+=vec3(.35,.06,0.)*(1.-smoothstep(0.,.55,y))*.35;',
        ' vec3 sp=vec3(0.);',
        ' for(int i=0;i<3;i++){float fi=float(i);',
        '  float sc=14.+fi*9.;vec2 g=p*sc;',
        '  g.y-=t*(1.6+fi*.7)*.09;',
        '  g.x+=sin(g.y*.35+fi*2.1+t*.8)*.9;',
        '  vec2 id=floor(g),f=fract(g)-.5;',
        '  float r=h1(id+vec2(fi*17.));',
        '  if(r<S){vec2 o=(h2(id+vec2(fi*3.1))-.5)*.7;',
        '   float dd=length(f-o);',
        '   float sz=.035+.05*h1(id+vec2(9.));',
        '   float fl=.6+.4*sin(t*(8.+r*20.)+r*60.);',
        '   float fade=1.-smoothstep(.15,1.,uv.y+.25*h1(id+vec2(2.)));',
        '   sp+=vec3(1.,.55,.18)*(1.-smoothstep(0.,sz,dd))*fl*fade*(1.4-fi*.3);}}',
        ' col+=sp;',
        ' gl_FragColor=vec4(col,1.);}'
      ].join('\n');

      function sh(type, source) {
        var shader = gl.createShader(type);
        gl.shaderSource(shader, source);
        gl.compileShader(shader);
        if (!gl.getShaderParameter(shader, gl.COMPILE_STATUS)) {
          console.error(gl.getShaderInfoLog(shader));
          gl.deleteShader(shader);
          return null;
        }
        return shader;
      }

      var vertexShader = sh(gl.VERTEX_SHADER, vs);
      var fragmentShader = sh(gl.FRAGMENT_SHADER, fs);
      if (!vertexShader || !fragmentShader) return null;
      var pr = gl.createProgram();
      gl.attachShader(pr, vertexShader);
      gl.attachShader(pr, fragmentShader);
      gl.linkProgram(pr);
      if (!gl.getProgramParameter(pr, gl.LINK_STATUS)) {
        console.error(gl.getProgramInfoLog(pr));
        return null;
      }
      gl.useProgram(pr);

      var b = gl.createBuffer();
      gl.bindBuffer(gl.ARRAY_BUFFER, b);
      gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([-1, -1, 3, -1, -1, 3]), gl.STATIC_DRAW);
      var la = gl.getAttribLocation(pr, 'a');
      gl.enableVertexAttribArray(la);
      gl.vertexAttribPointer(la, 2, gl.FLOAT, false, 0, 0);
      var uR = gl.getUniformLocation(pr, 'R');
      var uT = gl.getUniformLocation(pr, 'T');
      var uH = gl.getUniformLocation(pr, 'H');
      var uS = gl.getUniformLocation(pr, 'S');
      var t = 0;
      var last = performance.now();
      var running = true;

      function size() {
        var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
        var w = Math.round(canvas.clientWidth * dpr);
        var h = Math.round(canvas.clientHeight * dpr);
        if (canvas.width !== w || canvas.height !== h) {
          canvas.width = w;
          canvas.height = h;
          gl.viewport(0, 0, w, h);
        }
      }

      function frame(now) {
        var dt = Math.min((now - last) / 1000, 0.1);
        last = now;
        if (running) t += dt * opts.speed;
        size();
        gl.uniform2f(uR, canvas.width, canvas.height);
        gl.uniform1f(uT, t);
        gl.uniform1f(uH, opts.height);
        gl.uniform1f(uS, opts.sparks * 0.35);
        gl.drawArrays(gl.TRIANGLES, 0, 3);
        requestAnimationFrame(frame);
      }

      requestAnimationFrame(frame);
      document.addEventListener('visibilitychange', function () { last = performance.now(); });
      return {
        opts: opts,
        pause: function () { running = false; },
        play: function () { running = true; },
        get running() { return running; }
      };
    }

    initFuego(document.getElementById('fuego'), { height: 1.55, speed: 0.2, sparks: 1 });
  </script>
</body>
</html>
