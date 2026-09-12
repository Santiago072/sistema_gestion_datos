<?php
require_once __DIR__ . '/BaseModel.php';

class FasesModel extends BaseModel {

    private function resolveProyectoId(?int $idFicha, ?int $idProyecto = null): ?int {
        if ($idProyecto) return $idProyecto;
        if (!$idFicha) return null;
        $st = $this->db->prepare("SELECT id_proyecto FROM programas WHERE id_ficha = ?");
        $st->execute([$idFicha]);
        $val = $st->fetchColumn();
        return $val ? (int)$val : null;
    }

    public function listRelaciones(?int $idFicha, ?int $idProyecto = null): array {
        $idProy = $this->resolveProyectoId($idFicha, $idProyecto);
        $params = [];
        $where  = '';
        if ($idProy) {
            $where = ' WHERE fp.id_proyecto = :id_proy ';
            $params[':id_proy'] = $idProy;
        } elseif ($idFicha) {
            $where = ' WHERE (fp.id_ficha = :id_ficha OR fp.id_proyecto = (SELECT id_proyecto FROM programas WHERE id_ficha = :id_ficha2)) ';
            $params[':id_ficha'] = $idFicha;
            $params[':id_ficha2'] = $idFicha;
        }

        $sql = "SELECT fcr.id, fp.nombre_fase, af.id_actividad, af.nombre AS actividad,
            COALESCE(fcr.nombre_competencia, c.nombre) AS competencia,
            COALESCE(fcr.nombre_resultado, r.nombre)   AS resultado_aprendizaje
          FROM fase_competencia_resultado fcr
          JOIN actividades_fase af ON af.id_actividad = fcr.id_actividad
          JOIN fases_proyecto   fp ON fp.id_fase      = af.id_fase
          LEFT JOIN competencias c ON c.id_competencia = fcr.id_competencia
          LEFT JOIN resultados   r ON r.id_resultado   = fcr.id_resultado
          {$where}
          ORDER BY fp.orden, af.nombre";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listFases(?int $idFicha, ?int $idProyecto = null): array {
        $idProy = $this->resolveProyectoId($idFicha, $idProyecto);
        $params = [];
        if ($idProy) {
            $params[':id_proy'] = $idProy;
            $sql = "SELECT * FROM fases_proyecto WHERE id_proyecto = :id_proy ORDER BY orden";
        } elseif ($idFicha) {
            $params[':id_ficha'] = $idFicha;
            $sql = "SELECT * FROM fases_proyecto WHERE id_ficha = :id_ficha ORDER BY orden";
        } else {
            $sql = "SELECT MIN(id_fase) AS id_fase, nombre_fase, MIN(descripcion) AS descripcion, MIN(orden) AS orden, NULL AS id_ficha, NULL AS id_proyecto 
                    FROM fases_proyecto GROUP BY nombre_fase ORDER BY MIN(orden)";
        }
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listActividades(?int $idFase, ?string $nombreFase, ?int $idFicha, ?int $idProyecto = null): array {
        $idProy = $this->resolveProyectoId($idFicha, $idProyecto);
        $params = [];
        if ($idProy) {
            $params[':f'] = $idFase ?? 0;
            $params[':p'] = $idProy;
            $sql = "SELECT * FROM actividades_fase WHERE id_fase = :f AND id_proyecto = :p ORDER BY nombre";
        } elseif ($idFicha) {
            $params[':f'] = $idFase ?? 0;
            $params[':id_ficha'] = $idFicha;
            $sql = "SELECT * FROM actividades_fase WHERE id_fase = :f AND (id_ficha = :id_ficha OR id_proyecto = (SELECT id_proyecto FROM programas WHERE id_ficha = :id_ficha2)) ORDER BY nombre";
            $params[':id_ficha2'] = $idFicha;
        } else {
            $params[':n'] = $nombreFase ?? '';
            $sql = "SELECT MIN(af.id_actividad) AS id_actividad, af.nombre, MIN(af.descripcion) AS descripcion, MIN(af.id_fase) AS id_fase, NULL AS id_ficha, NULL AS id_proyecto 
                    FROM actividades_fase af 
                    JOIN fases_proyecto fp ON fp.id_fase = af.id_fase 
                    WHERE fp.nombre_fase = :n 
                    GROUP BY af.nombre 
                    ORDER BY af.nombre";
        }
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function createFase(array $data): int {
        $idProy = !empty($data['id_proyecto']) ? (int)$data['id_proyecto'] : null;
        $idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;
        if (!$idProy && $idFicha) {
            $idProy = $this->resolveProyectoId($idFicha);
        }

        $st = $this->db->prepare("INSERT INTO fases_proyecto(nombre_fase, orden, descripcion, id_ficha, id_proyecto) VALUES(:n, :o, :d, :f, :p)");
        $st->execute([
            ':n' => $data['nombre_fase'],
            ':o' => (int)($data['orden'] ?? 1),
            ':d' => $data['descripcion'] ?? '',
            ':f' => $idFicha,
            ':p' => $idProy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateFase(array $data): void {
        $st = $this->db->prepare("UPDATE fases_proyecto SET nombre_fase=:n, orden=:o, descripcion=:d WHERE id_fase=:id");
        $st->execute([
            ':n'  => $data['nombre_fase'],
            ':o'  => (int)($data['orden'] ?? 1),
            ':d'  => $data['descripcion'] ?? '',
            ':id' => (int)$data['id_fase'],
        ]);
    }

    public function deleteFase(int $id): void {
        $st = $this->db->prepare("DELETE FROM fases_proyecto WHERE id_fase=:id");
        $st->execute([':id' => $id]);
    }

    public function createActividad(array $data): int {
        $idFase = (int)($data['id_fase'] ?? 0);
        $idProy = !empty($data['id_proyecto']) ? (int)$data['id_proyecto'] : null;
        $idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;

        if (!$idProy && $idFase) {
            $stF = $this->db->prepare("SELECT id_proyecto, id_ficha FROM fases_proyecto WHERE id_fase = ?");
            $stF->execute([$idFase]);
            $row = $stF->fetch();
            if ($row) {
                $idProy = $row['id_proyecto'] ?? null;
                if (!$idFicha) $idFicha = $row['id_ficha'] ?? null;
            }
        }

        $st = $this->db->prepare("INSERT INTO actividades_fase(nombre, descripcion, id_fase, id_ficha, id_proyecto) VALUES(:n, :d, :f, :fi, :p)");
        $st->execute([
            ':n'  => $data['nombre'],
            ':d'  => $data['descripcion'] ?? '',
            ':f'  => $idFase,
            ':fi' => $idFicha,
            ':p'  => $idProy,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteActividad(int $id): void {
        $st = $this->db->prepare("DELETE FROM actividades_fase WHERE id_actividad=:id");
        $st->execute([':id' => $id]);
    }

    public function createRelacion(array $data): int {
        $nombreCompetencia = null;
        $nombreResultado   = null;

        if (!empty($data['id_competencia'])) {
            $stC = $this->db->prepare("SELECT nombre FROM competencias WHERE id_competencia = ?");
            $stC->execute([(int)$data['id_competencia']]);
            $nombreCompetencia = $stC->fetchColumn();
        }
        if (!empty($data['id_resultado'])) {
            $stR = $this->db->prepare("SELECT nombre FROM resultados WHERE id_resultado = ?");
            $stR->execute([(int)$data['id_resultado']]);
            $nombreResultado = $stR->fetchColumn();
        }

        $idActividad = (int)$data['id_actividad'];
        $idProy = null;
        $stA = $this->db->prepare("SELECT id_proyecto FROM actividades_fase WHERE id_actividad = ?");
        $stA->execute([$idActividad]);
        $idProy = $stA->fetchColumn() ?: null;

        $st = $this->db->prepare("INSERT INTO fase_competencia_resultado(id_actividad, id_proyecto, nombre_competencia, nombre_resultado) VALUES(:a, :p, :nc, :nr)");
        $st->execute([
            ':a'  => $idActividad,
            ':p'  => $idProy,
            ':nc' => $nombreCompetencia,
            ':nr' => $nombreResultado,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteRelacion(int $id): void {
        $st = $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id=:id");
        $st->execute([':id' => $id]);
    }

    public function deleteProyecto(int $idProyectoOIdFicha): void {
        $this->db->beginTransaction();
        try {
            // Determinar si es id_proyecto directo o id_ficha
            $idProyecto = null;
            $stCheck = $this->db->prepare("SELECT id_proyecto FROM proyectos_formativos WHERE id_proyecto = ?");
            $stCheck->execute([$idProyectoOIdFicha]);
            if ($stCheck->fetch()) {
                $idProyecto = $idProyectoOIdFicha;
            } else {
                $stFicha = $this->db->prepare("SELECT id_proyecto FROM programas WHERE id_ficha = ?");
                $stFicha->execute([$idProyectoOIdFicha]);
                $idProyecto = $stFicha->fetchColumn() ?: null;
            }

            if ($idProyecto) {
                // Desvincular de los programas/fichas (quedan con id_proyecto = NULL, no se borran)
                $this->db->prepare("UPDATE programas SET id_proyecto = NULL WHERE id_proyecto = ?")->execute([$idProyecto]);

                // Eliminar fases, actividades y mapeos del proyecto
                $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_proyecto = ?")->execute([$idProyecto]);
                $this->db->prepare("DELETE FROM actividades_fase WHERE id_proyecto = ?")->execute([$idProyecto]);
                $this->db->prepare("DELETE FROM fases_proyecto WHERE id_proyecto = ?")->execute([$idProyecto]);

                // Eliminar el proyecto
                $this->db->prepare("DELETE FROM proyectos_formativos WHERE id_proyecto = ?")->execute([$idProyecto]);
            }

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listProyectos(): array {
        $sql = "SELECT 
                    pf.id_proyecto,
                    COALESCE(p.id_ficha, (SELECT id_ficha FROM programas WHERE id_proyecto = pf.id_proyecto LIMIT 1)) AS id_ficha,
                    COALESCE(pf.nombre_programa, p.nombre, 'Sin programa asociado') AS nombre,
                    COALESCE(pf.codigo_programa_sofia, '—') AS codigo_programa_sofia,
                    pf.nombre_proyecto,
                    COALESCE(pf.centro_formacion, '—') AS centro_formacion,
                    COALESCE(pf.regional, '—') AS regional,
                    COALESCE(pf.total_resultados, (SELECT COUNT(*) FROM fase_competencia_resultado fcr WHERE fcr.id_proyecto = pf.id_proyecto)) AS total_resultados,
                    COALESCE(pf.tiempo_estimado_meses, 0) AS tiempo_estimado_meses,
                    (SELECT COUNT(*) FROM fases_proyecto fp WHERE fp.id_proyecto = pf.id_proyecto) AS total_fases,
                    COUNT(DISTINCT p.id_ficha) AS total_fichas_asociadas
                FROM proyectos_formativos pf
                LEFT JOIN programas p ON p.id_proyecto = pf.id_proyecto
                GROUP BY pf.id_proyecto
                ORDER BY pf.nombre_proyecto ASC";
        $st = $this->db->query($sql);
        return $st->fetchAll();
    }

    public function getProyectoDetalle(int $idProyectoOIdFicha): array {
        $idProyecto = $idProyectoOIdFicha;
        $stCheck = $this->db->prepare("SELECT id_proyecto FROM proyectos_formativos WHERE id_proyecto = ?");
        $stCheck->execute([$idProyectoOIdFicha]);
        if (!$stCheck->fetch()) {
            $stFicha = $this->db->prepare("SELECT id_proyecto FROM programas WHERE id_ficha = ?");
            $stFicha->execute([$idProyectoOIdFicha]);
            $idProyecto = (int)($stFicha->fetchColumn() ?: 0);
        }

        if (!$idProyecto) return [];

        // Fases
        $stF = $this->db->prepare("SELECT * FROM fases_proyecto WHERE id_proyecto = ? ORDER BY orden");
        $stF->execute([$idProyecto]);
        $fases = $stF->fetchAll();
        
        // Actividades
        $stA = $this->db->prepare("SELECT * FROM actividades_fase WHERE id_proyecto = ? ORDER BY nombre");
        $stA->execute([$idProyecto]);
        $actividades = $stA->fetchAll();
        
        // Competencias/Resultados (Relaciones)
        $stR = $this->db->prepare("SELECT * FROM fase_competencia_resultado WHERE id_proyecto = ? ORDER BY nombre_competencia, nombre_resultado");
        $stR->execute([$idProyecto]);
        $relaciones = $stR->fetchAll();
        
        // Estructurar árbol
        foreach ($fases as &$f) {
            $f['actividades'] = array_filter($actividades, fn($a) => $a['id_fase'] == $f['id_fase']);
            $f['actividades'] = array_values($f['actividades']);
            
            foreach ($f['actividades'] as &$a) {
                $a['relaciones'] = array_values(array_filter($relaciones, fn($r) => $r['id_actividad'] == $a['id_actividad']));
            }
        }
        return $fases;
    }
}
