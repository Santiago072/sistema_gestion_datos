<?php
require_once __DIR__ . '/BaseModel.php';

class ProgramaModel extends BaseModel {

    public function getAll(): array {
        // Consultar con fallback robusto por si la migración de base de datos aún no se ha aplicado en el entorno
        try {
            $stmt = $this->db->query("
                SELECT p.id_ficha, p.nombre, p.id_proyecto, pf.nombre_proyecto, pf.codigo_programa_sofia
                FROM programas p
                LEFT JOIN proyectos_formativos pf ON p.id_proyecto = pf.id_proyecto
                ORDER BY p.nombre
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            // Fallback si la tabla proyectos_formativos o la columna id_proyecto no existen aún en la base de datos del host
            $stmt = $this->db->query("SELECT id_ficha, nombre FROM programas ORDER BY nombre");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
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
        
            // 4. Eliminar a los aprendices de la ficha
            $sqlAprendices = "DELETE FROM aprendices WHERE id_ficha = :ficha";
            $stmtA = $this->db->prepare($sqlAprendices);
            $stmtA->execute([':ficha' => $id_ficha]);

            // 5. Eliminar la ficha de la tabla programas
            // NOTA ARQUITECTÓNICA: Como el proyecto formativo ahora vive en `proyectos_formativos`,
            // la eliminación de la ficha o de sus aprendices NUNCA afecta ni borra el proyecto formativo,
            // sus fases, actividades ni competencias curriculares.
            $sqlPrograma = "DELETE FROM programas WHERE id_ficha = :ficha";
            $stmtP = $this->db->prepare($sqlPrograma);
            $stmtP->execute([':ficha' => $id_ficha]);
        
            $this->db->commit();
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
