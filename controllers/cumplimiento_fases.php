<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DashboardFasesRepository.php';

header('Content-Type: application/json; charset=utf-8');

$model = new DashboardFasesRepository(getDB());
$id_ficha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;

echo json_encode($model->getCumplimiento($id_ficha), JSON_UNESCAPED_UNICODE);
