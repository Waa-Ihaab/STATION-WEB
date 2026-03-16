<?php
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Capteurs</title>
  <link rel="stylesheet" href="style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

  <div class="top-buttons">
    <button class="tab-btn active">Dashboard</button>
  </div>

  <div class="dashboard-container">
    <h1>Station IOT</h1>

    <div class="cards-grid">
      
      <div class="card">
        
        <div class="card-header">
          <h2>Température</h2>
          
          <div class="value-badge" id="tempValue">-- °C</div>
        </div>
        <canvas id="tempChart"></canvas>
      </div>

      <div class="card">
        <div class="card-header">
          <h2>Distance</h2>
          <div class="status-zone">
            <div class="led" id="buzzerLed"></div>
            <span id="distanceValue" class="value-badge">-- cm</span>
          </div>
        </div>
        <canvas id="distanceChart"></canvas>
        <div class="legend">
          <span class="legend-item red"></span> Proche
          <span class="legend-item yellow"></span> Moyen
          <span class="legend-item green"></span> Loin
        </div>
      </div>

      
    </div>
  </div>

<script>
const maxPoints = 20;
const tempLabels = [];
const tempData = [];
const tempIds = [];

const distanceLabels = [];
const distanceData = [];
const distancePointColors = [];
const distanceIds = [];

function getDistanceColor(value) {
  if (value < 5) return 'rgb(220, 53, 69)';      // rouge
  if (value < 15) return 'rgb(255, 193, 7)';     // jaune
  return 'rgb(40, 167, 69)';                     // vert
}

const tempChart = new Chart(document.getElementById('tempChart'), {
  type: 'line',
  data: {
    labels: tempLabels,
    datasets: [{
        label: 'Température (°C)',
        data: tempData,
        borderColor: 'rgb(52, 152, 219)',
        backgroundColor: 'rgba(52, 152, 219, 0.15)',
        tension: 0.35,
        fill: true,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointHitRadius: 20
    }]
  },
  options: {
    responsive: true,
    animation: false,
    plugins: {
      legend: { display: true },
      tooltip: {
        callbacks: {
          label: function(context) {
            const index = context.dataIndex;
            const id = tempIds[index];
            const value = context.parsed.y;
            return `ID: ${id} | Température: ${value.toFixed(2)} °C`;
          }
        }
      }
    },
    scales: {
      x: {
        ticks: {
          display: false
        },
        grid: {
          display: false
        }
      },
      y: {
        min: 0,
        max: 30
      }
    }
  }
});

const distanceChart = new Chart(document.getElementById('distanceChart'), {
  type: 'line',
  data: {
    labels: distanceLabels,
    datasets: [{
        label: 'Distance (cm)',
        data: distanceData,
        borderColor: 'rgb(40, 167, 69)',
        backgroundColor: 'rgba(40, 167, 69, 0.10)',
        tension: 0.35,
        fill: true,
        pointRadius: 5,
        pointHoverRadius: 8,
        pointHitRadius: 20,
        pointBackgroundColor: distancePointColors,
        pointBorderColor: distancePointColors,
        segment: {
            borderColor: ctx => {
            const y = ctx.p1.parsed.y;
            return getDistanceColor(y);
            }
        }
    }]
  },
  options: {
    responsive: true,
    animation: false,
    plugins: {
      legend: { display: true },
      tooltip: {
        callbacks: {
          label: function(context) {
            const index = context.dataIndex;
            const id = distanceIds[index];
            const value = context.parsed.y;
            return `ID: ${id} | Distance: ${value.toFixed(2)} cm`;
          }
        }
      }
    },
    scales: {
      x: {
        ticks: {
          display: false
        },
        grid: {
          display: false
        }
      },
      y: {
        min: 0,
        max: 200
      }
    }
  }
});

function addData(chart, labelsArray, dataArray, label, value, idsArray, idValue, extraColorArray = null, color = null) {
  labelsArray.push(label);
  dataArray.push(value);
  idsArray.push(idValue);

  if (extraColorArray && color) {
    extraColorArray.push(color);
  }

  if (labelsArray.length > maxPoints) {
    labelsArray.shift();
    dataArray.shift();
    idsArray.shift();
    if (extraColorArray) extraColorArray.shift();
  }

  chart.update();
}

async function fetchData() {
  try {
    const response = await fetch('get_latest_data.php');
    const data = await response.json();

    if (!data.success) return;

    const id = parseInt(data.id);
    const temp = parseFloat(data.temperature);
    const distance = parseFloat(data.distance);
    const buzzer = parseInt(data.buzzer);
    const timeLabel = data.created_at;

    document.getElementById('tempValue').textContent = temp.toFixed(2) + ' °C';
    document.getElementById('distanceValue').textContent = distance.toFixed(2) + ' cm';

    const led = document.getElementById('buzzerLed');
    if (buzzer === 1) {
      led.classList.add('on');
    } else {
      led.classList.remove('on');
    }

    if (
      tempLabels.length === 0 ||
      tempLabels[tempLabels.length - 1] !== timeLabel
    ) {
      addData(tempChart, tempLabels, tempData, timeLabel, temp, tempIds, id);

      const color = getDistanceColor(distance);
      addData(distanceChart, distanceLabels, distanceData, timeLabel, distance, distanceIds, id, distancePointColors, color);
    }

  } catch (error) {
    console.error('Erreur fetch:', error);
  }
}

fetchData();
setInterval(fetchData, 1000);
</script>

</body>
</html>