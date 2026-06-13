<?php
/**
 * UPLOAD MASIVO DESDE EXCEL (.xlsx/.xls) o CSV
 * ─────────────────────────────────────────────
 * Versión asíncrona: Encola el archivo y lanza el worker.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/queue/JobQueue.php';

use Services\Queue\JobQueue;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo'])) {
    echo json_encode(['error' => true, 'message' => 'No se recibió archivo']); exit;
}

$file = $_FILES['archivo'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['error' => true, 'message' => 'Solo se permiten archivos .xlsx, .xls o .csv']); exit;
}

// ── Guardar archivo temporal ──────────────────────────────────────────────────
$tmpDir = __DIR__ . '/../tmp_uploads';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

$tmpFile = $tmpDir . '/upload_' . uniqid() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    echo json_encode(['error' => true, 'message' => 'No se pudo guardar el archivo temporal']); exit;
}

// ── Encolar trabajo ───────────────────────────────────────────────────────────
$db = getDB();
$queue = new JobQueue($db);
$jobId = $queue->enqueue('excel_aprendices', $tmpFile);

// ── Lanzar worker en segundo plano (Windows) ──────────────────────────────────
$workerScript = __DIR__ . '/worker.php';
pclose(popen("start /B php \"$workerScript\" > NUL 2>&1", "r"));

echo json_encode([
    'ok' => true,
    'job_id' => $jobId,
    'message' => 'Archivo encolado correctamente'
]);
