<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/models/FasesModel.php';
require_once dirname(__DIR__) . '/models/ProgramaModel.php';
require_once dirname(__DIR__) . '/models/DashboardFasesRepository.php';
require_once dirname(__DIR__) . '/services/import/FasesImportService.php';

class FasesController {
    private PDO $db;
    private FasesModel $fasesModel;
    private ProgramaModel $programaModel;
    private DashboardFasesRepository $dashboardFasesRepo;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->fasesModel = new FasesModel($db);
        $this->programaModel = new ProgramaModel($db);
        $this->dashboardFasesRepo = new DashboardFasesRepository($db);
    }

    public function index(): void {
        $programas = $this->programaModel->getAll();
        require dirname(__DIR__) . '/views/fases/index.php';
    }

    public function dashboard(): void {
        $programas = $this->programaModel->getAll();
        require dirname(__DIR__) . '/views/fases/dashboard.php';
    }

    public function ajaxCumplimiento(): void {
        verificar_rate_limit(60, 60, 'cumplimiento_fases');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->dashboardFasesRepo->getCumplimiento($idFicha));
    }

    public function ajaxDetalle(): void {
        verificar_rate_limit(60, 60, 'detalle_fases');
        $idFase  = !empty($_GET['id_fase']) ? (int)$_GET['id_fase'] : null;
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->dashboardFasesRepo->getDetalleFases($idFase, $idFicha));
    }

    public function ajaxCrud(): void {
        verificar_rate_limit(60, 60, 'fases_crud');
        $action = $_GET['subaction'] ?? $_POST['subaction'] ?? $_GET['action_type'] ?? $_POST['action_type'] ?? $_GET['action'] ?? $_POST['action'] ?? 'list';

        try {
            switch ($action) {
                case 'list':
                case 'list_relaciones':
                    $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
                    jsonResponse($this->fasesModel->listRelaciones($idFicha));
                    break;

                case 'list_fases':
                    $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
                    jsonResponse($this->fasesModel->listFases($idFicha));
                    break;

                case 'list_actividades':
                    $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
                    $idFase = !empty($_GET['id_fase']) ? (int)$_GET['id_fase'] : null;
                    $nombreFase = $_GET['nombre_fase'] ?? null;
                    jsonResponse($this->fasesModel->listActividades($idFase, $nombreFase, $idFicha));
                    break;

                case 'create_fase':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $this->fasesModel->createFase($data);
                    jsonResponse(['id' => $id, 'ok' => true]);
                    break;

                case 'update_fase':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->fasesModel->updateFase($data);
                    jsonResponse(['ok' => true]);
                    break;

                case 'delete_fase':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->fasesModel->deleteFase((int)$data['id_fase']);
                    jsonResponse(['ok' => true]);
                    break;

                case 'create_actividad':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $id = $this->fasesModel->createActividad($data);
                    jsonResponse(['id' => $id, 'ok' => true]);
                    break;

                case 'delete_actividad':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->fasesModel->deleteActividad((int)$data['id_actividad']);
                    jsonResponse(['ok' => true]);
                    break;

                case 'list_proyectos':
                    jsonResponse($this->fasesModel->listProyectos());
                    break;

                case 'get_proyecto_detalle':
                    $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
                    jsonResponse($this->fasesModel->getProyectoDetalle($idFicha));
                    break;

                case 'delete_proyecto':
                    $data = json_decode(file_get_contents('php://input'), true);
                    $idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;
                    if ($idFicha) {
                        $this->fasesModel->deleteProyecto($idFicha);
                        jsonResponse(['ok' => true]);
                    } else {
                        jsonResponse(['ok' => false, 'error' => 'ID de ficha no proporcionado'], 400);
                    }
                    break;

                default:
                    jsonResponse(['error' => 'Acción no permitida'], 400);
            }
        } catch (Exception $e) {
            jsonResponse(['error' => $e->getMessage()], 500);
        }
    }

    public function ajaxUploadPdf(): void {
        requireMethod('POST');
        verificar_rate_limit(15, 60, 'upload_pdf');

        if (empty($_FILES['pdf']) || $_FILES['pdf']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => 'No se recibió archivo PDF válido'], 400);
        }

        $file = $_FILES['pdf'];
        if (strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
            jsonResponse(['error' => 'El archivo debe ser PDF'], 400);
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            jsonResponse(['error' => 'El archivo no debe superar 10MB'], 400);
        }

        $tmpDir = dirname(__DIR__, 2) . '/tmp_pdf';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $tmpFile = $tmpDir . '/pdf_' . uniqid('', true) . '.pdf';
        if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
            jsonResponse(['error' => 'No se pudo guardar el archivo PDF temporalmente'], 500);
        }

        $isWindows = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
        $pythonCmd = 'python3';
        if ($isWindows) {
            $venvPy = dirname(__DIR__, 2) . '/.venv/Scripts/python.exe';
            $winPy  = 'C:\Users\Usuario\AppData\Local\Programs\Python\Python313\python.exe';
            if (file_exists($venvPy)) {
                $pythonCmd = $venvPy;
            } elseif (file_exists($winPy)) {
                $pythonCmd = $winPy;
            } else {
                $pythonCmd = 'python';
            }
        }

        $scriptPath = __DIR__ . '/scripts/extract_pdf.py';
        $descriptors = [
            0 => ["pipe", "r"],
            1 => ["pipe", "w"],
            2 => ["pipe", "w"]
        ];

        $cmdArray = [$pythonCmd, '-X', 'utf8', $scriptPath, $tmpFile];
        $process  = proc_open($cmdArray, $descriptors, $pipes);
        $stdout   = '';
        $stderr   = '';

        if (is_resource($process)) {
            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]);
            $stderr = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        }

        @unlink($tmpFile);
        $response = trim($stdout);

        if (empty($response)) {
            $errDetalle = !empty($stderr) ? $stderr : 'No se recibió respuesta del script de extracción Python';
            jsonResponse(['error' => 'Error en la extracción del PDF: ' . mb_convert_encoding($errDetalle, 'UTF-8', 'UTF-8, CP1252, ISO-8859-1')], 500);
        }

        $posStart = strpos($response, '{');
        $posEnd   = strrpos($response, '}');
        if ($posStart !== false && $posEnd !== false && $posEnd > $posStart) {
            $response = substr($response, $posStart, $posEnd - $posStart + 1);
        }

        $data = json_decode($response, true);
        if (!$data || !is_array($data)) {
            jsonResponse(['error' => 'Error al decodificar JSON de extracción: ' . mb_substr($response, 0, 300)], 500);
        }

        if (!empty($data['error'])) {
            jsonResponse(['error' => $data['error']], 422);
        }

        $registros = $data['registros'] ?? [];
        $mapped = [
            'fases'        => $data['fases'] ?? [],
            'actividades'  => $data['actividades'] ?? [],
            'resultados'   => [],
            'competencias' => [],
            'registros'    => $registros,
            'resumen'      => $data['resumen'] ?? [],
        ];

        $resMap = [];
        foreach ($registros as $reg) {
            $key = $reg['resultado_codigo'] ?: $reg['resultado_nombre'];
            if (!empty($reg['resultado_nombre']) && !isset($resMap[$key])) {
                $resMap[$key] = [
                    'codigo' => $reg['resultado_codigo'],
                    'nombre' => $reg['resultado_nombre'],
                ];
            }
        }
        $mapped['resultados'] = array_values($resMap);

        $compMap = [];
        foreach ($registros as $reg) {
            $key = $reg['competencia_codigo'] ?: $reg['competencia'];
            if (!empty($reg['competencia']) && !isset($compMap[$key])) {
                $compMap[$key] = [
                    'codigo' => $reg['competencia_codigo'],
                    'nombre' => $reg['competencia'],
                ];
            }
        }
        $mapped['competencias'] = array_values($compMap);

        $mapped['resumen']['total_actividades']  = count($mapped['actividades']);
        $mapped['resumen']['total_resultados']   = count($mapped['resultados']);
        $mapped['resumen']['total_competencias'] = count($mapped['competencias']);

        jsonResponse([
            'ok'               => true,
            'total_caracteres' => array_sum(array_map('strlen', array_column($registros, 'actividad'))) + 100,
            'total_paginas'    => 0,
            'texto_extraido'   => 'Extraído con Python pdfplumber (estructura de tabla preservada)',
            'datos_extraidos'  => [
                'informacion_basica' => $data['informacion_basica'] ?? [],
                'fases'              => $data['fases'] ?? [],
                'registros'          => $registros,
                'resumen'            => $data['resumen'] ?? [],
            ],
            'datos_mapeados'  => $mapped,
        ]);
    }

    public function ajaxImportPdf(): void {
        requireMethod('POST');
        verificar_rate_limit(20, 60, 'import_pdf');

        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            jsonResponse(['error' => 'JSON inválido o vacío'], 400);
        }

        $idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;
        $service = new \Services\Import\FasesImportService($this->db);
        $results = $service->import($data, $idFicha);

        if ($results['ok']) {
            jsonResponse($results);
        } else {
            jsonResponse([
                'ok'    => false,
                'error' => implode(', ', $results['errores']) ?: 'Error desconocido al importar',
            ], 500);
        }
    }
}
