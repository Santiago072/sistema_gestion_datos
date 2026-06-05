<?php
require_once __DIR__ . '/BaseModel.php';

class AprendizModel extends BaseModel {

    public function buscar(string $documento, string $id_ficha): array {
        $conditions = [];
        $params     = [];
        
        if ($documento !== '') {
            $conditions[] = "a.documento LIKE :doc";
            $params[':doc'] = '%' . $documento . '%';
        }
        if ($id_ficha !== '') {
            $conditions[] = "a.id_ficha = :ficha";
            $params[':ficha'] = $id_ficha;
        }
        
        if (empty($conditions)) {
            return [];
        }
        
        $where = 'WHERE ' . implode(' AND ', $conditions);
        
        $sql = "SELECT a.documento, a.nombres, a.apellidos, a.id_ficha, p.nombre AS programa
                FROM aprendices a
                LEFT JOIN programas p ON a.id_ficha = p.id_ficha
                $where
                ORDER BY a.apellidos, a.nombres
                LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function existeEnFicha(string $documento, string $id_ficha): bool {
        $stmt = $this->db->prepare("SELECT documento FROM aprendices WHERE documento = :doc AND id_ficha = :ficha LIMIT 1");
        $stmt->execute([':doc' => $documento, ':ficha' => $id_ficha]);
        return (bool)$stmt->fetch();
    }

    public function eliminar(string $documento, string $id_ficha): void {
        $this->db->beginTransaction();
        try {
            // 1. Eliminar juicios del aprendiz en esa ficha
            $this->db->prepare("DELETE FROM juicios WHERE documento_aprendiz = :doc AND id_ficha = :ficha")
               ->execute([':doc' => $documento, ':ficha' => $id_ficha]);
        
            // 2. Eliminar competencias y resultados relacionados (si existen para ese aprendiz y ficha)
            $this->db->prepare("DELETE c FROM competencias c WHERE c.id_aprendiz = :doc AND c.id_ficha = :ficha")
               ->execute([':doc' => $documento, ':ficha' => $id_ficha]);
        
            // 3. Eliminar al aprendiz
            $this->db->prepare("DELETE FROM aprendices WHERE documento = :doc AND id_ficha = :ficha")
               ->execute([':doc' => $documento, ':ficha' => $id_ficha]);
        
            $this->db->commit();
        } catch (\Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function getFormacion(?string $prog): array {
        $where = $prog ? " WHERE p.id_ficha = :prog " : "";
        
        $sql = "SELECT p.id_ficha, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa,
            COUNT(a.documento) AS total_aprendices,
            SUM(a.estado='En formación') AS en_formacion,
            SUM(a.estado='Retirado')     AS retirados,
            SUM(a.estado='Trasladado')   AS trasladados,
            SUM(a.estado='Egresado')     AS egresados
        FROM programas p
        LEFT JOIN aprendices a ON p.id_ficha = a.id_ficha
        $where
        GROUP BY p.id_ficha, p.nombre ORDER BY p.nombre";
        
        $stmt = $this->db->prepare($sql);
        if ($prog) {
            $stmt->execute([':prog' => $prog]);
        } else {
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
}
