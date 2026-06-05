<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/DashboardModel.php';

header('Content-Type: application/json; charset=utf-8');

$model = new DashboardModel(getDB());
$prog = !empty($_GET['programa']) ? (int)$_GET['programa'] : null;

echo json_encode($model->getKpis($prog), JSON_UNESCAPED_UNICODE);
