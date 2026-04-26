<?php
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "radar_station";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Connexion MySQL échouée"
    ]);
    exit;
}

$sql = "SELECT id, mode, angle, temperature, distance
        FROM radar_scans
        ORDER BY id DESC
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "id" => (int)$row["id"],
        "mode" => (int)$row["mode"],
        "angle" => (int)$row["angle"],
        "temperature" => (float)$row["temperature"],
        "distance" => (float)$row["distance"],
        "buzzer" => 0
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Aucune donnée trouvée"
    ]);
}

$conn->close();
?>
