<?php
/**
 * UPLOAD MASIVO DESDE EXCEL (.xlsx) o CSV
 * ─────────────────────────────────────────
 * El archivo Excel de Sofia Plus puede tener estas columnas
 * (se detectan por encabezado, sin importar el orden):
 *
 * APRENDICES:
 *   documento | tipo_documento | nombres | apellidos | estado | ficha | programa
 *
 * COMPETENCIAS / RESULTADOS / JUICIOS (hoja "Juicios" o columnas adicionales):
 *   competencia | resultado_aprendizaje | tipo_juicio | fecha_juicio | documento_funcionario | nombre_funcionario
 *
 * Si el archivo solo tiene datos de aprendices → inserta solo en aprendices+programas
 * Si también tiene competencias/juicios → distribuye a todas las tablas
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/import/ImportAdapterInterface.php';
require_once __DIR__ . '/../services/import/ExcelAdapter.php';
require_once __DIR__ . '/../services/import/CsvAdapter.php';

use Services\Import\ExcelAdapter;
use Services\Import\CsvAdapter;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo'])) {
    echo json_encode(['error' => true, 'message' => 'No se recibió archivo']); exit;
}

$file = $_FILES['archivo'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['error' => true, 'message' => 'Solo se permiten archivos .xlsx, .xls o .csv']); exit;
}

$db = getDB();

require_once __DIR__ . '/../services/queue/JobQueue.php';
use Services\Queue\JobQueue;

$tmpDir = __DIR__ . '/../tmp_uploads';
if (!is_dir($tmpDir)) {
    @mkdir($tmpDir, 0755, true);
}

$tmpFile = $tmpDir . '/upload_' . uniqid() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    echo json_encode(['error' => true, 'message' => 'No se pudo guardar el archivo temporal']); exit;
}

$queue = new JobQueue($db);
$jobId = $queue->enqueue('excel_aprendices', realpath($tmpFile));

echo json_encode([
    'ok'      => true,
    'job_id'  => $jobId,
    'message' => 'Archivo encolado exitosamente para procesamiento en segundo plano.'
]);
