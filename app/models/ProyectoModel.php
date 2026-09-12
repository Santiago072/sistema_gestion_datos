<?php
require_once __DIR__ . '/BaseModel.php';

class ProyectoModel extends BaseModel {

    public function getAll(): array {
        $sql = "SELECT pf.*, 
                       COUNT(p.id_ficha) AS total_fichas,
                       (SELECT COUNT(*) FROM fases_proyecto fp WHERE fp.id_proyecto = pf.id_proyecto) AS total_fases,
                       (SELECT COUNT(*) FROM actividades_fase af WHERE af.id_proyecto = pf.id_proyecto) AS total_actividades
                FROM proyectos_formativos pf
                LEFT JOIN programas p ON p.id_proyecto = pf.id_proyecto
                GROUP BY pf.id_proyecto
                ORDER BY pf.nombre_proyecto ASC";
        $st = $this->db->query($sql);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById(int $idProyecto): ?array {
        $st = $this->db->prepare("SELECT * FROM proyectos_formativos WHERE id_proyecto = ?");
        $st->execute([$idProyecto]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function getFichasAsociadas(int $idProyecto): array {
        $st = $this->db->prepare("SELECT id_ficha, nombre FROM programas WHERE id_proyecto = ? ORDER BY nombre");
        $st->execute([$idProyecto]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function asociarFicha(int $idFicha, int $idProyecto): bool {
        $st = $this->db->prepare("UPDATE programas SET id_proyecto = :p WHERE id_ficha = :f");
        return $st->execute([':p' => $idProyecto, ':f' => $idFicha]);
    }

    public function desasociarFicha(int $idFicha): bool {
        $st = $this->db->prepare("UPDATE programas SET id_proyecto = NULL WHERE id_ficha = ?");
        return $st->execute([$idFicha]);
    }

    public function delete(int $idProyecto): bool {
        $this->db->beginTransaction();
        try {
            // Desvincular fichas
            $this->db->prepare("UPDATE programas SET id_proyecto = NULL WHERE id_proyecto = ?")->execute([$idProyecto]);

            // Eliminar relaciones curriculares
            $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_proyecto = ?")->execute([$idProyecto]);
            $this->db->prepare("DELETE FROM actividades_fase WHERE id_proyecto = ?")->execute([$idProyecto]);
            $this->db->prepare("DELETE FROM fases_proyecto WHERE id_proyecto = ?")->execute([$idProyecto]);

            // Eliminar el proyecto formativo
            $st = $this->db->prepare("DELETE FROM proyectos_formativos WHERE id_proyecto = ?");
            $st->execute([$idProyecto]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
