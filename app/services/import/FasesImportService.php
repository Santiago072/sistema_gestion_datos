<?php
namespace Services\Import;

use PDO;

class FasesImportService {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function import(array $data, ?int $idFicha): array {
        $results = [
            'ok'                     => false,
            'id_proyecto'            => null,
            'fases_insertadas'       => 0,
            'actividades_insertadas' => 0,
            'relaciones_insertadas'  => 0,
            'errores'                => [],
            'detalle'                => [],
        ];

        try {
            $this->db->beginTransaction();

            $ib = $data['informacion_basica'] ?? [];
            $codSofia    = !empty($ib['codigo_programa_sofia']) ? trim($ib['codigo_programa_sofia']) : null;
            $nombreProg  = !empty($ib['programa_formacion']) ? trim($ib['programa_formacion']) : null;
            $nombreProy  = !empty($ib['nombre_proyecto']) ? trim($ib['nombre_proyecto']) : ($nombreProg ?: 'Proyecto Formativo');
            $centro      = !empty($ib['centro_formacion']) ? trim($ib['centro_formacion']) : null;
            $regional    = !empty($ib['regional']) ? trim($ib['regional']) : null;
            $tiempoMeses = isset($ib['tiempo_estimado_meses']) ? (int)$ib['tiempo_estimado_meses'] : null;
            $totRes      = isset($ib['total_resultados_programa']) ? (int)$ib['total_resultados_programa'] : null;

            // 0. Buscar si el proyecto formativo ya existe por código SOFIA o nombre de proyecto
            $idProyecto = null;
            if ($codSofia) {
                $stP = $this->db->prepare("SELECT id_proyecto FROM proyectos_formativos WHERE codigo_programa_sofia = ? LIMIT 1");
                $stP->execute([$codSofia]);
                $idProyecto = $stP->fetchColumn() ?: null;
            }
            if (!$idProyecto && $nombreProy) {
                $stP = $this->db->prepare("SELECT id_proyecto FROM proyectos_formativos WHERE nombre_proyecto = ? LIMIT 1");
                $stP->execute([$nombreProy]);
                $idProyecto = $stP->fetchColumn() ?: null;
            }

            if ($idProyecto) {
                // Actualizar datos del proyecto
                $stUp = $this->db->prepare("
                    UPDATE proyectos_formativos SET
                        codigo_programa_sofia = COALESCE(:cs, codigo_programa_sofia),
                        nombre_programa       = COALESCE(:np, nombre_programa),
                        nombre_proyecto       = :proy,
                        centro_formacion      = COALESCE(:cf, centro_formacion),
                        regional              = COALESCE(:re, regional),
                        tiempo_estimado_meses = COALESCE(:te, tiempo_estimado_meses),
                        total_resultados      = COALESCE(:tr, total_resultados)
                    WHERE id_proyecto = :id
                ");
                $stUp->execute([
                    ':cs'   => $codSofia,
                    ':np'   => $nombreProg,
                    ':proy' => $nombreProy,
                    ':cf'   => $centro,
                    ':re'   => $regional,
                    ':te'   => $tiempoMeses,
                    ':tr'   => $totRes,
                    ':id'   => $idProyecto,
                ]);
                $results['detalle'][] = "✓ Proyecto formativo existente actualizado (ID: $idProyecto)";
            } else {
                // Insertar nuevo proyecto formativo independiente
                $stIns = $this->db->prepare("
                    INSERT INTO proyectos_formativos (
                        codigo_programa_sofia, nombre_programa, nombre_proyecto,
                        centro_formacion, regional, tiempo_estimado_meses, total_resultados
                    ) VALUES (:cs, :np, :proy, :cf, :re, :te, :tr)
                ");
                $stIns->execute([
                    ':cs'   => $codSofia,
                    ':np'   => $nombreProg,
                    ':proy' => $nombreProy,
                    ':cf'   => $centro,
                    ':re'   => $regional,
                    ':te'   => $tiempoMeses,
                    ':tr'   => $totRes,
                ]);
                $idProyecto = (int)$this->db->lastInsertId();
                $results['detalle'][] = "✓ Nuevo proyecto formativo registrado (ID: $idProyecto)";
            }

            $results['id_proyecto'] = $idProyecto;

            // Asociar ficha si fue provista
            if ($idFicha) {
                $stProg = $this->db->prepare("UPDATE programas SET id_proyecto = :p WHERE id_ficha = :f");
                $stProg->execute([':p' => $idProyecto, ':f' => $idFicha]);
                $results['detalle'][] = "✓ Ficha $idFicha asociada al proyecto formativo $idProyecto";
            }

            // Limpiar datos previos de fases/actividades de ESTE PROYECTO para recargar limpio
            $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_proyecto = ?")->execute([$idProyecto]);
            $this->db->prepare("DELETE FROM actividades_fase WHERE id_proyecto = ?")->execute([$idProyecto]);
            $this->db->prepare("DELETE FROM fases_proyecto WHERE id_proyecto = ?")->execute([$idProyecto]);

            // 1. Fases
            $faseMap = []; 
            $stmtInsertFase = $this->db->prepare("INSERT INTO fases_proyecto (nombre_fase, orden, descripcion, id_proyecto, id_ficha) VALUES (:n, :o, :d, :p, :f)");

            foreach ($data['fases'] ?? [] as $fase) {
                $nombre = trim($fase['nombre_fase'] ?? '');
                if (!$nombre) continue;

                $stmtInsertFase->execute([
                    ':n' => $nombre,
                    ':o' => (int)($fase['orden'] ?? 1),
                    ':d' => $fase['descripcion'] ?? '',
                    ':p' => $idProyecto,
                    ':f' => $idFicha,
                ]);
                $faseMap[$nombre] = (int)$this->db->lastInsertId();
                $results['fases_insertadas']++;
            }

            // 2. Actividades
            $actMap = []; 
            $stmtInsertAct = $this->db->prepare("INSERT INTO actividades_fase (nombre, descripcion, id_fase, id_proyecto, id_ficha) VALUES (:n, :d, :f, :p, :fi)");

            foreach ($data['actividades'] ?? [] as $act) {
                $faseNombre  = trim($act['fase_nombre'] ?? '');
                $actNombre   = mb_substr(trim($act['nombre'] ?? ''), 0, 255);
                $idFaseLocal = $faseMap[$faseNombre] ?? null;

                if (!$idFaseLocal || !$actNombre) continue;

                $mapKey = $faseNombre . '||' . $actNombre;
                if (isset($actMap[$mapKey])) continue;

                $stmtInsertAct->execute([
                    ':n'  => $actNombre,
                    ':d'  => $act['descripcion'] ?? '',
                    ':f'  => $idFaseLocal,
                    ':p'  => $idProyecto,
                    ':fi' => $idFicha,
                ]);
                $actMap[$mapKey] = (int)$this->db->lastInsertId();
                $results['actividades_insertadas']++;
            }

            // 3. Relaciones (BULK INSERT)
            if (!empty($data['registros'])) {
                $batchRelaciones = [];
                $relInserted = [];

                $flushRelaciones = function() use (&$batchRelaciones, &$results) {
                    if (empty($batchRelaciones)) return;
                    $flat = [];
                    $placeholders = implode(',', array_fill(0, count($batchRelaciones), '(?,?,?,?,?,?,?)'));
                    foreach ($batchRelaciones as $r) {
                        array_push($flat, $r['a'], $r['p'], $r['fi'], $r['nc'], $r['nr'], $r['cc'], $r['cr']);
                    }
                    $sql = "INSERT INTO fase_competencia_resultado 
                            (id_actividad, id_proyecto, id_ficha, nombre_competencia, nombre_resultado, codigo_competencia, codigo_resultado) 
                            VALUES $placeholders";

                    try {
                        $this->db->prepare($sql)->execute($flat);
                        $results['relaciones_insertadas'] += count($batchRelaciones);
                    } catch (\PDOException $e) {
                        $results['errores'][] = "Error en lote: " . $e->getMessage();
                    }
                    $batchRelaciones = [];
                };

                foreach ($data['registros'] as $reg) {
                    $actNombre  = mb_substr(trim($reg['actividad']   ?? ''), 0, 255);
                    $faseNombre = trim($reg['fase'] ?? '');
                    $mapKey     = $faseNombre . '||' . $actNombre;
                    $actId      = $actMap[$mapKey] ?? null;

                    if (!$actId) {
                        foreach ($actMap as $k => $v) {
                            if (str_ends_with($k, '||' . $actNombre)) {
                                $actId = $v; break;
                            }
                        }
                    }
                    if (!$actId) continue; 

                    $compNombre = !empty($reg['competencia'])        ? trim($reg['competencia'])        : null;
                    $resNombre  = !empty($reg['resultado_nombre'])   ? trim($reg['resultado_nombre'])   : null;
                    $compCodigo = !empty($reg['competencia_codigo']) ? trim($reg['competencia_codigo']) : null;
                    $resCodigo  = !empty($reg['resultado_codigo'])   ? trim($reg['resultado_codigo'])   : null;

                    if (!$compNombre && !$resNombre) continue;

                    $relKey = $actId . '||' . ($compCodigo ?? $compNombre ?? '') . '||' . ($resCodigo ?? $resNombre ?? '');
                    if (isset($relInserted[$relKey])) continue;

                    $batchRelaciones[] = [
                        'a'  => $actId,
                        'p'  => $idProyecto,
                        'fi' => $idFicha,
                        'nc' => $compNombre,
                        'nr' => $resNombre,
                        'cc' => $compCodigo,
                        'cr' => $resCodigo
                    ];
                    $relInserted[$relKey] = true;

                    if (count($batchRelaciones) >= 500) {
                        $flushRelaciones();
                    }
                }
                $flushRelaciones();
            }

            $this->db->commit();
            $results['ok'] = true;
            $results['resumen'] = "✓ {$results['fases_insertadas']} fases · {$results['actividades_insertadas']} actividades · {$results['relaciones_insertadas']} relaciones";
            if (count($results['errores'])) {
                $results['resumen'] .= " · ⚠ " . count($results['errores']) . " errores";
            }

        } catch (\Exception $e) {
            $this->db->rollBack();
            $results['errores'][] = $e->getMessage();
        }

        return $results;
    }
}
