<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/FasesModel.php';

header('Content-Type: application/json; charset=utf-8');

verificar_rate_limit(40, 60, 'fases_crud');

$model = new FasesModel(getDB());
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
            echo json_encode($model->listRelaciones($idFicha), JSON_UNESCAPED_UNICODE);
            break;

        case 'list_fases':
            $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
            echo json_encode($model->listFases($idFicha), JSON_UNESCAPED_UNICODE);
            break;

        case 'list_actividades':
            $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
            $idFase = !empty($_GET['id_fase']) ? (int)$_GET['id_fase'] : null;
            $nombreFase = $_GET['nombre_fase'] ?? null;
            echo json_encode($model->listActividades($idFase, $nombreFase, $idFicha), JSON_UNESCAPED_UNICODE);
            break;

        case 'create_fase':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $model->createFase($data);
            echo json_encode(['id' => $id, 'ok' => true]);
            break;

        case 'update_fase':
            $data = json_decode(file_get_contents('php://input'), true);
            $model->updateFase($data);
            echo json_encode(['ok' => true]);
            break;

        case 'delete_fase':
            $data = json_decode(file_get_contents('php://input'), true);
            $model->deleteFase((int)$data['id_fase']);
            echo json_encode(['ok' => true]);
            break;

        case 'create_actividad':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $model->createActividad($data);
            echo json_encode(['id' => $id, 'ok' => true]);
            break;

        case 'delete_actividad':
            $data = json_decode(file_get_contents('php://input'), true);
            $model->deleteActividad((int)$data['id_actividad']);
            echo json_encode(['ok' => true]);
            break;

        case 'create_relation':
            $data = json_decode(file_get_contents('php://input'), true);
            $id = $model->createRelacion($data);
            echo json_encode(['id' => $id, 'ok' => true]);
            break;

        case 'delete_relation':
            $data = json_decode(file_get_contents('php://input'), true);
            $model->deleteRelacion((int)$data['id']);
            echo json_encode(['ok' => true]);
            break;

        case 'delete_proyecto':
            $data = json_decode(file_get_contents('php://input'), true);
            $model->deleteProyecto((int)$data['id_ficha']);
            echo json_encode(['ok' => true]);
            break;

        case 'list_proyectos':
            echo json_encode($model->listProyectos(), JSON_UNESCAPED_UNICODE);
            break;

        case 'get_proyecto_detalle':
            $idFicha = (int)$_GET['id_ficha'];
            echo json_encode($model->getProyectoDetalle($idFicha), JSON_UNESCAPED_UNICODE);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Acción no reconocida']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
