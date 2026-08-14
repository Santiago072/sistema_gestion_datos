<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/models/ProgramaModel.php';
require_once dirname(__DIR__) . '/models/AprendizModel.php';

class ProgramaController {
    private PDO $db;
    private ProgramaModel $programaModel;
    private AprendizModel $aprendizModel;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->programaModel = new ProgramaModel($db);
        $this->aprendizModel = new AprendizModel($db);
    }

    public function eliminacion(): void {
        $programas = $this->programaModel->getAll();
        require dirname(__DIR__) . '/views/eliminacion/index.php';
    }

    public function ajaxEliminarPrograma(): void {
        requireMethod('POST');
        verificar_rate_limit(20, 60, 'eliminar_programa');
        $idFicha = $_POST['id_ficha'] ?? '';

        if (empty($idFicha) || !validar_numero($idFicha)) {
            jsonResponse(['success' => false, 'message' => 'ID de ficha no proporcionado o inválido'], 400);
        }

        try {
            $this->programaModel->eliminar((int)$idFicha);
            jsonResponse(['success' => true]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function ajaxEliminarAprendiz(): void {
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
