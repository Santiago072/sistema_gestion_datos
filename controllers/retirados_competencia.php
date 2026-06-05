<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/RetiradosModel.php';

header('Content-Type: application/json; charset=utf-8');

$model = new RetiradosModel(getDB());
$prog = !empty($_GET['programa']) ? (int)$_GET['programa'] : null;

echo json_encode(['survival' => $model->getSurvivalData($prog)], JSON_UNESCAPED_UNICODE);