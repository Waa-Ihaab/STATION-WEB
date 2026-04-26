<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "sensor";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Erreur de connexion MySQL : " . $conn->connect_error);
}

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=radar_logs.csv');

$output = fopen('php://output', 'w');

fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

fputcsv($output, ['ID', 'Mode', 'Angle', 'Temperature', 'Distance', 'Buzzer'], ';');

$sql = "SELECT id, mode, angle, temperature, distance, buzzer
        FROM sensor_data
        ORDER BY id DESC";

$result = $conn->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['mode'],
            $row['angle'],
            $row['temperature'],
            $row['distance'],
            $row['buzzer']
        ], ';');
    }
}

fclose($output);
$conn->close();
exit;
?>
