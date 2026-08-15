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
        $sqlTotales = "SELECT p.id_ficha, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, COUNT(DISTINCT a.documento) AS total_inicial
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            WHERE 1=1 $whereAll
            GROUP BY p.id_ficha, p.nombre";
        $stmtT = $this->db->prepare($sqlTotales);
        $stmtT->execute($params);
        $totales = $stmtT->fetchAll(PDO::FETCH_ASSOC);

        // 2. Lista ordenada de competencias por programa según fecha cronológica de evaluación
        $sqlComps = "SELECT p.id_ficha, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, c.nombre AS competencia,
            DATE_FORMAT(MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END), '%d/%m/%Y') as primera_fecha,
            MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END) as raw_fecha,
            MAX(fase_map.nombre_fase) AS fase
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
            LEFT JOIN (
                SELECT fcr.codigo_resultado, fcr.codigo_competencia, fcr.nombre_competencia, fp.nombre_fase, af.id_ficha
                FROM fase_competencia_resultado fcr
                JOIN actividades_fase af ON fcr.id_actividad = af.id_actividad
                JOIN fases_proyecto fp ON af.id_fase = fp.id_fase
            ) fase_map ON (
                fase_map.id_ficha = p.id_ficha AND (
                    (r.codigo IS NOT NULL AND r.codigo != '' AND r.codigo = fase_map.codigo_resultado) OR
                    (fase_map.nombre_competencia IS NOT NULL AND fase_map.nombre_competencia != '' AND (
                        c.nombre LIKE CONCAT(fase_map.nombre_competencia, '%') OR
                        fase_map.nombre_competencia LIKE CONCAT(SUBSTRING(c.nombre, 1, 30), '%')
                    )) OR
                    (fase_map.codigo_competencia IS NOT NULL AND fase_map.codigo_competencia != '' AND c.nombre LIKE CONCAT('%', fase_map.codigo_competencia, '%'))
                )
            )
            WHERE 1=1 $whereAll
            GROUP BY p.id_ficha, p.nombre, c.nombre
            ORDER BY p.nombre, p.id_ficha, 
                     CASE WHEN MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END) IS NULL THEN 1 ELSE 0 END, 
                     MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END) ASC, 
                     c.nombre";
        $stmtC = $this->db->prepare($sqlComps);
        $stmtC->execute($params);
        $compRows = $stmtC->fetchAll(PDO::FETCH_ASSOC);

        $progComps = [];
        $progCompIndex = [];
        $progIdByProgName = [];
        foreach ($compRows as $cr) {
            $prog = $cr['programa'];
            $progIdByProgName[$prog] = $cr['id_ficha'];
            if (!isset($progComps[$prog])) {
                $progComps[$prog] = [];
                $progCompIndex[$prog] = [];
            }
            $idx = count($progComps[$prog]);
            $progComps[$prog][] = $cr;
            $progCompIndex[$prog][$cr['competencia']] = $idx;
        }

        // 3. Obtener aprendices retirados/trasladados y asociar su salida a su última actividad aprobada (en su fecha real de 2025)
        $sqlRetirados = "SELECT a.documento, CONCAT(a.nombres, ' ', a.apellidos) AS nombre, 
            CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, a.estado,
            DATE_FORMAT(MAX(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio END), '%d/%m/%Y') as fecha_salida
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            LEFT JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
            WHERE a.estado IN ('Retirado', 'Trasladado') $whereAll
            GROUP BY a.documento, nombre, p.id_ficha, p.nombre, a.estado";
        $stmtR = $this->db->prepare($sqlRetirados);
        $stmtR->execute($params);
        $retirados = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        $retirosMap = [];
        $stApp = $this->db->prepare("SELECT DISTINCT c.nombre 
            FROM competencias c 
            JOIN resultados r ON c.id_resultado = r.id_resultado 
            JOIN juicios j ON r.id_juicio = j.id_juicio 
            WHERE c.id_aprendiz = :doc AND j.tipo_juicio = 'Aprobado'");

        foreach ($retirados as $ret) {
            $prog = $ret['programa'];
            $doc  = $ret['documento'];
            $stApp->execute([':doc' => $doc]);
            $approvedComps = $stApp->fetchAll(PDO::FETCH_COLUMN);

            $maxIdx = -1;
            if (isset($progCompIndex[$prog])) {
                foreach ($approvedComps as $ac) {
                    if (isset($progCompIndex[$prog][$ac])) {
                        if ($progCompIndex[$prog][$ac] > $maxIdx) {
                            $maxIdx = $progCompIndex[$prog][$ac];
                        }
                    }
                }
            }

            // Se registra la salida en el último punto donde estuvo activo en 2025
            $dropIdx = ($maxIdx === -1) ? 0 : $maxIdx;

            if (!isset($retirosMap[$prog])) $retirosMap[$prog] = [];
            if (!isset($retirosMap[$prog][$dropIdx])) $retirosMap[$prog][$dropIdx] = [];
            $retirosMap[$prog][$dropIdx][] = [
                'nombre'       => $ret['nombre'],
                'estado'       => $ret['estado'],
                'fecha_salida' => $ret['fecha_salida']
            ];
        }

        // 4. Instructores estrictamente vinculados a cada programa/ficha (para no mezclar docentes de otros excels)
        $sqlFuncs = "SELECT p.id_ficha, c.nombre AS competencia, 
            GROUP_CONCAT(DISTINCT f.nombre ORDER BY f.nombre SEPARATOR ', ') AS funcionario
            FROM programas p
            JOIN aprendices a ON p.id_ficha = a.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            JOIN resultados r ON c.id_resultado = r.id_resultado
            JOIN juicios j ON r.id_juicio = j.id_juicio
            JOIN funcionarios f ON j.id_funcionario = f.documento
            WHERE f.nombre IS NOT NULL AND f.nombre != '' AND f.nombre != 'Sin asignar' AND j.tipo_juicio = 'Aprobado'
            GROUP BY p.id_ficha, c.nombre";
        $stmtF = $this->db->query($sqlFuncs);
        $funcsByFichaComp = [];
        while ($row = $stmtF->fetch(PDO::FETCH_ASSOC)) {
            $funcsByFichaComp[$row['id_ficha'] . '_' . $row['competencia']] = $row['funcionario'];
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

        foreach ($estructura as $prog => &$data) {
            $corriente = $data['total_inicial'];
            $comps = $progComps[$prog] ?? [];
            $fichaId = $progIdByProgName[$prog] ?? null;

            foreach ($comps as $idx => $compInfo) {
                $compName = $compInfo['competencia'];
                $fase     = $compInfo['fase'] ?? 'N/A';
                $fecha    = $compInfo['primera_fecha'] ?? null;
                
                $salieron = $retirosMap[$prog][$idx] ?? [];
                $corriente -= count($salieron);

                $fArr = [];
                $funcKey = $fichaId . '_' . $compName;
                if (isset($funcsByFichaComp[$funcKey])) {
                    $fArr[] = $funcsByFichaComp[$funcKey];
                }

                $fechasSalida = array_filter(array_column($salieron, 'fecha_salida'));
                $fechaSalidaGrupo = !empty($fechasSalida) ? reset($fechasSalida) : $fecha;

                $data['puntos'][] = [
                    'competencia'  => $compName,
                    'fase'         => $fase,
                    'fecha'        => $fecha,
                    'fecha_salida' => $fechaSalidaGrupo,
                    'aprendices'   => max(0, $corriente),
                    'retirados'    => $salieron,
                    'funcionarios' => $fArr
                ];
            }
        }

        return array_values($estructura);
    }
}
