<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Station Radar — Interface de Surveillance</title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700&display=swap');

  :root {
    --green:     #00ff88;
    --green-dim:#00aa55;
    --green-lo: #004422;
    --red:      #ff3333;
    --amber:    #ffaa00;
    --bg:       #020d06;
    --bg2:      #040f08;
    --panel:    #071a0d;
    --border:   #0a3318;
    --text:     #00cc66;
    --text-dim: #006633;
  }

  * { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: 'Share Tech Mono', monospace;
    min-height: 100vh;
    display: grid;
    grid-template-rows: auto 1fr auto;
    overflow: hidden;
  }

  /* ── HEADER ── */
  header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 10px 24px;
    border-bottom: 1px solid var(--border);
    background: var(--panel);
  }
  .logo {
    font-family: 'Orbitron', monospace;
    font-size: 13px;
    letter-spacing: 4px;
    color: var(--green);
  }
  .logo span { color: var(--green-dim); font-size: 10px; }

  .header-stats {
    display: flex;
    gap: 32px;
  }
  .stat {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
  }
  .stat-label { font-size: 9px; color: var(--text-dim); letter-spacing: 2px; text-transform: uppercase; }
  .stat-val   { font-size: 16px; font-family: 'Orbitron', monospace; }
  .stat-val.green  { color: var(--green); }
  .stat-val.amber  { color: var(--amber); }
  .stat-val.red    { color: var(--red); }

  /* ── MAIN ── */
  main {
    display: grid;
    grid-template-columns: 220px 1fr 220px;
    gap: 0;
    overflow: hidden;
  }

  /* ── SIDE PANELS ── */
  .side {
    background: var(--panel);
    border-right: 1px solid var(--border);
    padding: 20px 14px;
    display: flex;
    flex-direction: column;
    gap: 18px;
    overflow-y: auto;
  }
  .side.right { border-right: none; border-left: 1px solid var(--border); }

  .panel-block {
    border: 1px solid var(--border);
    padding: 12px;
    position: relative;
  }
  .panel-block::before {
    content: attr(data-label);
    position: absolute;
    top: -7px; left: 10px;
    background: var(--panel);
    padding: 0 6px;
    font-size: 9px;
    color: var(--text-dim);
    letter-spacing: 2px;
  }
  .big-num {
    font-family: 'Orbitron', monospace;
    font-size: 28px;
    line-height: 1;
    margin-top: 4px;
  }
  .big-unit { font-size: 11px; color: var(--text-dim); margin-top: 4px; }

  /* Mode badge */
  .mode-badge {
    font-family: 'Orbitron', monospace;
    font-size: 11px;
    letter-spacing: 2px;
    padding: 8px 10px;
    border: 1px solid;
    text-align: center;
    transition: all 0.3s;
  }
  .mode-badge.normal    { border-color: var(--green-dim); color: var(--green); background: #001a08; }
  .mode-badge.smart     { border-color: var(--amber); color: var(--amber); background: #1a0e00; }
  .mode-badge.locked    { border-color: var(--red); color: var(--red); background: #1a0000; animation: blink 0.6s infinite; }

  @keyframes blink { 0%,100%{ opacity:1 } 50%{ opacity:0.3 } }

  /* Trace log */
  .log-list {
    list-style: none;
    font-size: 10px;
    color: var(--text-dim);
    max-height: 180px;
    overflow-y: auto;
    display: flex;
    flex-direction: column-reverse;
    gap: 3px;
  }
  .log-list li { border-bottom: 1px solid #0a1f10; padding-bottom: 3px; }
  .log-list li.danger { color: var(--red); }
  .log-list li.warn   { color: var(--amber); }

  /* Scale bars */
  .scale-bar {
    height: 6px;
    background: var(--green-lo);
    border-radius: 3px;
    overflow: hidden;
    margin-top: 6px;
  }
  .scale-bar-fill {
    height: 100%;
    background: var(--green);
    border-radius: 3px;
    transition: width 0.3s;
  }
  .scale-bar-fill.danger { background: var(--red); }
  .scale-bar-fill.warn   { background: var(--amber); }

  /* ── RADAR CANVAS ── */
  .radar-wrap {
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--bg);
    position: relative;
    overflow: hidden;
  }
  canvas#radar {
    display: block;
    max-width: 100%;
    max-height: 100%;
  }

  /* Overlay CIBLE VERROUILLEE */
  #lock-overlay {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    font-family: 'Orbitron', monospace;
    font-size: 18px;
    letter-spacing: 6px;
    color: var(--red);
    text-shadow: 0 0 20px var(--red);
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.3s;
    text-align: center;
    white-space: nowrap;
  }
  #lock-overlay.visible { opacity: 1; animation: blink 0.6s infinite; }

  /* ── FOOTER ── */
  footer {
    background: var(--panel);
    border-top: 1px solid var(--border);
    padding: 6px 24px;
    display: flex;
    gap: 32px;
    font-size: 10px;
    color: var(--text-dim);
    letter-spacing: 1px;
  }
  footer .dot {
    display: inline-block;
    width: 6px; height: 6px;
    border-radius: 50%;
    background: var(--green);
    margin-right: 6px;
    animation: pulse 2s infinite;
  }
  @keyframes pulse { 0%,100%{ opacity:1 } 50%{ opacity:0.3 } }
  footer .dot.off { background: var(--text-dim); animation: none; }
</style>
</head>
<body>

<header>
  <div class="logo">
    STATION RADAR<br>
    <span>SURVEILLANCE EMBARQUÉE v2.0</span>
  </div>
  <div class="header-stats">
    <div class="stat">
      <span class="stat-label">Angle actuel</span>
      <span class="stat-val green" id="h-angle">---°</span>
    </div>
    <div class="stat">
      <span class="stat-label">Distance</span>
      <span class="stat-val green" id="h-dist">--- cm</span>
    </div>
    <div class="stat">
      <span class="stat-label">Température</span>
      <span class="stat-val amber" id="h-temp">--.- °C</span>
    </div>
    <div class="stat">
      <span class="stat-label">Statut</span>
      <span class="stat-val green" id="h-status">INIT</span>
    </div>
  </div>
</header>

<main>
  <aside class="side">
    <div class="panel-block" data-label="MODE ACTIF">
      <div class="mode-badge normal" id="mode-badge">SCAN NORMAL</div>
    </div>
    <div class="panel-block" data-label="MESURE DISTANCE">
      <div class="big-num green" id="dist-big">---</div>
      <div class="big-unit">CENTIMÈTRES</div>
      <div class="scale-bar"><div class="scale-bar-fill" id="dist-bar" style="width:0%"></div></div>
    </div>
    <div class="panel-block" data-label="TEMPÉRATURE">
      <div class="big-num amber" id="temp-big">--.-</div>
      <div class="big-unit">CELSIUS</div>
    </div>
    <div class="panel-block" data-label="POSITION SERVO">
      <div class="big-num green" id="angle-big">---</div>
      <div class="big-unit">DEGRÉS (0–180°)</div>
      <div class="scale-bar"><div class="scale-bar-fill" id="angle-bar" style="width:0%"></div></div>
    </div>
  </aside>

  <div class="radar-wrap">
    <canvas id="radar"></canvas>
    <div id="lock-overlay">⊕ CIBLE VERROUILLEE</div>
  </div>

  <aside class="side right">
    <div class="panel-block" data-label="DÉTECTIONS RÉCENTES">
      <ul class="log-list" id="log-list">
        <li>En attente de données...</li>
      </ul>
    </div>
    <div class="panel-block" data-label="PORTÉE RADAR">
      <div style="font-size:10px;color:var(--text-dim);margin-bottom:8px;">
        Zone de détection : 20 cm<br>
        Portée max US : 400 cm<br>
        Balayage : 0° – 180°
      </div>
    </div>
    <div class="panel-block" data-label="LÉGENDE">
      <div style="font-size:10px;line-height:1.9;color:var(--text-dim);">
        <span style="color:var(--green)">●</span> Éventail de balayage<br>
        <span style="color:rgba(0,255,136,0.5)">●</span> Traces (estompe)<br>
        <span style="color:var(--red)">—</span> Faisceau cible (Ligne)<br>
        <span style="color:#ff333388">✚</span> Obstacle verrouillé
      </div>
    </div>
  </aside>
</main>

<footer>
  <span><span class="dot" id="dot-db"></span>BASE DE DONNÉES</span>
  <span><span class="dot" id="dot-serial"></span>LIAISON SÉRIE</span>
  <span id="footer-time">--:--:--</span>
  <span id="footer-pts">0 points actifs</span>
</footer>

<script>
// ═══════════════════════════════════════════════════════════
// CONFIGURATION
// ═══════════════════════════════════════════════════════════
const API = './api.php';
const POLL_MS   = 150;   // polling état global
const MAX_RANGE = 20;   // cm max affiché sur radar
const FADE_MS   = 3000;  // durée de vie d'une trace obstacle (ms) avant disparition
const FADE_DOTS = 1500;  // temps avant que les points verts normaux disparaissent
const FAN_LINES = 15;    // nombre de lignes de l'éventail de balayage

// ═══════════════════════════════════════════════════════════
// INITIALISATION CANVAS
// ═══════════════════════════════════════════════════════════
const canvas = document.getElementById('radar');
const ctx    = canvas.getContext('2d');

function resizeCanvas() {
  const wrap = canvas.parentElement;
  const size = Math.min(wrap.clientWidth, wrap.clientHeight) - 20;
  canvas.width  = size;
  canvas.height = size / 2 + size * 0.1; 
}
resizeCanvas();
window.addEventListener('resize', () => { resizeCanvas(); drawRadar(); });

// ═══════════════════════════════════════════════════════════
// VARIABLES D'ÉTAT
// ═══════════════════════════════════════════════════════════
let state = {
  mode: 1, angle: 0, distance: null, temperature: null, objet: false, locked: false
};

const scanPoints = new Array(181).fill(null);
const obstacleTraces = [];
const logEntries = [];
let angleHistory = []; 
let lockedTargetsHistory = []; 

// ═══════════════════════════════════════════════════════════
// FONCTIONS MATHÉMATIQUES RADAR
// ═══════════════════════════════════════════════════════════
function radarOrigin() { return { x: canvas.width / 2, y: canvas.height - canvas.height * 0.08 }; }
function radarRadius() { return Math.min(canvas.width / 2, canvas.height) * 0.92; }

function polarToXY(angleDeg, distCm) {
  const o = radarOrigin();
  const R = radarRadius();
  const norm   = Math.min(distCm, MAX_RANGE) / MAX_RANGE;
  const rad    = (180 - angleDeg) * Math.PI / 180; 
  return {
    x: o.x + R * norm * Math.cos(rad),
    y: o.y - R * norm * Math.sin(rad),
  };
}

// ═══════════════════════════════════════════════════════════
// FONCTION PRINCIPALE DE DESSIN
// ═══════════════════════════════════════════════════════════
function drawRadar() {
  const o = radarOrigin();
  const R = radarRadius();
  ctx.clearRect(0, 0, canvas.width, canvas.height);

  // 1. Fond et Grille
  ctx.fillStyle = 'rgba(2,13,6,0.0)';
  ctx.fillRect(0, 0, canvas.width, canvas.height);

  const rings = [0.25, 0.5, 0.75, 1.0];
  rings.forEach((r, i) => {
    ctx.beginPath();
    ctx.arc(o.x, o.y, R * r, Math.PI, 0, false);
    ctx.strokeStyle = i === 3 ? '#0a4020' : '#061a0b';
    ctx.lineWidth = i === 3 ? 1.5 : 0.8;
    ctx.stroke();
    const label = Math.round(MAX_RANGE * r) + 'cm';
    ctx.fillStyle = '#004422';
    ctx.font = '9px Share Tech Mono';
    ctx.fillText(label, o.x + R * r + 3, o.y - 4);
  });

  [0, 30, 60, 90, 120, 150, 180].forEach(a => {
    const rad = (180 - a) * Math.PI / 180;
    ctx.beginPath();
    ctx.moveTo(o.x, o.y);
    ctx.lineTo(o.x + R * Math.cos(rad), o.y - R * Math.sin(rad));
    ctx.strokeStyle = '#061a0b';
    ctx.lineWidth = 0.8;
    ctx.stroke();
    const lx = o.x + (R + 14) * Math.cos(rad);
    const ly = o.y - (R + 14) * Math.sin(rad);
    ctx.fillStyle = '#006633';
    ctx.textAlign = 'center';
    ctx.fillText(a + '°', lx, ly);
  });
  ctx.textAlign = 'left';

  const now = Date.now();

  // 2. Points de balayage normaux (Effet essuie-glace avec traces qui disparaissent)
  scanPoints.forEach((pt, angle) => {
    if (!pt) return;
    const age = now - pt.ts;
    if (age > FADE_DOTS) { scanPoints[angle] = null; return; }
    const alpha = 1 - (age / FADE_DOTS);
    const pos = polarToXY(angle, pt.dist);
    ctx.beginPath();
    ctx.arc(pos.x, pos.y, 2, 0, Math.PI * 2);
    ctx.fillStyle = `rgba(0,255,136,${alpha * 0.6})`;
    ctx.fill();
  });

  // 3. Dessin de l'éventail (Fan effect)
  angleHistory.forEach((histAngle, i) => {
    const histRad = (180 - histAngle) * Math.PI / 180;
    const alpha = 1 - (i / FAN_LINES); 
    
    ctx.beginPath();
    ctx.moveTo(o.x, o.y);
    ctx.lineTo(o.x + R * Math.cos(histRad), o.y - R * Math.sin(histRad));
    
    if (state.mode === 2) {
      // Éventail Jaune/Orange en mode intelligent
      ctx.strokeStyle = `rgba(255, ${200 - (i*10)}, 50, ${alpha * 0.4})`; 
    } else {
      // Éventail Vert en mode normal
      ctx.strokeStyle = `rgba(0, 255, 136, ${alpha * 0.25})`; 
    }
    ctx.lineWidth = 2;
    ctx.stroke();
  });

  // Ligne de balayage principale
  const sweepRad = (180 - state.angle) * Math.PI / 180;
  ctx.beginPath();
  ctx.moveTo(o.x, o.y);
  ctx.lineTo(o.x + R * Math.cos(sweepRad), o.y - R * Math.sin(sweepRad));
  ctx.strokeStyle = state.mode === 2 ? '#ffaa00' : '#00ff88'; 
  ctx.lineWidth = 3;
  ctx.stroke();

  // 4. Traces des obstacles : LIGNE ROUGE (Laser) au lieu des points ronds
  for (let i = obstacleTraces.length - 1; i >= 0; i--) {
    const tr = obstacleTraces[i];
    const age = now - tr.ts;
    if (age > FADE_MS && !tr.isLocked) { obstacleTraces.splice(i, 1); continue; }
    
    // La ligne reste rouge vif plus longtemps si la cible est verrouillée
    let alpha = tr.isLocked ? 1 : 1 - age / FADE_MS;
    if(alpha < 0) alpha = 0;

    const pos = polarToXY(tr.angle, tr.dist);
    const laserEdge = polarToXY(tr.angle, MAX_RANGE); // Pour tracer la ligne jusqu'au bout
    
    // Dessin de la ligne droite rouge (Façon Sniper)
    ctx.beginPath();
    ctx.moveTo(o.x, o.y);
    ctx.lineTo(pos.x, pos.y); // Ligne principale jusqu'à l'objet
    ctx.strokeStyle = tr.isLocked ? `rgba(255, 0, 0, ${alpha})` : `rgba(255, 80, 80, ${alpha * 0.8})`;
    ctx.lineWidth = tr.isLocked ? 2 : 1.5;
    ctx.shadowBlur = 10;
    ctx.shadowColor = 'red';
    ctx.stroke();
    ctx.shadowBlur = 0;

    // Faisceau résiduel plus fin qui va jusqu'au bout du radar
    ctx.beginPath();
    ctx.moveTo(pos.x, pos.y);
    ctx.lineTo(laserEdge.x, laserEdge.y);
    ctx.strokeStyle = `rgba(255, 0, 0, ${alpha * 0.2})`;
    ctx.lineWidth = 1;
    ctx.stroke();

    // Au lieu d'un cercle, on dessine une croix de visée sur la cible
    ctx.beginPath();
    ctx.moveTo(pos.x - 6, pos.y); ctx.lineTo(pos.x + 6, pos.y);
    ctx.moveTo(pos.x, pos.y - 6); ctx.lineTo(pos.x, pos.y + 6);
    ctx.strokeStyle = `rgba(255, 255, 255, ${alpha})`;
    ctx.lineWidth = 1.5;
    ctx.stroke();
  }

  // 5. Étiquettes "CIBLE 1" pour les objets verrouillés
  drawLockedTargetCallouts(lockedTargetsHistory);

  // 6. Base / Centre
  ctx.font = '600 11px Orbitron, monospace';
  ctx.fillStyle = state.mode === 2 ? '#ffaa00' : '#00aa55';
  ctx.textAlign = 'center';
  ctx.fillText(state.mode === 2 ? 'MODE INTELLIGENT' : 'MODE NORMAL', o.x, o.y + 18);
  ctx.textAlign = 'left';

  ctx.beginPath();
  ctx.arc(o.x, o.y, 4, 0, Math.PI * 2);
  ctx.fillStyle = state.mode === 2 ? '#ffaa00' : '#00ff88';
  ctx.fill();
}

function drawLockedTargetCallouts(targets) {
    const o = radarOrigin();
    const R = radarRadius();
    const now = Date.now();

    targets.forEach((t, i) => {
        if (now - t.ts > FADE_MS) return;

        const pos = polarToXY(t.angle, t.dist);
        const calloutPos = {
            x: o.x + (i % 2 === 0 ? 1 : -1) * (R * 0.6),
            y: pos.y > o.y - 80 ? pos.y - 60 : pos.y + 60
        };
        const boxPos = {
            x: calloutPos.x - 40,
            y: calloutPos.y - 12
        };

        ctx.save();
        
        // Ligne de connexion entre la boîte et la cible
        ctx.beginPath();
        ctx.moveTo(pos.x, pos.y);
        ctx.lineTo(boxPos.x + 40, boxPos.y + (calloutPos.y < pos.y ? 24 : 0));
        ctx.strokeStyle = 'rgba(255, 100, 100, 0.6)';
        ctx.lineWidth = 1;
        ctx.stroke();

        // Boîte d'étiquette
        ctx.fillStyle = 'rgba(20, 20, 20, 0.8)';
        ctx.strokeStyle = 'rgba(255, 50, 50, 0.8)';
        ctx.lineWidth = 1;
        ctx.fillRect(boxPos.x, boxPos.y, 80, 24);
        ctx.strokeRect(boxPos.x, boxPos.y, 80, 24);
        
        // Texte
        ctx.fillStyle = '#ff3333';
        ctx.font = 'bold 11px Orbitron, sans-serif';
        ctx.textAlign = 'center';
        ctx.fillText(`CIBLE ${t.id}`, boxPos.x + 40, boxPos.y + 16);
        
        ctx.restore();
    });
}

// ═══════════════════════════════════════════════════════════
// MISE À JOUR UI ET SYNCHRONISATION API
// ═══════════════════════════════════════════════════════════
function updateUI(data) {
  if (data.angle !== state.angle) {
    angleHistory.unshift(data.angle);
    if (angleHistory.length > FAN_LINES) angleHistory.pop();
  }

  state.mode      = data.mode;
  state.angle     = data.angle;
  state.distance  = data.distance;
  state.temperature = data.temperature;
  state.objet     = data.objet;
  state.locked    = data.mode === 2 && data.objet;

  document.getElementById('h-angle').textContent = data.angle + '°';
  document.getElementById('h-dist').textContent  = data.distance !== null ? data.distance.toFixed(1) + ' cm' : 'Hors portée';
  document.getElementById('h-temp').textContent  = data.temperature !== null ? data.temperature.toFixed(1) + ' °C' : 'Erreur';

  const statusEl = document.getElementById('h-status');
  const badge = document.getElementById('mode-badge');
  
  if (state.locked) {
    statusEl.textContent = 'VERROUILLEE';
    statusEl.className = 'stat-val red';
    badge.textContent = '⊕ CIBLE VERROUILLEE';
    badge.className = 'mode-badge locked';
  } else if (data.mode === 2) {
    statusEl.textContent = 'SCAN INT.';
    statusEl.className = 'stat-val amber';
    badge.textContent = 'SCAN INTELLIGENT';
    badge.className = 'mode-badge smart';
  } else {
    statusEl.textContent = 'SCAN';
    statusEl.className = 'stat-val green';
    badge.textContent = 'SCAN NORMAL';
    badge.className = 'mode-badge normal';
  }

  document.getElementById('lock-overlay').classList.toggle('visible', state.locked);

  const distEl = document.getElementById('dist-big');
  const distBar = document.getElementById('dist-bar');
  if (data.distance !== null) {
    distEl.textContent = data.distance.toFixed(1);
    const pct = Math.min(data.distance / MAX_RANGE * 100, 100);
    distBar.style.width = pct + '%';
    distBar.className = 'scale-bar-fill' + (data.distance <= 20 ? ' danger' : data.distance <= 50 ? ' warn' : '');
    distEl.className = 'big-num' + (data.distance <= 20 ? ' red' : ' green');
  } else {
    distEl.textContent = '---';
    distEl.className = 'big-num green';
    distBar.style.width = '0%';
  }

  document.getElementById('temp-big').textContent = data.temperature !== null ? data.temperature.toFixed(1) : '--.-';
  document.getElementById('angle-big').textContent = data.angle;
  document.getElementById('angle-bar').style.width = (data.angle / 180 * 100) + '%';

  if (data.distance !== null) {
    scanPoints[data.angle] = { dist: data.distance, ts: Date.now() };
    
    if (data.objet) {
      obstacleTraces.push({ angle: data.angle, dist: data.distance, ts: Date.now(), isLocked: state.locked });
      
      if (state.locked) {
          addLog(`CIBLE VERROUILLEE @ ${data.angle}° | ${data.distance.toFixed(1)} cm`, 'danger');
          const existingTarget = lockedTargetsHistory.find(t => Math.abs(t.angle - data.angle) < 5);
          if (existingTarget) {
              existingTarget.ts = Date.now(); 
              existingTarget.dist = data.distance;
          } else {
              lockedTargetsHistory.push({ id: lockedTargetsHistory.length + 1, angle: data.angle, dist: data.distance, ts: Date.now() });
          }
      } else {
          addLog(`OBSTACLE @ ${data.angle}° | ${data.distance.toFixed(1)} cm`, 'warn');
      }
    }
  }

  lockedTargetsHistory = lockedTargetsHistory.filter(t => Date.now() - t.ts < FADE_MS);

  document.getElementById('footer-time').textContent = new Date().toLocaleTimeString('fr-FR');
  document.getElementById('dot-db').className = 'dot';
  document.getElementById('dot-serial').className = 'dot';
}

function addLog(msg, cls = '') {
  logEntries.unshift({ msg, cls, ts: Date.now() });
  if (logEntries.length > 40) logEntries.pop();
  const list = document.getElementById('log-list');
  list.innerHTML = logEntries.slice(0, 18).map(e => {
    const timeStr = new Date(e.ts).toLocaleTimeString('fr-FR', {minute: '2-digit', second: '2-digit'});
    return `<li class="${e.cls}">[${timeStr}] ${e.msg}</li>`;
  }).join('');
}

// ═══════════════════════════════════════════════════════════
// POLLING DE LA BASE DE DONNÉES (API)
// ═══════════════════════════════════════════════════════════
async function pollState() {
  try {
    const r = await fetch(API + '?ts=' + Date.now());
    if (!r.ok) throw new Error(r.status);
    const data = await r.json();
    if (data.status === 'no_data') return;
    updateUI(data);
    if (data.stats) {
      document.getElementById('footer-pts').textContent =
        (data.stats.total_points || 0) + ' pts globaux | DB Connectée';
    }
  } catch(e) {
    document.getElementById('dot-db').className = 'dot off';
    document.getElementById('dot-serial').className = 'dot off';
  }
}

function renderLoop() {
  drawRadar();
  requestAnimationFrame(renderLoop);
}

// Lancement
addLog('Système initialisé', 'green');
addLog('En attente de données Arduino (via MySQL)...');
setInterval(pollState, POLL_MS);
renderLoop();
</script>
</body>
</html>
