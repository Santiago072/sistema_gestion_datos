<?php
require_once __DIR__ . '/BaseModel.php';

class ProgramaModel extends BaseModel {

    public function getAll(): array {
        $stmt = $this->db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }


    public function eliminar(string $id_ficha): void {
        $this->db->beginTransaction();
        try {
            // 1. Eliminar juicios asociados a los aprendices de este programa
            $sqlJuicios = "DELETE j FROM juicios j
                           JOIN resultados r ON j.id_juicio = r.id_juicio
                           JOIN competencias c ON r.id_resultado = c.id_resultado
                           JOIN aprendices a ON c.id_aprendiz = a.documento
                           WHERE a.id_ficha = :ficha";
            $stmtJ = $this->db->prepare($sqlJuicios);
            $stmtJ->execute([':ficha' => $id_ficha]);
        
            // 2. Eliminar resultados asociados a los aprendices de este programa
            $sqlResultados = "DELETE r FROM resultados r
                              JOIN competencias c ON r.id_resultado = c.id_resultado
                              JOIN aprendices a ON c.id_aprendiz = a.documento
                              WHERE a.id_ficha = :ficha";
            $stmtR = $this->db->prepare($sqlResultados);
            $stmtR->execute([':ficha' => $id_ficha]);
        
            // 3. Eliminar competencias de los aprendices de este programa
            $sqlCompetencias = "DELETE c FROM competencias c
                                JOIN aprendices a ON c.id_aprendiz = a.documento
                                WHERE a.id_ficha = :ficha";
            $stmtC = $this->db->prepare($sqlCompetencias);
            $stmtC->execute([':ficha' => $id_ficha]);
        
            // 4. Eliminar a los aprendices
            $sqlAprendices = "DELETE FROM aprendices WHERE id_ficha = :ficha";
            $stmtA = $this->db->prepare($sqlAprendices);
            $stmtA->execute([':ficha' => $id_ficha]);
        
            // 5. Verificar si este programa tiene un proyecto formativo asociado (PDF cargado o fases)
            $stmtCheck = $this->db->prepare("
                SELECT 1 FROM fases_proyecto WHERE id_ficha = :ficha 
                UNION 
                SELECT 1 FROM programas WHERE id_ficha = :ficha2 AND (nombre_proyecto IS NOT NULL OR codigo_programa_sofia IS NOT NULL)
                LIMIT 1
            ");
            $stmtCheck->execute([':ficha' => $id_ficha, ':ficha2' => $id_ficha]);
            $tieneProyecto = (bool)$stmtCheck->fetch();

            // Si NO tiene proyecto formativo vinculado, podemos eliminar el registro de programas por completo
            if (!$tieneProyecto) {
                // Eliminar fases o residuos si existieran
                $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_ficha = :ficha")->execute([':ficha' => $id_ficha]);
                $this->db->prepare("DELETE FROM actividades_fase WHERE id_ficha = :ficha")->execute([':ficha' => $id_ficha]);
                $this->db->prepare("DELETE FROM fases_proyecto WHERE id_ficha = :ficha")->execute([':ficha' => $id_ficha]);

                // Eliminar el programa
                $sqlPrograma = "DELETE FROM programas WHERE id_ficha = :ficha";
                $stmtP = $this->db->prepare($sqlPrograma);
                $stmtP->execute([':ficha' => $id_ficha]);
            }
            // NOTA: Si SÍ tiene proyecto formativo (PDF), se conservan intactos sus datos (nombre_proyecto,
            // centro_formacion, regional, duración, fases y actividades), permitiendo que la información
            // del proyecto no se degrade ni se pierda al eliminar o limpiar la ficha evaluativa de aprendices.
        
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
