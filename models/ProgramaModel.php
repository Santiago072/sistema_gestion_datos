<?php
require_once __DIR__ . '/BaseModel.php';

class ProgramaModel extends BaseModel {

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
        
            // 5. Eliminar el programa
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
