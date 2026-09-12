<?php
require_once __DIR__ . '/BaseModel.php';

class DashboardModel extends BaseModel {
    public function getKpis(?int $progId): array|false {
        if ($progId) {
            $whereA = " AND a.id_ficha = $progId ";
            $whereA_simple = " AND id_ficha = $progId ";
            $whereP = " WHERE id_ficha = $progId ";
            $sql = "SELECT
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'En formación' $whereA_simple) AS total_aprendices_activos,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Retirado' $whereA_simple) AS total_retirados,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Trasladado' $whereA_simple) AS total_trasladados,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Egresado' $whereA_simple) AS total_egresados,
                (SELECT COUNT(DISTINCT j.id_juicio) FROM juicios j JOIN resultados r ON r.id_juicio=j.id_juicio JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE j.tipo_juicio = 'Aprobado' $whereA) AS total_juicios_aprobados,
                (SELECT COUNT(DISTINCT j.id_juicio) FROM juicios j JOIN resultados r ON r.id_juicio=j.id_juicio JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE j.tipo_juicio = 'Por evaluar' $whereA) AS total_juicios_por_evaluar,
                (SELECT COUNT(DISTINCT j.id_juicio) FROM juicios j JOIN resultados r ON r.id_juicio=j.id_juicio JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE j.tipo_juicio = 'No aprobado' $whereA) AS total_juicios_no_aprobados,
                (SELECT COUNT(*) FROM programas $whereP) AS total_programas,
                (SELECT COUNT(DISTINCT j.id_funcionario) FROM juicios j JOIN resultados r ON r.id_juicio=j.id_juicio JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE 1=1 $whereA) AS total_funcionarios,
                (SELECT COUNT(DISTINCT r.id_resultado) FROM resultados r JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE 1=1 $whereA) AS total_resultados,
                (SELECT COUNT(DISTINCT c.id_competencia) FROM competencias c JOIN aprendices a ON a.documento=c.id_aprendiz WHERE 1=1 $whereA) AS total_competencias";
            $stmt = $this->db->query($sql);
            $res = $stmt->fetch();
            
            $tieneDatos = ((int)($res['total_aprendices_activos'] ?? 0) + (int)($res['total_retirados'] ?? 0) + (int)($res['total_juicios_aprobados'] ?? 0)) > 0;
            if ($tieneDatos) {
                $sqlH = "SELECT h.fecha_corte, 
                                DATE_FORMAT(h.fecha_corte, '%d/%m/%Y') as fecha_corte_formato,
                                h.fecha_subida, 
                                DATE_FORMAT(h.fecha_subida, '%d/%m/%Y %h:%i:%s %p') as fecha_subida_formato,
                                h.nombre_archivo, h.total_filas 
                         FROM historial_cortes_reportes h
                         INNER JOIN programas p ON p.id_ficha = h.id_ficha
                         WHERE h.id_ficha = " . (int)$progId . " 
                         ORDER BY h.fecha_corte DESC, h.fecha_subida DESC LIMIT 1";
                $res['ultimo_corte'] = $this->db->query($sqlH)->fetch(PDO::FETCH_ASSOC) ?: null;
            } else {
                $res['ultimo_corte'] = null;
            }
            return $res;
        } else {
            $sql = "SELECT
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'En formación') AS total_aprendices_activos,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Retirado') AS total_retirados,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Trasladado') AS total_trasladados,
                (SELECT COUNT(*) FROM aprendices WHERE estado = 'Egresado') AS total_egresados,
                (SELECT COUNT(*) FROM juicios WHERE tipo_juicio = 'Aprobado') AS total_juicios_aprobados,
                (SELECT COUNT(*) FROM juicios WHERE tipo_juicio = 'Por evaluar') AS total_juicios_por_evaluar,
                (SELECT COUNT(*) FROM juicios WHERE tipo_juicio = 'No aprobado') AS total_juicios_no_aprobados,
                (SELECT COUNT(*) FROM programas) AS total_programas,
                (SELECT COUNT(*) FROM funcionarios) AS total_funcionarios,
                (SELECT COUNT(*) FROM resultados) AS total_resultados,
                (SELECT COUNT(*) FROM competencias) AS total_competencias";
            $res = $this->db->query($sql)->fetch();
            
            // Si está en "Todos los programas", el corte NO se muestra (cada ficha tiene su propio corte particular)
            $res['ultimo_corte'] = null;
            return $res;
        }
    }
}
