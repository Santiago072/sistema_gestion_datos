<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/AprendizModel.php';

header('Content-Type: application/json; charset=utf-8');

$model = new AprendizModel(getDB());
$prog = $_GET['programa'] ?? null;

echo json_encode($model->getFormacion($prog), JSON_UNESCAPED_UNICODE);
