<?php
require_once __DIR__ . '/BaseModel.php';

class JuiciosModel extends BaseModel {

    public function getAuditoriaFuncionarios(?int $idFicha): array {
        $where = $idFicha ? " WHERE a.id_ficha = :prog " : "";
        $sql = "SELECT f.nombre AS funcionario,
            COUNT(DISTINCT j.id_juicio)      AS total_registros,
            SUM(j.tipo_juicio='Aprobado')    AS aprobados,
            SUM(j.tipo_juicio='Por evaluar') AS por_evaluar,
            SUM(j.tipo_juicio='No aprobado') AS no_aprobados,
            DATE_FORMAT(MIN(j.fecha_juicio),'%d/%m/%Y') AS primer_registro,
            DATE_FORMAT(MAX(j.fecha_juicio),'%d/%m/%Y') AS ultimo_registro
        FROM funcionarios f
        JOIN juicios j ON f.documento = j.id_funcionario
        LEFT JOIN resultados r ON j.id_juicio = r.id_juicio
        LEFT JOIN competencias c ON r.id_resultado = c.id_resultado
        LEFT JOIN aprendices a ON c.id_aprendiz = a.documento
        $where
        GROUP BY f.documento, f.nombre
        ORDER BY total_registros DESC";

        $stmt = $this->db->prepare($sql);
        if ($idFicha) $stmt->execute([':prog' => $idFicha]);
        else $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getComparativa(?int $idFicha): array {
        $where = $idFicha ? " WHERE a.id_ficha = :prog " : "";
        $sql = "SELECT a.documento,
            CONCAT(a.nombres,' ',a.apellidos) AS nombre_completo,
            a.estado, p.nombre AS programa,
            SUM(j.tipo_juicio='Aprobado')    AS aprobados,
            SUM(j.tipo_juicio='Por evaluar') AS por_evaluar,
            SUM(j.tipo_juicio='No aprobado') AS no_aprobados,
            COUNT(j.id_juicio)               AS total_juicios
        FROM aprendices a
        JOIN programas    p ON a.id_ficha     = p.id_ficha
        JOIN competencias c ON a.documento    = c.id_aprendiz
        JOIN resultados   r ON c.id_resultado = r.id_resultado
        JOIN juicios      j ON r.id_juicio    = j.id_juicio
        $where
        GROUP BY a.documento, nombre_completo, a.estado, p.nombre
        ORDER BY nombre_completo";

        $stmt = $this->db->prepare($sql);
        if ($idFicha) $stmt->execute([':prog' => $idFicha]);
        else $stmt->execute();

        return $stmt->fetchAll();
    }

    public function getSeguimiento(?string $documento): array {
        $params = [];
        $and = '';
        if ($documento) {
            $and = ' AND a.documento = :doc';
            $params[':doc'] = $documento;
        }

        $sql = "SELECT a.documento,
            CONCAT(a.nombres,' ',a.apellidos) AS nombre_completo,
            p.nombre AS programa, c.nombre AS competencia,
            r.nombre AS resultado_aprendizaje, j.tipo_juicio,
            DATE_FORMAT(j.fecha_juicio,'%d/%m/%Y') AS fecha_juicio,
            f.nombre AS instructor,
            CASE WHEN j.tipo_juicio='Aprobado' THEN 100 ELSE 0 END AS cumplimiento_pct
        FROM aprendices a
        JOIN programas    p ON a.id_ficha     = p.id_ficha
        JOIN competencias c ON a.documento    = c.id_aprendiz
        JOIN resultados   r ON c.id_resultado = r.id_resultado
        JOIN juicios      j ON r.id_juicio    = j.id_juicio
        JOIN funcionarios f ON j.id_funcionario = f.documento
        WHERE 1=1 {$and}
        ORDER BY nombre_completo, c.nombre, r.id_resultado";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getFiltroAvanzado(array $filters, int $page = 1, int $limit = 15): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['programa'])) {
            $where[]  = 'a.id_ficha = :prog';
            $params[':prog'] = (int)$filters['programa'];
        }
        if (!empty($filters['documento'])) {
            $where[]  = 'a.documento = :doc';
            $params[':doc'] = $filters['documento'];
        }
        if (!empty($filters['estado'])) {
            $where[]  = 'a.estado = :estado';
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['competencia'])) {
            $where[] = "CONCAT_WS(' - ', c.codigo, c.nombre) LIKE :comp";
            $params[':comp'] = '%' . $filters['competencia'] . '%';
        }
        if (!empty($filters['resultado'])) {
            $where[] = "CONCAT_WS(' - ', r.codigo, r.nombre) LIKE :res";
            $params[':res'] = '%' . $filters['resultado'] . '%';
        }
        if (!empty($filters['tipo_juicio'])) {
            $where[]  = 'j.tipo_juicio = :tipo';
            $params[':tipo'] = $filters['tipo_juicio'];
        }

        $fromAndJoins = "FROM aprendices a
        JOIN programas    p ON a.id_ficha     = p.id_ficha
        JOIN competencias c ON a.documento    = c.id_aprendiz
        JOIN resultados   r ON c.id_resultado = r.id_resultado
        JOIN juicios      j ON r.id_juicio    = j.id_juicio
        JOIN funcionarios f ON j.id_funcionario = f.documento
        WHERE " . implode(' AND ', $where);

        $sqlCount = "SELECT COUNT(*) $fromAndJoins";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int)$stmtCount->fetchColumn();
        $totalPages = ceil($total / $limit);

        $offset = ($page - 1) * $limit;

        $sql = "SELECT a.documento,
            CONCAT(a.nombres,' ',a.apellidos) AS nombre_completo,
            a.estado, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, 
            CONCAT_WS(' - ', c.codigo, c.nombre) AS competencia,
            CONCAT_WS(' - ', r.codigo, r.nombre) AS resultado_aprendizaje, j.tipo_juicio,
            DATE_FORMAT(j.fecha_juicio,'%d/%m/%Y') AS fecha_juicio,
            f.nombre AS funcionario_registro
        $fromAndJoins
        ORDER BY nombre_completo, c.nombre
        LIMIT $limit OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return [
            'data' => $rows,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'totalPages' => $totalPages
            ]
        ];
    }

    public function getFiltroAvanzadoCsv(array $filters): array {
        $where = ['1=1'];
        $params = [];

        if (!empty($filters['programa'])) {
            $where[]  = 'a.id_ficha = :prog';
            $params[':prog'] = (int)$filters['programa'];
        }
        if (!empty($filters['documento'])) {
            $where[]  = 'a.documento = :doc';
            $params[':doc'] = $filters['documento'];
        }
        if (!empty($filters['estado'])) {
            $where[]  = 'a.estado = :estado';
            $params[':estado'] = $filters['estado'];
        }
        if (!empty($filters['competencia'])) {
            $where[] = "CONCAT_WS(' - ', c.codigo, c.nombre) LIKE :comp";
            $params[':comp'] = '%' . $filters['competencia'] . '%';
        }
        if (!empty($filters['resultado'])) {
            $where[] = "CONCAT_WS(' - ', r.codigo, r.nombre) LIKE :res";
            $params[':res'] = '%' . $filters['resultado'] . '%';
        }
        if (!empty($filters['tipo_juicio'])) {
            $where[]  = 'j.tipo_juicio = :tipo';
            $params[':tipo'] = $filters['tipo_juicio'];
        }

        $fromAndJoins = "FROM aprendices a
        JOIN programas    p ON a.id_ficha     = p.id_ficha
        JOIN competencias c ON a.documento    = c.id_aprendiz
        JOIN resultados   r ON c.id_resultado = r.id_resultado
        JOIN juicios      j ON r.id_juicio    = j.id_juicio
        JOIN funcionarios f ON j.id_funcionario = f.documento
        WHERE " . implode(' AND ', $where);

        $sql = "SELECT a.documento,
            CONCAT(a.nombres,' ',a.apellidos) AS nombre_completo,
            a.estado, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, 
            CONCAT_WS(' - ', c.codigo, c.nombre) AS competencia,
            CONCAT_WS(' - ', r.codigo, r.nombre) AS resultado_aprendizaje, j.tipo_juicio,
            DATE_FORMAT(j.fecha_juicio,'%d/%m/%Y') AS fecha_juicio,
            f.nombre AS funcionario_registro
        $fromAndJoins
        ORDER BY nombre_completo, c.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function getAvanceCompetencia(?string $documento, ?int $idFicha): array {
        $params = [];
        $where = [];
        if ($documento) {
            $where[] = 'a.documento = :doc';
            $params[':doc'] = $documento;
        }
        if ($idFicha) {
            $where[] = 'a.id_ficha = :prog';
            $params[':prog'] = $idFicha;
        }
        $whereSql = empty($where) ? "" : " WHERE " . implode(" AND ", $where);

        $sql = "SELECT a.documento,
            CONCAT(a.nombres,' ',a.apellidos) AS nombre_completo,
            a.estado, p.nombre AS programa, c.nombre AS competencia,
            COUNT(r.id_resultado) AS total_resultados,
            SUM(j.tipo_juicio='Aprobado')    AS aprobados,
            SUM(j.tipo_juicio='Por evaluar') AS por_evaluar,
            SUM(j.tipo_juicio='No aprobado') AS no_aprobados,
            ROUND(SUM(j.tipo_juicio='Aprobado')*100.0/NULLIF(COUNT(r.id_resultado),0),2) AS porcentaje_avance
        FROM aprendices a
        JOIN programas    p ON a.id_ficha     = p.id_ficha
        JOIN competencias c ON a.documento    = c.id_aprendiz
        JOIN resultados   r ON c.id_resultado = r.id_resultado
        JOIN juicios      j ON r.id_juicio    = j.id_juicio
        $whereSql
        GROUP BY a.documento, nombre_completo, a.estado, p.nombre, c.nombre
        ORDER BY nombre_completo, c.nombre";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }
}
