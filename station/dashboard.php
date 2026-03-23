<?php ?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TACTICAL RADAR HUD // DEF-1</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">
<style>
:root {
  --g:    #00ff00;
  --gd:   rgba(0,255,0,0.08);
  --gb:   rgba(0,255,0,0.25);
  --red:  #ff003c;
  --bg:   #000;
  --dark: rgba(0,20,0,0.85);
}
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;height:100%;overflow:hidden;}

body {
  background:var(--bg);
  color:var(--g);
  font-family:'Share Tech Mono',monospace;
  display:grid;
  grid-template-rows: auto 1fr;
  grid-template-columns: 1fr 210px;
  height:100vh;
}

body::after{
  content:'';
  position:fixed;
  inset:0;
  z-index:999;
  pointer-events:none;
  background:
    linear-gradient(rgba(18,16,16,0) 50%,rgba(0,0,0,0.22) 50%),
    linear-gradient(90deg,rgba(255,0,0,0.04),rgba(0,255,0,0.02),rgba(0,0,255,0.04));
  background-size:100% 4px,6px 100%;
}

/* TOPBAR */
.topbar{
  grid-column:1/-1;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:12px 24px;
  border-bottom:2px solid var(--g);
  background:var(--dark);
  text-transform:uppercase;
  z-index:10;
}
.top-left{ display:flex;flex-direction:column;gap:3px; }
.sys-title{
  font-size:.72rem;
  letter-spacing:.2em;
  opacity:.6;
}
.sys-sub{
  font-size:.58rem;
  letter-spacing:.1em;
  opacity:.4;
}
.temp-big{
  font-size:2rem;
  letter-spacing:.04em;
  text-shadow:0 0 12px var(--g);
  line-height:1;
  margin-top:4px;
}

.top-right{
  display:flex;
  align-items:center;
  gap:20px;
}

.alert-box{
  border:1px solid var(--g);
  padding:8px 18px;
  background:var(--gd);
  letter-spacing:.1em;
  font-size:1rem;
  transition:all .2s;
}
body.alert .alert-box{
  border-color:var(--red);
  background:rgba(255,0,60,0.18);
  color:var(--red);
  text-shadow:0 0 10px var(--red);
  animation:blink .5s infinite;
}
@keyframes blink{0%,100%{opacity:1;}50%{opacity:.45;}}

.ctrl-btn{
  background:transparent;
  color:var(--g);
  border:1px solid var(--g);
  padding:7px 14px;
  font-family:'Share Tech Mono',monospace;
  font-size:.78rem;
  letter-spacing:.1em;
  text-transform:uppercase;
  cursor:pointer;
  transition:.2s;
  text-decoration:none;
  display:inline-block;
}
.ctrl-btn:hover{
  background:var(--g);
  color:#000;
  box-shadow:0 0 12px var(--g);
}

/* RADAR AREA */
.radar-area{
  grid-column:1;
  grid-row:2;
  display:flex;
  justify-content:center;
  align-items:center;
  position:relative;
  overflow:hidden;
}
#radarCanvas{display:block;}

.hud-overlay{
  position:absolute;
  bottom:16px;
  left:18px;
  font-size:.68rem;
  opacity:.5;
  line-height:1.7;
  pointer-events:none;
}

/* RIGHT PANEL */
.panel-right{
  grid-column:2;
  grid-row:2;
  border-left:2px solid var(--g);
  background:var(--dark);
  display:flex;
  flex-direction:column;
  padding:14px 12px;
  gap:10px;
  overflow:hidden;
}

.p-label{
  font-size:.58rem;
  letter-spacing:.18em;
  opacity:.45;
  text-transform:uppercase;
  margin-bottom:2px;
}

.p-card{
  border:1px solid rgba(0,255,0,0.2);
  background:var(--gd);
  padding:9px 10px;
}

.badge{
  display:inline-block;
  font-size:.65rem;
  letter-spacing:.1em;
  padding:2px 8px;
  border:1px solid;
}
.badge-ok {
  color:var(--g);
  border-color:rgba(0,255,0,.35);
  background:rgba(0,255,0,.06);
}
.badge-err{
  color:var(--red);
  border-color:rgba(255,0,60,.35);
  background:rgba(255,0,60,.06);
  animation:blink .6s step-end infinite;
}

.meta{
  font-size:.6rem;
  opacity:.45;
  line-height:1.8;
  margin-top:5px;
}
.meta span{
  color:var(--g);
  opacity:1;
}

.rec-id{
  font-size:1.4rem;
  letter-spacing:.05em;
  text-shadow:0 0 8px var(--g);
}

.log{
  flex:1;
  overflow-y:auto;
  min-height:0;
  border:1px solid rgba(0,255,0,0.15);
  background:rgba(0,8,0,.6);
  padding:5px 7px;
  font-size:.55rem;
  line-height:1.75;
  color:rgba(0,255,0,0.4);
}
.log::-webkit-scrollbar{width:2px;}
.log::-webkit-scrollbar-thumb{background:rgba(0,255,0,.25);}
.log-alert{color:var(--red)!important;opacity:1!important;}
.log-new{color:var(--g)!important;opacity:.8!important;}
</style>
</head>
<body>

<div class="topbar">
  <div class="top-left">
    <div class="sys-title">◈ RADAR SYS // DEF-1</div>
    <div class="sys-sub">SERVO-RANGE 20°–160° | HC-SR04 SONAR | DS18B20</div>
    <div class="temp-big">ENV_TEMP: <span id="tempDisplay">--.-</span>°C</div>
  </div>

  <div class="top-right">
    <div class="alert-box" id="alertBox">STATUS: SECURE</div>
    <a href="export_csv.php" class="ctrl-btn" id="btnDownload">⬇ Extract Logs</a>
  </div>
</div>

<div class="radar-area" id="radarArea">
  <canvas id="radarCanvas"></canvas>
  <div class="hud-overlay">
    &gt; LAT: 48.8566 N<br>
    &gt; LNG: 2.3522 E<br>
    &gt; SCAN_RADIUS: 400cm
  </div>
</div>

<div class="panel-right">

  <div>
    <div class="p-label">⊹ System Status</div>
    <div class="p-card">
      <div id="sysStatus" class="badge badge-ok">ONLINE</div>
      <div class="meta">
        REFRESH <span>500ms</span><br>
        PROTO <span>BT+SERIAL</span><br>
        RANGE <span>20°–160°</span>
      </div>
    </div>
  </div>

  <div>
    <div class="p-label">⊹ Last Record ID</div>
    <div class="p-card">
      <div class="rec-id" id="recId">----</div>
    </div>
  </div>

  <div>
    <div class="p-label">⊹ Distance</div>
    <div class="p-card">
      <div class="rec-id" id="distDisp" style="font-size:1.1rem;">--- cm</div>
    </div>
  </div>

  <div>
    <div class="p-label">⊹ Angle Servo</div>
    <div class="p-card">
      <div class="rec-id" id="angleDisp" style="font-size:1.1rem;">---°</div>
    </div>
  </div>

  <div style="margin-top:auto;">
    <div class="p-label">⊹ Event Log</div>
    <div class="log" id="logBox"></div>
  </div>

</div>

<script>
const INVERSER_SENS_MOTEUR = false;

const canvas = document.getElementById('radarCanvas');
const ctx = canvas.getContext('2d');

let W, H, cx, cy, R;
function resize() {
  const area = document.getElementById('radarArea');
  const aw = area.clientWidth;
  const ah = area.clientHeight;
  R = Math.min(aw / 2 - 40, ah - 80);
  W = aw;
  H = R + 100;
  canvas.width = W;
  canvas.height = H;
  cx = W / 2;
  cy = H - 60;
}
resize();
window.addEventListener('resize', resize);

const hits = [];
let sweepAngle = 20;
let sweepSpeed = 1.5;
let prevId = 0;
let lastRealAngle = 20;

function distToR(d) {
  return Math.min(d / 400, 1) * R;
}

function pointXY(deg, dist) {
  const renderDeg = INVERSER_SENS_MOTEUR ? (180 - deg) : deg;
  const rad = renderDeg * Math.PI / 180;
  return {
    x: cx + distToR(dist) * Math.cos(rad),
    y: cy - distToR(dist) * Math.sin(rad)
  };
}

function drawRadar() {
  ctx.clearRect(0, 0, W, H);

  ctx.beginPath();
  ctx.arc(cx, cy, R, Math.PI, Math.PI * 2);
  ctx.fillStyle = 'rgba(0, 15, 0, 0.9)';
  ctx.fill();
  ctx.strokeStyle = '#0f0';
  ctx.lineWidth = 2;
  ctx.stroke();

  const rings = 4;
  ctx.strokeStyle = 'rgba(0, 255, 0, 0.3)';
  ctx.fillStyle = 'rgba(0, 255, 0, 0.5)';
  ctx.font = "10px 'Share Tech Mono'";

  for (let i = 1; i <= rings; i++) {
    const r = (R / rings) * i;
    ctx.beginPath();
    ctx.arc(cx, cy, r, Math.PI, Math.PI * 2);
    ctx.stroke();
    ctx.fillText(`${i * 100}cm`, cx + r + 5, cy - 5);
  }

  [20, 50, 90, 130, 160].forEach(deg => {
    const ptLine = pointXY(deg, 400);
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.lineTo(ptLine.x, ptLine.y);
    ctx.strokeStyle = 'rgba(0, 255, 0, 0.3)';
    ctx.stroke();

    const ptText = pointXY(deg, 450);
    ctx.textAlign = 'center';
    ctx.textBaseline = 'middle';
    ctx.fillText(deg + '°', ptText.x, ptText.y);
  });

  const trailLength = 30;
  for (let t = 0; t < trailLength; t++) {
    const offset = t * (sweepSpeed > 0 ? 1 : -1);
    const angleTrail = sweepAngle - offset;
    if (angleTrail < 20 || angleTrail > 160) continue;

    const ptLine = pointXY(angleTrail, 400);
    ctx.beginPath();
    ctx.moveTo(cx, cy);
    ctx.lineTo(ptLine.x, ptLine.y);
    const alpha = 0.6 * (1 - t / trailLength);
    ctx.strokeStyle = `rgba(0, 255, 0, ${alpha})`;
    ctx.lineWidth = 3;
    ctx.stroke();
  }

  hits.forEach(p => {
    const pt = pointXY(p.angle, p.distance);
    const alpha = Math.max(0, 1 - p.age / 150);
    const color = p.buzzer === 1
      ? `rgba(255, 0, 0, ${alpha})`
      : `rgba(0, 255, 0, ${alpha})`;

    const size = 6;
    ctx.beginPath();
    ctx.moveTo(pt.x - size, pt.y);
    ctx.lineTo(pt.x + size, pt.y);
    ctx.moveTo(pt.x, pt.y - size);
    ctx.lineTo(pt.x, pt.y + size);
    ctx.strokeStyle = color;
    ctx.lineWidth = 2;
    ctx.stroke();

    ctx.strokeRect(pt.x - size - 2, pt.y - size - 2, (size * 2) + 4, (size * 2) + 4);
    p.age++;
  });
}

function animate() {
  drawRadar();

  sweepAngle += sweepSpeed;
  if (sweepAngle >= 160) {
    sweepAngle = 160;
    sweepSpeed = -1.5;
  } else if (sweepAngle <= 20) {
    sweepAngle = 20;
    sweepSpeed = 1.5;
  }

  while (hits.length > 0 && hits[0].age > 150) {
    hits.shift();
  }

  requestAnimationFrame(animate);
}
animate();

function addLog(msg, className) {
  const logBox = document.getElementById('logBox');
  const d = new Date();
  const t = d.getHours().toString().padStart(2, '0') + ':' +
            d.getMinutes().toString().padStart(2, '0') + ':' +
            d.getSeconds().toString().padStart(2, '0');

  const div = document.createElement('div');
  div.className = className;
  div.textContent = `[${t}] ${msg}`;
  logBox.prepend(div);

  if (logBox.children.length > 50) {
    logBox.removeChild(logBox.lastChild);
  }
}

async function fetchData() {
  try {
    const res = await fetch('get_latest_data.php');
    const data = await res.json();

    if (!data.success) {
      document.getElementById('sysStatus').textContent = 'NO DATA';
      document.getElementById('sysStatus').className = 'badge badge-err';
      return;
    }

    document.getElementById('sysStatus').textContent = 'ONLINE';
    document.getElementById('sysStatus').className = 'badge badge-ok';

    const id = parseInt(data.id);
    const temp = parseFloat(data.temperature);
    const dist = parseFloat(data.distance);
    const buzzer = parseInt(data.buzzer);
    const realAngle = parseInt(data.angle);

    if (!isNaN(realAngle) && realAngle !== lastRealAngle) {
      if (realAngle > lastRealAngle) {
        sweepSpeed = Math.abs(sweepSpeed);
      } else if (realAngle < lastRealAngle) {
        sweepSpeed = -Math.abs(sweepSpeed);
      }

      if (Math.abs(sweepAngle - realAngle) > 20) {
        sweepAngle = realAngle;
      }

      lastRealAngle = realAngle;
    }

    document.getElementById('tempDisplay').textContent = temp.toFixed(1);
    document.getElementById('recId').textContent = '#' + id;
    document.getElementById('distDisp').textContent = dist < 400 ? dist.toFixed(1) + ' cm' : 'OUT_OF_RANGE';
    document.getElementById('angleDisp').textContent = isNaN(realAngle) ? '---°' : realAngle + '°';

    const alertBox = document.getElementById('alertBox');
    if (buzzer === 1) {
      document.body.classList.add('alert');
      alertBox.textContent = 'WARNING: INTRUDER';
    } else {
      document.body.classList.remove('alert');
      alertBox.textContent = 'STATUS: SECURE';
    }

    if (id !== prevId) {
      if (dist > 0 && dist < 400 && !isNaN(realAngle)) {
        hits.push({
          angle: realAngle,
          distance: dist,
          buzzer: buzzer,
          age: 0
        });
      }

      if (buzzer === 1) {
        addLog(`⚠ THREAT DETECTED: dist=${dist.toFixed(1)}cm ang=${realAngle}°`, 'log-alert');
      } else {
        addLog(`SCAN: dist=${dist.toFixed(1)}cm ang=${realAngle}°`, 'log-new');
      }

      prevId = id;
    }

  } catch (e) {
    document.getElementById('sysStatus').textContent = 'ERR: FETCH';
    document.getElementById('sysStatus').className = 'badge badge-err';
  }
}

fetchData();
setInterval(fetchData, 500);
</script>
</body>
</html>