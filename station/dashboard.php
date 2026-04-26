<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TACTICAL RADAR HUD</title>

<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&display=swap" rel="stylesheet">

<style>
:root {
  --g:#00ff00;
  --gd:rgba(0,255,0,0.08);
  --red:#ff003c;
  --bg:#000;
  --dark:rgba(0,20,0,0.85);
}

*{margin:0;padding:0;box-sizing:border-box;}
html,body{width:100%;height:100%;overflow:hidden;}

body{
  background:var(--bg);
  color:var(--g);
  font-family:'Share Tech Mono',monospace;
  display:grid;
  grid-template-rows:auto 1fr;
  grid-template-columns:1fr 230px;
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

.topbar{
  grid-column:1/-1;
  display:flex;
  justify-content:space-between;
  align-items:center;
  padding:12px 24px;
  border-bottom:2px solid var(--g);
  background:var(--dark);
}

.sys-title{
  font-size:.7rem;
  letter-spacing:.2em;
  opacity:.6;
}

.temp-big{
  font-size:2rem;
  text-shadow:0 0 12px var(--g);
}

.top-right{
  display:flex;
  align-items:center;
  gap:20px;
}

.alert-box,.ctrl-btn{
  border:1px solid var(--g);
  padding:8px 16px;
  background:var(--gd);
  color:var(--g);
  text-decoration:none;
  font-family:'Share Tech Mono',monospace;
  letter-spacing:.1em;
}

body.alert .alert-box{
  border-color:var(--red);
  color:var(--red);
  background:rgba(255,0,60,.18);
  animation:blink .5s infinite;
}

@keyframes blink{
  50%{opacity:.45;}
}

.radar-area{
  grid-column:1;
  grid-row:2;
  display:flex;
  justify-content:center;
  align-items:center;
  overflow:hidden;
}

#radarCanvas{display:block;}

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
  opacity:.5;
  margin-bottom:3px;
  text-transform:uppercase;
}

.p-card{
  border:1px solid rgba(0,255,0,.25);
  background:var(--gd);
  padding:9px 10px;
}

.badge{
  display:inline-block;
  padding:3px 9px;
  font-size:.7rem;
  border:1px solid var(--g);
}

.badge-ok{color:var(--g);}
.badge-err{color:var(--red);border-color:var(--red);}

.meta{
  font-size:.6rem;
  opacity:.5;
  line-height:1.8;
  margin-top:5px;
}

.rec-id{
  font-size:1.35rem;
  text-shadow:0 0 8px var(--g);
}

.log{
  flex:1;
  overflow-y:auto;
  min-height:0;
  border:1px solid rgba(0,255,0,.18);
  background:rgba(0,8,0,.65);
  padding:6px;
  font-size:.55rem;
  line-height:1.7;
}

.log-new{color:var(--g);}
.log-alert{color:var(--red);}
</style>
</head>

<body>

<div class="topbar">
  <div>
    <div class="sys-title">RADARSYS</div>
    <div class="temp-big">ENV_TEMP: <span id="tempDisplay">--.-</span>°C</div>
  </div>

  <div class="top-right">
    <div class="alert-box">MODE: TACTICAL</div>
    <div class="alert-box" id="alertBox">STATUS: SECURE</div>
    <a href="export_csv.php" class="ctrl-btn">⬇ EXTRACT LOGS</a>
  </div>
</div>

<div class="radar-area" id="radarArea">
  <canvas id="radarCanvas"></canvas>
</div>

<div class="panel-right">

  <div>
    <div class="p-label">⊹ System Status</div>
    <div class="p-card">
      <div id="sysStatus" class="badge badge-ok">ONLINE</div>
      <div class="meta">
        REFRESH 500ms<br>
        PROTO BT+SERIAL<br>
        RANGE 0°–180°
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
      <div class="rec-id" id="distDisp">--- cm</div>
    </div>
  </div>

  <div>
    <div class="p-label">⊹ Angle Servo</div>
    <div class="p-card">
      <div class="rec-id" id="angleDisp">---°</div>
    </div>
  </div>

  <div>
    <div class="p-label">⊹ Dernières données</div>
    <div class="log" id="lastDataBox" style="height:110px;flex:none;"></div>
  </div>

  <div style="flex:1;min-height:0;">
    <div class="p-label">⊹ Event Log</div>
    <div class="log" id="logBox"></div>
  </div>

</div>

<script>
const canvas = document.getElementById('radarCanvas');
const ctx = canvas.getContext('2d');

let W,H,cx,cy,R;
let sweepAngle = 0;
let sweepSpeed = 1.5;
let prevId = 0;
let hits = [];

function resize(){
  const area = document.getElementById('radarArea');
  W = area.clientWidth;
  R = Math.min(W / 2 - 40, area.clientHeight - 80);
  H = R + 100;
  canvas.width = W;
  canvas.height = H;
  cx = W / 2;
  cy = H - 60;
}
resize();
window.addEventListener('resize', resize);

function pointXY(deg, radius){
  const rad = deg * Math.PI / 180;
  return {
    x: cx + radius * Math.cos(rad),
    y: cy - radius * Math.sin(rad)
  };
}

function distToR(d){
  return Math.min(d / 400, 1) * R;
}

function drawRadar(){
  ctx.clearRect(0,0,W,H);

  // fond radar
  ctx.beginPath();
  ctx.arc(cx,cy,R,Math.PI,Math.PI*2);
  ctx.fillStyle='rgba(0,15,0,.9)';
  ctx.fill();
  ctx.strokeStyle='#0f0';
  ctx.lineWidth=2;
  ctx.stroke();

  // cercles distance
  ctx.strokeStyle='rgba(0,255,0,.3)';
  ctx.fillStyle='rgba(0,255,0,.6)';
  ctx.font="10px 'Share Tech Mono'";

  for(let i=1;i<=4;i++){
    const r=(R/4)*i;
    ctx.beginPath();
    ctx.arc(cx,cy,r,Math.PI,Math.PI*2);
    ctx.stroke();
    ctx.fillText(`${i*100}cm`,cx+r+5,cy-5);
  }

  // angles
  [180,120,80,40,0].forEach(deg=>{
    const p=pointXY(deg,R);
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.lineTo(p.x,p.y);
    ctx.stroke();

    const t=pointXY(deg,R+15);
    ctx.fillText(deg+'°',t.x,t.y);
  });

  // balayage radar
  for(let t=0;t<30;t++){
    const a=sweepAngle-t;
    if(a<0 || a>180) continue;
    const p=pointXY(a,R);
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.lineTo(p.x,p.y);
    ctx.strokeStyle=`rgba(0,255,0,${0.6*(1-t/30)})`;
    ctx.lineWidth=3;
    ctx.stroke();
  }

  // === DETECTION AVEC LIGNE ===
  hits.forEach(h=>{
    const p=pointXY(h.angle,distToR(h.distance));
    const alpha=Math.max(0,1-h.age/180);

    ctx.strokeStyle = h.alert
      ? `rgba(255,0,60,${alpha})`
      : `rgba(0,255,0,${alpha})`;

    ctx.lineWidth = 2;

    // ligne centre -> point
    ctx.beginPath();
    ctx.moveTo(cx,cy);
    ctx.lineTo(p.x,p.y);
    ctx.stroke();

    // point
    ctx.beginPath();
    ctx.arc(p.x,p.y,6,0,Math.PI*2);
    ctx.stroke();

    h.age++;
  });

  // disparition progressive
  hits = hits.filter(h=>h.age < 180);
}

function animate(){
  drawRadar();

  sweepAngle += sweepSpeed;
  if(sweepAngle >= 180){
    sweepAngle = 180;
    sweepSpeed = -1.5;
  }
  if(sweepAngle <= 0){
    sweepAngle = 0;
    sweepSpeed = 1.5;
  }

  requestAnimationFrame(animate);
}
animate();

function addLog(msg, cls){
  const box=document.getElementById('logBox');
  const div=document.createElement('div');
  const t=new Date().toLocaleTimeString();
  div.className=cls;
  div.textContent=`[${t}] ${msg}`;
  box.prepend(div);
  if(box.children.length>40) box.removeChild(box.lastChild);
}

function addLastData(data, alert){
  const box=document.getElementById('lastDataBox');
  const div=document.createElement('div');
  div.className=alert ? 'log-alert' : 'log-new';
  div.textContent=`#${data.id} | ${data.temperature}°C | ${data.distance}cm | ${data.angle}°`;
  box.prepend(div);
  if(box.children.length>8) box.removeChild(box.lastChild);
}

async function fetchData(){
  try{
    const res = await fetch('get_latest_data.php');
    const data = await res.json();

    if(!data.success){
      document.getElementById('sysStatus').textContent='NO DATA';
      document.getElementById('sysStatus').className='badge badge-err';
      return;
    }

    const id=parseInt(data.id);
    const angle=parseInt(data.angle);
    const temp=parseFloat(data.temperature);
    const dist=parseFloat(data.distance);

    const alert = dist > 0 && dist < 20;

    document.getElementById('sysStatus').textContent='ONLINE';
    document.getElementById('sysStatus').className='badge badge-ok';

    document.getElementById('tempDisplay').textContent=temp.toFixed(1);
    document.getElementById('recId').textContent='#'+id;
    document.getElementById('distDisp').textContent=dist.toFixed(1)+' cm';
    document.getElementById('angleDisp').textContent=angle+'°';

    if(alert){
      document.body.classList.add('alert');
      document.getElementById('alertBox').textContent='⚠ OBSTACLE';
    }else{
      document.body.classList.remove('alert');
      document.getElementById('alertBox').textContent='STATUS: SECURE';
    }

    if(id !== prevId){
      sweepAngle = angle;

      hits.push({
        angle:angle,
        distance:dist,
        alert:alert,
        age:0
      });

      addLastData(data, alert);

      if(alert){
        addLog(`OBSTACLE: dist=${dist.toFixed(1)}cm angle=${angle}°`, 'log-alert');
      }else{
        addLog(`SCAN: dist=${dist.toFixed(1)}cm angle=${angle}°`, 'log-new');
      }

      prevId = id;
    }

  }catch(e){
    document.getElementById('sysStatus').textContent='ERR: FETCH';
    document.getElementById('sysStatus').className='badge badge-err';
  }
}

fetchData();
setInterval(fetchData,500);
</script>

</body>
</html>
