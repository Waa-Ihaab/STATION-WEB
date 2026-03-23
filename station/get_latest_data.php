<?php
header('Content-Type: application/json; charset=utf-8');

$host = "localhost";
$user = "root";
$pass = "";
$db   = "sensor";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Connexion MySQL échouée"
    ]);
    exit;
}

$sql = "SELECT id, angle, temperature, distance, buzzer 
        FROM sensor_data 
        ORDER BY id DESC 
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();

    echo json_encode([
        "success" => true,
        "id" => (int)$row["id"],
        "angle" => (int)$row["angle"],
        "temperature" => (float)$row["temperature"],
        "distance" => (float)$row["distance"],
        "buzzer" => (int)$row["buzzer"]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Aucune donnée trouvée"
    ]);
}

$conn->close();
?>