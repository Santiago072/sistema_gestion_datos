<?php
/**
 * API: Buscar aprendiz por documento y/o ficha
 * GET /api/buscar_aprendiz.php?documento=XXX&id_ficha=YYY
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AprendizModel.php';

header('Content-Type: application/json; charset=utf-8');

$model      = new AprendizModel(getDB());
$documento  = trim($_GET['documento'] ?? '');
$id_ficha   = trim($_GET['id_ficha']  ?? '');

$rows = $model->buscar($documento, $id_ficha);

echo json_encode($rows, JSON_UNESCAPED_UNICODE);
