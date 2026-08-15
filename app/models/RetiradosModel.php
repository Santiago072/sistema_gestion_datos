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

        // 2. Lista ordenada de competencias por programa según fecha cronológica de evaluación y fase
        $sqlComps = "SELECT p.id_ficha, CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, c.nombre AS competencia,
            DATE_FORMAT(MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END), '%d/%m/%Y') as primera_fecha,
            MIN(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio ELSE j.fecha_juicio END) as raw_fecha,
            COALESCE(
                (
                    SELECT fp2.nombre_fase
                    FROM fase_competencia_resultado fcr2
                    JOIN actividades_fase af2 ON fcr2.id_actividad = af2.id_actividad
                    JOIN fases_proyecto fp2 ON af2.id_fase = fp2.id_fase
                    WHERE fcr2.id_ficha = p.id_ficha
                      AND (
                          (r.codigo IS NOT NULL AND r.codigo != '' AND fcr2.codigo_resultado = r.codigo)
                          OR (fcr2.codigo_competencia IS NOT NULL AND fcr2.codigo_competencia != '' AND (c.codigo = fcr2.codigo_competencia OR c.nombre LIKE CONCAT('%', fcr2.codigo_competencia, '%')))
                          OR (fcr2.nombre_competencia IS NOT NULL AND fcr2.nombre_competencia != '' AND (c.nombre LIKE CONCAT(fcr2.nombre_competencia, '%') OR fcr2.nombre_competencia LIKE CONCAT(SUBSTRING(c.nombre, 1, 30), '%')))
                      )
                    ORDER BY fp2.orden ASC, af2.id_actividad ASC
                    LIMIT 1
                ),
                'N/A'
            ) AS fase
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
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

        // 3. Obtener aprendices retirados/trasladados y su instructor evaluador directo
        $sqlRetirados = "SELECT a.documento, CONCAT(a.nombres, ' ', a.apellidos) AS nombre, 
            a.id_ficha,
            CONCAT(p.nombre, ' (', p.id_ficha, ')') AS programa, a.estado,
            DATE_FORMAT(MAX(CASE WHEN j.tipo_juicio = 'Aprobado' THEN j.fecha_juicio END), '%d/%m/%Y') as fecha_salida
            FROM aprendices a
            JOIN programas p ON a.id_ficha = p.id_ficha
            LEFT JOIN competencias c ON a.documento = c.id_aprendiz
            LEFT JOIN resultados r ON c.id_resultado = r.id_resultado
            LEFT JOIN juicios j ON r.id_juicio = j.id_juicio
            WHERE a.estado IN ('Retirado', 'Trasladado') $whereAll
            GROUP BY a.documento, nombre, a.id_ficha, p.id_ficha, p.nombre, a.estado";
        $stmtR = $this->db->prepare($sqlRetirados);
        $stmtR->execute($params);
        $retirados = $stmtR->fetchAll(PDO::FETCH_ASSOC);

        $retirosMap = [];
        $stApp = $this->db->prepare("SELECT c.nombre, f.nombre as instructor, r.codigo as res_codigo,
            fp.nombre_fase as fase_salida
            FROM competencias c 
            JOIN resultados r ON c.id_resultado = r.id_resultado 
            JOIN juicios j ON r.id_juicio = j.id_juicio 
            LEFT JOIN funcionarios f ON j.id_funcionario = f.documento
            LEFT JOIN fase_competencia_resultado fcr ON fcr.id_ficha = :ficha AND fcr.codigo_resultado = r.codigo
            LEFT JOIN actividades_fase af ON fcr.id_actividad = af.id_actividad
            LEFT JOIN fases_proyecto fp ON af.id_fase = fp.id_fase
            WHERE c.id_aprendiz = :doc AND j.tipo_juicio = 'Aprobado'
            ORDER BY j.fecha_juicio DESC, c.id_competencia DESC");

        foreach ($retirados as $ret) {
            $prog    = $ret['programa'];
            $doc     = $ret['documento'];
            $fichaId = $ret['id_ficha'];
            $stApp->execute([':doc' => $doc, ':ficha' => $fichaId]);
            $rows = $stApp->fetchAll(PDO::FETCH_ASSOC);

            $maxIdx = -1;
            $evalInstructor = null;
            $faseSalida = null;

            if (isset($progCompIndex[$prog])) {
                foreach ($rows as $row) {
                    $ac = $row['nombre'];
                    if (isset($progCompIndex[$prog][$ac])) {
                        if ($progCompIndex[$prog][$ac] > $maxIdx) {
                            $maxIdx = $progCompIndex[$prog][$ac];
                            if (!empty($row['instructor']) && $row['instructor'] !== 'Sin asignar') {
                                $evalInstructor = $row['instructor'];
                            }
                            if (!empty($row['fase_salida'])) {
                                $faseSalida = $row['fase_salida'];
                            }
                        }
                    }
                }
            }

            $dropIdx = ($maxIdx === -1) ? 0 : $maxIdx;

            if (!isset($retirosMap[$prog])) $retirosMap[$prog] = [];
            if (!isset($retirosMap[$prog][$dropIdx])) $retirosMap[$prog][$dropIdx] = [];
            $retirosMap[$prog][$dropIdx][] = [
                'nombre'       => $ret['nombre'],
                'estado'       => $ret['estado'],
                'fecha_salida' => $ret['fecha_salida'],
                'fase_salida'  => $faseSalida,
                'instructor'   => $evalInstructor
            ];
        }

        // 4. Instructor principal por (ficha + competencia) - único por competencia en cada programa
        $sqlFuncs = "SELECT p.id_ficha, c.nombre AS competencia, f.nombre AS funcionario, COUNT(*) as cnt
            FROM programas p
            JOIN aprendices a ON p.id_ficha = a.id_ficha
            JOIN competencias c ON a.documento = c.id_aprendiz
            JOIN resultados r ON c.id_resultado = r.id_resultado
            JOIN juicios j ON r.id_juicio = j.id_juicio
            JOIN funcionarios f ON j.id_funcionario = f.documento
            WHERE f.nombre IS NOT NULL AND f.nombre != '' AND f.nombre != 'Sin asignar' AND j.tipo_juicio = 'Aprobado'
            GROUP BY p.id_ficha, c.nombre, f.nombre
            ORDER BY cnt DESC";
        $stmtF = $this->db->query($sqlFuncs);
        $funcsByFichaComp = [];
        while ($row = $stmtF->fetch(PDO::FETCH_ASSOC)) {
            $key = $row['id_ficha'] . '_' . $row['competencia'];
            if (!isset($funcsByFichaComp[$key])) {
                $funcsByFichaComp[$key] = $row['funcionario'];
            }
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

                // Si hay aprendices saliendo con su fase individual identificada, ajustar si la competencia marcaba N/A
                $fasesSalida = array_filter(array_column($salieron, 'fase_salida'));
                if (($fase === 'N/A' || empty($fase)) && !empty($fasesSalida)) {
                    $fase = reset($fasesSalida);
                }

                // Obtener el instructor específico individual de los retirados o el instructor principal de la ficha
                $insts = array_filter(array_column($salieron, 'instructor'));
                $singleFunc = !empty($insts) ? reset($insts) : ($funcsByFichaComp[$fichaId . '_' . $compName] ?? null);

                $fArr = [];
                if ($singleFunc) {
                    $fArr[] = $singleFunc;
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
