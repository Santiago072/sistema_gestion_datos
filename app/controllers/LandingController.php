<?php
/**
 * Controller para la Landing Page Institucional
 * Sistema de Gestión de Datos — SENA
 */
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/models/DashboardModel.php';
require_once dirname(__DIR__) . '/models/ProgramaModel.php';

class LandingController {
    private PDO $db;
    private DashboardModel $dashboardModel;
    private ProgramaModel $programaModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->dashboardModel = new DashboardModel($db);
        $this->programaModel  = new ProgramaModel($db);
    }

    public function index(): void {
        $kpis = [];
        try {
            $kpis = $this->dashboardModel->getKpis(null) ?: [];
        } catch (Throwable $e) {
            // Manejo silencioso si DB estuviese vacía o inicializándose
            $kpis = [
                'total_aprendices_activos' => 0,
                'total_retirados'          => 0,
                'total_juicios_aprobados'  => 0,
                'total_programas'          => 0,
                'total_funcionarios'       => 0,
                'total_competencias'       => 0
            ];
        }

        $programas = [];
        try {
            $programas = $this->programaModel->getAll() ?: [];
        } catch (Throwable $e) {
            $programas = [];
        }

        require dirname(__DIR__) . '/views/landing/index.php';
    }

    public function verDocumento(): void {
        $file = $_GET['file'] ?? '';
        $allowed = [
            'Manual_de_Usuario.md'          => 'Manual de Usuario',
            'documentacion-tecnica.md'       => 'Documentación Técnica',
            'ARQUITECTURA_Y_SEGURIDAD.md'   => 'Arquitectura y Seguridad',
            'DESPLIEGUE_VPS.md'             => 'Guía de Despliegue VPS',
            'Especificacion_Requisitos.md'  => 'Especificación de Requisitos'
        ];

        if (!isset($allowed[$file])) {
            header('Location: ' . BASE_URL . '?module=landing');
            exit();
        }

        $filePath = dirname(__DIR__, 2) . '/docs/' . $file;
        if (!file_exists($filePath)) {
            header('Location: ' . BASE_URL . '?module=landing');
            exit();
        }

        header('Content-Type: text/html; charset=UTF-8');
        $rawContent = file_get_contents($filePath);
        $titleDoc = $allowed[$file];
        require dirname(__DIR__) . '/views/landing/doc_viewer.php';
    }
}
