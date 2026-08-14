<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/models/AprendizModel.php';
require_once dirname(__DIR__) . '/models/JuiciosModel.php';
require_once dirname(__DIR__) . '/models/ProgramaModel.php';

class AprendizController {
    private PDO $db;
    private AprendizModel $aprendizModel;
    private JuiciosModel $juiciosModel;
    private ProgramaModel $programaModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->aprendizModel = new AprendizModel($db);
        $this->juiciosModel  = new JuiciosModel($db);
        $this->programaModel = new ProgramaModel($db);
    }

    public function consulta(): void {
        $programas = $this->programaModel->getAll();
        $aprendices = $this->aprendizModel->getAllWithPrograma();
        require dirname(__DIR__) . '/views/aprendices/consulta.php';
    }

    public function ajaxBuscar(): void {
        verificar_rate_limit(60, 60, 'buscar_aprendiz');
        $documento = sanitizar_entrada($_GET['documento'] ?? '');
        $idFicha   = sanitizar_entrada($_GET['id_ficha'] ?? '');
        jsonResponse($this->aprendizModel->buscar($documento, $idFicha));
    }

    public function ajaxAvanceCompetencia(): void {
        verificar_rate_limit(60, 60, 'avance_competencia');
        $documento = !empty($_GET['documento']) ? sanitizar_entrada($_GET['documento']) : null;
        $programa  = !empty($_GET['programa']) ? (int)$_GET['programa'] : null;
        jsonResponse($this->juiciosModel->getAvanceCompetencia($documento, $programa));
    }

    public function ajaxSeguimientoResultado(): void {
        verificar_rate_limit(60, 60, 'seguimiento_resultado');
        $documento = !empty($_GET['documento']) ? sanitizar_entrada($_GET['documento']) : null;
        jsonResponse($this->juiciosModel->getSeguimiento($documento));
    }

    public function ajaxEliminar(): void {
        requireMethod('POST');
        verificar_rate_limit(30, 60, 'eliminar_aprendiz');
        $documento = sanitizar_entrada($_POST['documento'] ?? '');
        $idFicha   = sanitizar_entrada($_POST['id_ficha'] ?? '');

        if (!$documento || !$idFicha) {
            jsonResponse(['success' => false, 'message' => 'Documento e id_ficha son obligatorios'], 400);
        }

        if (!$this->aprendizModel->existeEnFicha($documento, $idFicha)) {
            jsonResponse(['success' => false, 'message' => 'No se encontró el aprendiz en esa ficha'], 404);
        }

        try {
            $this->aprendizModel->eliminar($documento, $idFicha);
            jsonResponse([
                'success' => true,
                'message' => "Aprendiz $documento eliminado correctamente de la ficha $idFicha"
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
