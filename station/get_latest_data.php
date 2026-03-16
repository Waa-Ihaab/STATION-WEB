<?php
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$password = "";
$database = "sensor";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    echo json_encode([
        "success" => false,
        "message" => "Connexion échouée : " . $conn->connect_error
    ]);
    exit;
}

$sql = "SELECT id, temperature, distance, buzzer, created_at
        FROM sensor_data
        ORDER BY id DESC
        LIMIT 1";

$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode([
        "success" => true,
        "id" => $row["id"],
        "temperature" => $row["temperature"],
        "distance" => $row["distance"],
        "buzzer" => $row["buzzer"],
        "created_at" => $row["created_at"]
    ]);
} else {
    echo json_encode([
        "success" => false,
        "message" => "Aucune donnée trouvée"
    ]);
}

$conn->close();
?>