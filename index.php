<?php
/**
 * index.php — Front Controller / Router Principal
 * Sistema de Gestión de Juicios Evaluativos SENA
 *
 * Único punto de entrada del sistema. Lee los parámetros ?module= y ?action=
 * de la URL, valida, instancia el controlador correspondiente y despacha la petición.
 */

// ── Errores: solo al log, NUNCA al navegador (producción) ─────────────────────
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
ini_set('log_errors', '1');

$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) {
    @mkdir($logDir, 0755, true);
}
$logFile = $logDir . '/php_errors.log';
ini_set('error_log', $logFile);

// ── Manejador Global de Excepciones ──────────────────────────────────────────
set_exception_handler(function (Throwable $e) use ($logFile) {
    $timestamp = date('Y-m-d H:i:s');
    $logMsg = "[{$timestamp}] Uncaught Exception: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine() . "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    @file_put_contents($logFile, $logMsg, FILE_APPEND);
    error_log((string)$e);

    http_response_code(500);
    $esAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
              || (!empty($_GET['action']) && $_GET['action'] !== 'index');

    if ($esAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => true, 'message' => 'Error interno del servidor: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    } else {
        echo "<!DOCTYPE html><html lang='es'><head><meta charset='UTF-8'><title>500 - Error Interno</title><style>body{font-family:system-ui,-apple-system,sans-serif;background:#0d1117;color:#c9d1d9;padding:40px;text-align:center;}h1{color:#ef4444;}a{color:#39A900;text-decoration:none;font-weight:600;}</style></head><body><h1>500 — Error Interno</h1><p>Ocurrió un problema inesperado al procesar su solicitud.</p><p><a href='?module=dashboard'>Volver al Dashboard</a></p></body></html>";
    }
    exit();
});

// ── Carga de Configuración y Seguridad ───────────────────────────────────────
require_once __DIR__ . '/config/url_config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/seguridad.php';

// Emitir cabeceras HTTP de seguridad
enviar_cabeceras_seguridad();

// ── Controladores ────────────────────────────────────────────────────────────
require_once __DIR__ . '/controllers/DashboardController.php';
require_once __DIR__ . '/controllers/AprendizController.php';
require_once __DIR__ . '/controllers/CargaController.php';
require_once __DIR__ . '/controllers/FasesController.php';
require_once __DIR__ . '/controllers/ProgramaController.php';

// ── Obtener Conexión PDO ─────────────────────────────────────────────────────
$db = getDB();

// ── Enrutamiento por Módulo y Acción ─────────────────────────────────────────
$module = $_GET['module'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';

switch ($module) {
    case 'dashboard':
        $ctrl = new DashboardController($db);
        switch ($action) {
            case 'kpis':
                $ctrl->ajaxKpis();
                break;
            case 'aprendices_formacion':
                $ctrl->ajaxAprendicesFormacion();
                break;
            case 'comparativa_juicios':
                $ctrl->ajaxComparativaJuicios();
                break;
            case 'retirados_competencia':
                $ctrl->ajaxRetiradosCompetencia();
                break;
            case 'auditoria_funcionarios':
                $ctrl->ajaxAuditoriaFuncionarios();
                break;
            case 'filtro_avanzado':
                $ctrl->ajaxFiltroAvanzado();
                break;
            case 'index':
            default:
                $ctrl->index();
                break;
        }
        break;

    case 'consulta':
    case 'aprendices':
        $ctrl = new AprendizController($db);
        switch ($action) {
            case 'buscar':
                $ctrl->ajaxBuscar();
                break;
            case 'avance':
            case 'avance_competencia':
                $ctrl->ajaxAvanceCompetencia();
                break;
            case 'seguimiento':
            case 'seguimiento_resultado':
                $ctrl->ajaxSeguimientoResultado();
                break;
            case 'eliminar':
                $ctrl->ajaxEliminar();
                break;
            case 'index':
            case 'consulta':
            default:
                $ctrl->consulta();
                break;
        }
        break;

    case 'carga':
    case 'carga_masiva':
        $ctrl = new CargaController($db);
        switch ($action) {
            case 'upload_excel':
            case 'upload':
                $ctrl->ajaxUploadExcel();
                break;
            case 'index':
            default:
                $ctrl->index();
                break;
        }
        break;

    case 'eliminacion':
    case 'eliminacion_masiva':
    case 'programas':
        $ctrl = new ProgramaController($db);
        switch ($action) {
            case 'eliminar_programa':
                $ctrl->ajaxEliminarPrograma();
                break;
            case 'eliminar_aprendiz':
                $ctrl->ajaxEliminarAprendiz();
                break;
            case 'index':
            case 'eliminacion':
            default:
                $ctrl->eliminacion();
                break;
        }
        break;

    case 'fases':
    case 'proyectos':
    case 'dashboard_fases':
        $ctrl = new FasesController($db);
        switch ($action) {
            case 'dashboard':
                $ctrl->dashboard();
                break;
            case 'upload_pdf':
                $ctrl->ajaxUploadPdf();
                break;
            case 'import_pdf':
                $ctrl->ajaxImportPdf();
                break;
            case 'cumplimiento':
            case 'cumplimiento_fases':
                $ctrl->ajaxCumplimiento();
                break;
            case 'detalle':
            case 'detalle_fases':
                $ctrl->ajaxDetalle();
                break;
            case 'crud':
            case 'fases_crud':
                $ctrl->ajaxCrud();
                break;
            case 'index':
            default:
                if ($module === 'dashboard_fases') {
                    $ctrl->dashboard();
                } else {
                    $ctrl->index();
                }
                break;
        }
        break;

    default:
        // Si el módulo no existe, redirigir al Dashboard principal
        header('Location: ' . BASE_URL . '?module=dashboard');
        exit();
}
