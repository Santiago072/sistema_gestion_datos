<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/JuiciosModel.php';

header('Content-Type: application/json; charset=utf-8');

$model = new JuiciosModel(getDB());
$prog = !empty($_GET['programa']) ? (int)$_GET['programa'] : null;

echo json_encode($model->getAuditoriaFuncionarios($prog), JSON_UNESCAPED_UNICODE);