<?php
require_once __DIR__ . '/BaseModel.php';

class FasesModel extends BaseModel {

    public function listRelaciones(?int $idFicha): array {
        $params = [];
        $where  = '';
        if ($idFicha) {
            $where = ' WHERE fp.id_ficha = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
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

    public function listFases(?int $idFicha): array {
        $params = [];
        if ($idFicha) {
            $params[':id_ficha'] = $idFicha;
            $sql = "SELECT * FROM fases_proyecto WHERE id_ficha = :id_ficha ORDER BY orden";
        } else {
            $sql = "SELECT MIN(id_fase) AS id_fase, nombre_fase, MIN(descripcion) AS descripcion, MIN(orden) AS orden, NULL AS id_ficha FROM fases_proyecto GROUP BY nombre_fase ORDER BY MIN(orden)";
        }
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function listActividades(?int $idFase, ?string $nombreFase, ?int $idFicha): array {
        $params = [];
        if ($idFicha) {
            $params[':f'] = $idFase ?? 0;
            $params[':id_ficha'] = $idFicha;
            $sql = "SELECT * FROM actividades_fase WHERE id_fase = :f AND id_ficha = :id_ficha ORDER BY nombre";
        } else {
            $params[':n'] = $nombreFase ?? '';
            $sql = "SELECT MIN(af.id_actividad) AS id_actividad, af.nombre, MIN(af.descripcion) AS descripcion, MIN(af.id_fase) AS id_fase, NULL AS id_ficha 
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
        $st = $this->db->prepare("INSERT INTO fases_proyecto(nombre_fase, orden, descripcion, id_ficha) VALUES(:n, :o, :d, :f)");
        $st->execute([
            ':n' => $data['nombre_fase'],
            ':o' => (int)($data['orden'] ?? 1),
            ':d' => $data['descripcion'] ?? '',
            ':f' => !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function updateFase(array $data): void {
        $st = $this->db->prepare("UPDATE fases_proyecto SET nombre_fase=:n, orden=:o, descripcion=:d, id_ficha=:f WHERE id_fase=:id");
        $st->execute([
            ':n'  => $data['nombre_fase'],
            ':o'  => (int)($data['orden'] ?? 1),
            ':d'  => $data['descripcion'] ?? '',
            ':f'  => !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null,
            ':id' => (int)$data['id_fase'],
        ]);
    }

    public function deleteFase(int $id): void {
        $st = $this->db->prepare("DELETE FROM fases_proyecto WHERE id_fase=:id");
        $st->execute([':id' => $id]);
    }

    public function createActividad(array $data): int {
        $idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;
        if (!$idFicha && !empty($data['id_fase'])) {
            $stF = $this->db->prepare("SELECT id_ficha FROM fases_proyecto WHERE id_fase = ?");
            $stF->execute([(int)$data['id_fase']]);
            $row = $stF->fetch();
            $idFicha = $row ? ($row['id_ficha'] ?? null) : null;
        }
        $st = $this->db->prepare("INSERT INTO actividades_fase(nombre, descripcion, id_fase, id_ficha) VALUES(:n, :d, :f, :fi)");
        $st->execute([
            ':n'  => $data['nombre'],
            ':d'  => $data['descripcion'] ?? '',
            ':f'  => (int)$data['id_fase'],
            ':fi' => $idFicha,
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

        $st = $this->db->prepare("INSERT INTO fase_competencia_resultado(id_actividad, nombre_competencia, nombre_resultado) VALUES(:a, :nc, :nr)");
        $st->execute([
            ':a'  => (int)$data['id_actividad'],
            ':nc' => $nombreCompetencia,
            ':nr' => $nombreResultado,
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function deleteRelacion(int $id): void {
        $st = $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id=:id");
        $st->execute([':id' => $id]);
    }

    public function deleteProyecto(int $idFicha): void {
        $this->db->beginTransaction();
        try {
            $stFcr = $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_ficha = ?");
            $stFcr->execute([$idFicha]);
            $stAf = $this->db->prepare("DELETE FROM actividades_fase WHERE id_ficha = ?");
            $stAf->execute([$idFicha]);
            $stFp = $this->db->prepare("DELETE FROM fases_proyecto WHERE id_ficha = ?");
            $stFp->execute([$idFicha]);
            $stP = $this->db->prepare("UPDATE programas SET codigo_programa_sofia = NULL, nombre_proyecto = NULL, centro_formacion = NULL, regional = NULL, tiempo_estimado_meses = NULL, total_resultados = NULL WHERE id_ficha = ?");
            $stP->execute([$idFicha]);
            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function listProyectos(): array {
        $st = $this->db->query("SELECT id_ficha, nombre, codigo_programa_sofia, nombre_proyecto, centro_formacion, regional, total_resultados, tiempo_estimado_meses FROM programas WHERE codigo_programa_sofia IS NOT NULL ORDER BY nombre");
        return $st->fetchAll();
    }

    public function getProyectoDetalle(int $idFicha): array {
        // Fases
        $stF = $this->db->prepare("SELECT * FROM fases_proyecto WHERE id_ficha = ? ORDER BY orden");
        $stF->execute([$idFicha]);
        $fases = $stF->fetchAll();
        
        // Actividades
        $stA = $this->db->prepare("SELECT * FROM actividades_fase WHERE id_ficha = ? ORDER BY nombre");
        $stA->execute([$idFicha]);
        $actividades = $stA->fetchAll();
        
        // Competencias/Resultados (Relaciones)
        $stR = $this->db->prepare("SELECT * FROM fase_competencia_resultado WHERE id_ficha = ? ORDER BY nombre_competencia, nombre_resultado");
        $stR->execute([$idFicha]);
        $relaciones = $stR->fetchAll();
        
        // Estructurar árbol
        foreach ($fases as &$f) {
            $f['actividades'] = array_filter($actividades, fn($a) => $a['id_fase'] == $f['id_fase']);
            // Reindexar el array para json
            $f['actividades'] = array_values($f['actividades']);
            
            foreach ($f['actividades'] as &$a) {
                $a['relaciones'] = array_values(array_filter($relaciones, fn($r) => $r['id_actividad'] == $a['id_actividad']));
            }
        }
        return $fases;
    }

    public function getCumplimiento(?int $idFicha): array {
        $fichaFilter = '';
        $params = [];
        if ($idFicha) {
            $fichaFilter = ' AND fp.id_ficha = :id_ficha ';
            $params[':id_ficha'] = $idFicha;
        }

        $sql = "SELECT
            MIN(fp.id_fase) AS id_fase,
            MIN(fp.orden) AS orden,
            fp.nombre_fase,
            COUNT(DISTINCT a.documento)                                                        AS total_aprendices,
            COUNT(DISTINCT fcr.codigo_resultado)                                               AS total_resultados_fase,
            COUNT(DISTINCT CONCAT(a.documento,'|',fcr.codigo_resultado))                       AS pares_total,
            COUNT(DISTINCT CASE WHEN ev.tipo_juicio='Aprobado'
                      THEN CONCAT(a.documento,'|',fcr.codigo_resultado) END)                   AS pares_aprobados,
            COUNT(DISTINCT CASE WHEN ev.tipo_juicio='No aprobado'
                      THEN CONCAT(a.documento,'|',fcr.codigo_resultado) END)                   AS pares_no_aprobados,
            COUNT(DISTINCT CASE WHEN COALESCE(ev.tipo_juicio,'Por evaluar')='Por evaluar'
                      THEN CONCAT(a.documento,'|',fcr.codigo_resultado) END)                   AS pares_pendientes,
            ROUND(COUNT(DISTINCT CASE WHEN ev.tipo_juicio='Aprobado'
                      THEN CONCAT(a.documento,'|',fcr.codigo_resultado) END) * 100.0
                / NULLIF(COUNT(DISTINCT CONCAT(a.documento,'|',fcr.codigo_resultado)),0), 1)   AS porcentaje_cumplimiento_fase,
            COUNT(DISTINCT CASE WHEN ev.tipo_juicio='Aprobado' THEN a.documento END)          AS aprendices_aprobados,
            COUNT(DISTINCT CASE WHEN COALESCE(ev.tipo_juicio,'Por evaluar') IN('Por evaluar','No aprobado')
                      THEN a.documento END)                                                     AS aprendices_pendientes,
            COUNT(DISTINCT a.documento)                                                        AS total_aprendices_fase
        FROM fases_proyecto fp
        JOIN actividades_fase af            ON af.id_fase       = fp.id_fase
        JOIN fase_competencia_resultado fcr ON fcr.id_actividad = af.id_actividad
        JOIN aprendices a                   ON a.id_ficha       = fp.id_ficha
        LEFT JOIN (
            SELECT j.documento_aprendiz, r.codigo, j.tipo_juicio
            FROM juicios j
            JOIN resultados r ON r.id_juicio = j.id_juicio
        ) ev ON ev.documento_aprendiz = a.documento AND ev.codigo = fcr.codigo_resultado
        WHERE 1=1 $fichaFilter
        GROUP BY fp.nombre_fase
        ORDER BY orden";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getDetalleFases(?int $idFase, ?int $idFicha): array {
        $params = [];
        $and = '';
        if ($idFase) {
            $and .= ' AND fp.id_fase = :id_fase';
            $params[':id_fase'] = $idFase;
        }
        if ($idFicha) {
            $and .= ' AND fp.id_ficha = :id_ficha';
            $params[':id_ficha'] = $idFicha;
        }

        $sql = "SELECT fp.orden, fp.nombre_fase,
            CONCAT(a.nombres,' ',a.apellidos) AS aprendiz,
            a.estado AS estado_aprendiz, fcr.nombre_competencia AS competencia,
            fcr.nombre_resultado AS resultado_aprendizaje, COALESCE(ev.tipo_juicio, 'Por evaluar') AS estado_en_fase
        FROM fases_proyecto fp
        JOIN actividades_fase af            ON af.id_fase       = fp.id_fase
        JOIN fase_competencia_resultado fcr ON fcr.id_actividad = af.id_actividad
        JOIN aprendices a                   ON a.id_ficha       = fp.id_ficha
        LEFT JOIN (
            SELECT j.documento_aprendiz, r.codigo, j.tipo_juicio
            FROM juicios j
            JOIN resultados r ON r.id_juicio = j.id_juicio
        ) ev ON ev.documento_aprendiz = a.documento AND ev.codigo = fcr.codigo_resultado
        WHERE 1=1 {$and}
        ORDER BY fp.orden, a.apellidos, a.nombres";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
