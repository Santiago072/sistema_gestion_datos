<?php
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/models/DashboardModel.php';
require_once dirname(__DIR__) . '/models/JuiciosModel.php';
require_once dirname(__DIR__) . '/models/RetiradosModel.php';
require_once dirname(__DIR__) . '/models/ProgramaModel.php';

class DashboardController {
    private PDO $db;
    private DashboardModel $dashboardModel;
    private JuiciosModel $juiciosModel;
    private RetiradosModel $retiradosModel;
    private ProgramaModel $programaModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->dashboardModel = new DashboardModel($db);
        $this->juiciosModel   = new JuiciosModel($db);
        $this->retiradosModel = new RetiradosModel($db);
        $this->programaModel  = new ProgramaModel($db);
    }

    public function index(): void {
        $programas = $this->programaModel->getAll();
        require dirname(__DIR__) . '/views/dashboard/index.php';
    }

    public function ajaxKpis(): void {
        verificar_rate_limit(60, 60, 'dashboard_kpis');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->dashboardModel->getKpis($idFicha));
    }

    public function ajaxAprendicesFormacion(): void {
        verificar_rate_limit(60, 60, 'aprendices_formacion');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->juiciosModel->getAprendicesFormacion($idFicha));
    }

    public function ajaxComparativaJuicios(): void {
        verificar_rate_limit(60, 60, 'comparativa_juicios');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->juiciosModel->getComparativaJuicios($idFicha));
    }

    public function ajaxRetiradosCompetencia(): void {
        verificar_rate_limit(60, 60, 'retirados_competencia');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->retiradosModel->getRetiradosPorCompetencia($idFicha));
    }

    public function ajaxAuditoriaFuncionarios(): void {
        verificar_rate_limit(60, 60, 'auditoria_funcionarios');
        $idFicha = !empty($_GET['id_ficha']) ? (int)$_GET['id_ficha'] : null;
        jsonResponse($this->juiciosModel->getAuditoriaFuncionarios($idFicha));
    }

    public function ajaxFiltroAvanzado(): void {
        verificar_rate_limit(40, 60, 'filtro_avanzado');
        $filters = [
            'programa'    => $_GET['programa'] ?? null,
            'documento'   => $_GET['documento'] ?? null,
            'estado'      => $_GET['estado'] ?? null,
            'competencia' => $_GET['competencia'] ?? null,
            'resultado'   => $_GET['resultado'] ?? null,
            'tipo_juicio' => $_GET['tipo_juicio'] ?? null,
        ];

        $isCsv = (isset($_GET['format']) && $_GET['format'] === 'csv');

        if ($isCsv) {
            $rows = $this->juiciosModel->getFiltroAvanzadoCsv($filters);
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="filtro_juicios_' . date('Ymd') . '.csv"');
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // UTF-8 BOM
            if (!empty($rows)) fputcsv($out, array_keys($rows[0]), ';');
            foreach ($rows as $r) fputcsv($out, $r, ';');
            fclose($out);
            exit;
        } else {
            $page  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
            $limit = isset($_GET['limit']) ? max(1, (int)$_GET['limit']) : 15;
            $result = $this->juiciosModel->getFiltroAvanzado($filters, $page, $limit);
            jsonResponse($result);
        }
    }
}
