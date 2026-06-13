<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

$db = getDB();
$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    // Lista de todos los trabajos con resumen
    $stmt = $db->query("
        SELECT 
            t.id,
            t.tipo,
            t.estado,
            t.progreso,
            t.resultado,
            t.errores,
            t.creado_en AS created_at,
            t.actualizado_en AS updated_at,
            COUNT(l.id) AS total_errores_log
        FROM trabajos_importacion t
        LEFT JOIN logs_importacion l ON l.job_id = t.id
        GROUP BY t.id
        ORDER BY t.id DESC
        LIMIT 100
    ");
    $jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($jobs as &$job) {
        if (!empty($job['resultado'])) {
            $job['resultado'] = json_decode($job['resultado'], true);
        }
    }

    echo json_encode($jobs);

} elseif ($action === 'logs') {
    // Logs de errores de un job específico
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['error' => 'ID requerido']); exit; }

    $stmt = $db->prepare("SELECT fila, mensaje_error, fecha_creacion AS created_at FROM logs_importacion WHERE job_id = :id ORDER BY id ASC");
    $stmt->execute([':id' => $id]);
    echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));

} elseif ($action === 'delete') {
    // Eliminar un job y sus logs
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) { http_response_code(400); echo json_encode(['ok' => false]); exit; }

    $db->prepare("DELETE FROM logs_importacion WHERE job_id = :id")->execute([':id' => $id]);
    $db->prepare("DELETE FROM trabajos_importacion WHERE id = :id")->execute([':id' => $id]);
    echo json_encode(['ok' => true]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Acción no válida']);
}
