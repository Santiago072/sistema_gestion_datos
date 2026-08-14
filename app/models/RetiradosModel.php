<?php
require_once __DIR__ . '/BaseModel.php';

class RetiradosModel extends BaseModel {

    public function getSurvivalData(?int $idFicha): array {
        $params = [];
        $whereAll = '';
        if ($idFicha) {
            $whereAll = " AND a.id_ficha = :prog ";
            $params[':prog'] = $idFicha;
        }

        // 1. Total inicial por programa
        $sqlTotales = "SELECT CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, COUNT(DISTINCT a.documento) AS total_inicial
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            WHERE 1=1 $whereAll
            GROUP BY p.id_ficha, p.nombre";
        $stmtT = $this->db->prepare($sqlTotales);
        $stmtT->execute($params);
        $totales = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        // 2. Lista ordenada de competencias por programa
        $sqlComps = "SELECT CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, c.nombre AS competencia,
            MIN(j.fecha_juicio) as primera_fecha,
            MAX(fase_map.nombre_fase) AS fase
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
            LEFT JOIN (
                SELECT UPPER(REPLACE(fcr.nombre_competencia, ' ', '')) as upper_comp, MAX(fp.nombre_fase) as nombre_fase, af.id_ficha
                FROM fase_competencia_resultado fcr
                JOIN actividades_fase af ON fcr.id_actividad = af.id_actividad
                JOIN fases_proyecto fp ON af.id_fase = fp.id_fase
                WHERE fcr.nombre_competencia IS NOT NULL AND fcr.nombre_competencia != ''
                GROUP BY upper_comp, af.id_ficha
            ) fase_map ON (fase_map.upper_comp = UPPER(REPLACE(c.nombre, ' ', '')) AND fase_map.id_ficha = p.id_ficha)
            WHERE 1=1 $whereAll
            GROUP BY p.id_ficha, p.nombre, c.nombre
            ORDER BY p.nombre, p.id_ficha, CASE WHEN MIN(j.fecha_juicio) IS NULL THEN 1 ELSE 0 END, MIN(j.fecha_juicio) ASC, c.nombre";
        $stmtC = $this->db->prepare($sqlComps);
        $stmtC->execute($params);
        $compRows = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        // 3. Find the drop-out point for each withdrawn student
        $sqlRetirados = "SELECT a.documento, CONCAT(a.nombres, ' ', a.apellidos) AS nombre, 
            CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, a.estado,
            COUNT(DISTINCT CASE WHEN j.tipo_juicio = 'Aprobado' THEN c.nombre END) as aprobados_count
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
            WHERE a.estado IN ('Retirado', 'Trasladado') $whereAll
            GROUP BY a.documento, nombre, p.id_ficha, p.nombre, a.estado";
        $stmtR = $this->db->prepare($sqlRetirados);
        $stmtR->execute($params);
        $retirados = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        // Build map: program -> array of students and their dropout index
        $retirosMap = [];
        foreach ($retirados as $ret) {
            $prog = $ret['programa'];
            $idx = (int)$ret['aprobados_count']; // index where they drop out
            if (!isset($retirosMap[$prog])) $retirosMap[$prog] = [];
            if (!isset($retirosMap[$prog][$idx])) $retirosMap[$prog][$idx] = [];
            $retirosMap[$prog][$idx][] = [
                'nombre' => $ret['nombre'],
                'estado' => $ret['estado']
            ];
        }

        // 4. Funcionario por competencia
        $sqlFuncs = "SELECT c.nombre AS competencia, MAX(f.nombre) AS funcionario
            FROM competencias c
            JOIN resultados r ON c.id_resultado = r.id_resultado
            JOIN juicios j ON r.id_juicio = j.id_juicio
            JOIN funcionarios f ON j.id_funcionario = f.documento
            WHERE f.nombre != 'Sin asignar'
            GROUP BY c.nombre";
        $stmtF = $this->db->query($sqlFuncs);
        $funcs = [];
        while ($row = $stmtF->fetch(PDO::FETCH_ASSOC)) {
            $funcs[$row['competencia']] = $row['funcionario'];
        }

        $estructura = [];
        foreach ($totales as $t) {
            $prog = $t['programa'];
            $estructura[$prog] = [
                'programa' => $prog,
                'total_inicial' => (int)$t['total_inicial'],
                'puntos' => []
            ];
        }

        $progComps = [];
        foreach ($compRows as $cr) {
            $progComps[$cr['programa']][] = [
                'nombre' => $cr['competencia'],
                'fase'   => $cr['fase']
            ];
        }

        $survival = [];
        foreach ($estructura as $prog => $datos) {
            $current = $datos['total_inicial'];
            $comps = $progComps[$prog] ?? [];
            $puntosList = [];
            
            foreach ($comps as $idx => $compData) {
                $comp = $compData['nombre'];
                $fase = $compData['fase'];
                $retirosHere = $retirosMap[$prog][$idx] ?? [];
                
                $puntosList[] = [
                    'competencia' => $comp,
                    'fase' => $fase,
                    'aprendices' => $current,
                    'retirados' => $retirosHere,
                    'funcionarios' => isset($funcs[$comp]) ? [$funcs[$comp]] : []
                ];
                
                $current -= count($retirosHere);
                if ($current < 0) $current = 0;
            }
            
            if (!empty($puntosList)) {
                $survival[] = [
                    'programa' => $prog,
                    'puntos' => $puntosList
                ];
            }
        }

        return $survival;
    }
}
