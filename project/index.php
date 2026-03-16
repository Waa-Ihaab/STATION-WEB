<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Dashboard</title>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        body{
            font-family: Arial, sans-serif;
            margin:30px;
            background:#f5f7fb;
        }

        h1{
            margin-bottom:10px;
        }

        .card{
            background:white;
            border-radius:12px;
            padding:20px;
            margin-bottom:20px;
            box-shadow:0 2px 10px rgba(0,0,0,0.08);
        }

        .grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
        }

        canvas{
            width:100% !important;
            height:400px !important;
        }

        .latest{
            font-size:18px;
            line-height:1.8;
        }

        .toolbar{
            display:flex;
            align-items:center;
            gap:12px;
            margin-bottom:20px;
        }

        select{
            padding:8px 12px;
            border-radius:8px;
            border:1px solid #ccc;
            font-size:16px;
        }
    </style>
</head>

<body>

<h1>Sensor Dashboard</h1>

<div class="toolbar">
    <label for="limitSelect"><strong>Show:</strong></label>
    <select id="limitSelect">
        <option value="10">Last 10</option>
        <option value="50" selected>Last 50</option>
        <option value="100">Last 100</option>
        <option value="all">All</option>
    </select>
</div>

<div class="card latest" id="latestData">
    Loading latest data...
</div>

<div class="grid">
    <div class="card">
        <h2>Temperature Chart</h2>
        <canvas id="tempChart"></canvas>
    </div>

    <div class="card">
        <h2>Distance Chart</h2>
        <canvas id="distanceChart"></canvas>
    </div>
</div>

<script>
    let tempChart = null;
    let distanceChart = null;

    async function loadData() {
        const limit = document.getElementById("limitSelect").value;

        const response = await fetch('data.php?limit=' + encodeURIComponent(limit) + '&t=' + Date.now());
        const data = await response.json();

        if (data.error) {
            document.getElementById("latestData").innerHTML = "Error: " + data.error;
            return;
        }

        if (!data || data.length === 0) {
            document.getElementById("latestData").innerHTML = "No data";
            return;
        }

        const labels = data.map(item => item.created_at);
        const temperatures = data.map(item => item.temperature);
        const distances = data.map(item => item.distance);

        const last = data[data.length - 1];

        document.getElementById("latestData").innerHTML = `
            <strong>Latest Data</strong><br>
            Temperature: ${last.temperature} °C<br>
            Distance: ${last.distance} cm<br>
            Time: ${last.created_at}
        `;

        if (!tempChart) {
            tempChart = new Chart(
                document.getElementById("tempChart"),
                {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "Temperature (°C)",
                                data: temperatures,
                                borderWidth: 2,
                                tension: 0.2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 8
                                }
                            }
                        }
                    }
                }
            );

            distanceChart = new Chart(
                document.getElementById("distanceChart"),
                {
                    type: "line",
                    data: {
                        labels: labels,
                        datasets: [
                            {
                                label: "Distance (cm)",
                                data: distances,
                                borderWidth: 2,
                                tension: 0.2
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            x: {
                                ticks: {
                                    maxTicksLimit: 8
                                }
                            }
                        }
                    }
                }
            );
        } else {
            tempChart.data.labels = labels;
            tempChart.data.datasets[0].data = temperatures;
            tempChart.update();

            distanceChart.data.labels = labels;
            distanceChart.data.datasets[0].data = distances;
            distanceChart.update();
        }
    }

    document.getElementById("limitSelect").addEventListener("change", loadData);

    loadData();
    setInterval(loadData, 3000);
</script>

</body>
</html>