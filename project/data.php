<?php
header('Content-Type: application/json');

try {
    $db = new PDO('sqlite:' . __DIR__ . '/sensor_data.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $limit = $_GET['limit'] ?? '50';

    if ($limit === 'all') {
        $stmt = $db->query("
            SELECT id, temperature, distance, created_at
            FROM sensor_data
            ORDER BY id ASC
        ");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $limit = (int)$limit;

        if ($limit <= 0) {
            $limit = 50;
        }

        $stmt = $db->prepare("
            SELECT id, temperature, distance, created_at
            FROM sensor_data
            ORDER BY id DESC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rows = array_reverse($rows);
    }

    echo json_encode($rows);

} catch (Exception $e) {
    echo json_encode([
        "error" => $e->getMessage()
    ]);
}
?>