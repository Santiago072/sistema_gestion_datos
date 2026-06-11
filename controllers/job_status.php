<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/queue/JobQueue.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Job ID requerido']);
    exit;
}

$db = getDB();
$queue = new \Services\Queue\JobQueue($db);

$status = $queue->getStatus((int)$_GET['id']);

if (!$status) {
    http_response_code(404);
    echo json_encode(['error' => 'Job no encontrado']);
    exit;
}

// Convert JSON string to object if present
if (!empty($status['resultado'])) {
    $status['resultado'] = json_decode($status['resultado'], true);
}

// Retrieve any logged errors from logs_importacion
$stmt = $db->prepare("SELECT fila, mensaje_error FROM logs_importacion WHERE job_id = :id ORDER BY id ASC");
$stmt->execute([':id' => $status['id']]);
$status['errores_log'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($status);
