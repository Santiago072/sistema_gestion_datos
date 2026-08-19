<?php
require_once __DIR__ . '/BaseModel.php';

class DashboardFasesRepository extends BaseModel {

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
        JOIN aprendices a                   ON a.id_ficha = fp.id_ficha
                                           AND a.estado = 'En formación'
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
        JOIN aprendices a                   ON a.id_ficha = fp.id_ficha
                                           AND a.estado = 'En formación'
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
