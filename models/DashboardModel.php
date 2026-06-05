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
                (SELECT COUNT(DISTINCT j.id_funcionario) FROM juicios j JOIN resultados r ON r.id_juicio=j.id_juicio JOIN competencias c ON c.id_resultado=r.id_resultado JOIN aprendices a ON a.documento=c.id_aprendiz WHERE 1=1 $whereA) AS total_funcionarios";
            $stmt = $this->db->query($sql);
            return $stmt->fetch();
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
                (SELECT COUNT(*) FROM funcionarios) AS total_funcionarios";
            return $this->db->query($sql)->fetch();
        }
    }
}
