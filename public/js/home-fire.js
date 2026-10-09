/* Fire shader adapted from welcome.blade copy.php. */
(() => {
function initFuego(canvas, opts) {
  if (!canvas) return null;
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
  var motion = window.matchMedia('(prefers-reduced-motion: reduce)');
  var running = !motion.matches;
  var animationId = null;

  function size() {
    var dpr = Math.min(window.devicePixelRatio || 1, 1.5);
    var w = Math.max(1, Math.round(canvas.clientWidth * dpr));
    var h = Math.max(1, Math.round(canvas.clientHeight * dpr));
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
    animationId = running && !document.hidden ? requestAnimationFrame(frame) : null;
  }

  function refresh() {
    if (animationId !== null) cancelAnimationFrame(animationId);
    animationId = null;
    last = performance.now();
    if (!document.hidden) frame(last);
  }

  document.addEventListener('visibilitychange', refresh);
  window.addEventListener('resize', refresh);
  motion.addEventListener('change', function () {
    running = !motion.matches;
    refresh();
  });
  refresh();
  return {
    opts: opts,
    pause: function () { running = false; refresh(); },
    play: function () { running = !motion.matches; refresh(); },
    get running() { return running; }
  };
}

initFuego(document.getElementById('fuego'), { height: 1.55, speed: 0.2, sparks: 1 });
})();
