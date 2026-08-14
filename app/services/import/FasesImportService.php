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
            'fases_insertadas'       => 0,
            'actividades_insertadas' => 0,
            'relaciones_insertadas'  => 0,
            'errores'                => [],
            'detalle'                => [],
        ];

        try {
            $this->db->beginTransaction();

            if ($idFicha) {
                $this->db->prepare("DELETE FROM fase_competencia_resultado WHERE id_ficha = ?")->execute([$idFicha]);
                $this->db->prepare("DELETE FROM actividades_fase WHERE id_ficha = ?")->execute([$idFicha]);
                $this->db->prepare("DELETE FROM fases_proyecto WHERE id_ficha = ?")->execute([$idFicha]);
            }

            // 0. Programa info
            if ($idFicha && !empty($data['informacion_basica'])) {
                $ib = $data['informacion_basica'];
                $this->db->prepare(
                    "INSERT INTO programas (id_ficha, nombre, codigo_programa_sofia, nombre_proyecto,
                                            centro_formacion, regional, tiempo_estimado_meses, total_resultados)
                     VALUES (:f, :n, :cs, :np, :cf, :re, :te, :tr)
                     ON DUPLICATE KEY UPDATE
                       codigo_programa_sofia  = VALUES(codigo_programa_sofia),
                       nombre_proyecto        = VALUES(nombre_proyecto),
                       centro_formacion       = VALUES(centro_formacion),
                       regional               = VALUES(regional),
                       tiempo_estimado_meses  = VALUES(tiempo_estimado_meses),
                       total_resultados       = VALUES(total_resultados)"
                )->execute([
                    ':f'  => $idFicha,
                    ':n'  => $ib['programa_formacion'] ?? $ib['nombre_proyecto'] ?? 'Sin nombre',
                    ':cs' => $ib['codigo_programa_sofia']  ?? null,
                    ':np' => $ib['nombre_proyecto']         ?? null,
                    ':cf' => $ib['centro_formacion']        ?? null,
                    ':re' => $ib['regional']                ?? null,
                    ':te' => isset($ib['tiempo_estimado_meses']) ? (int)$ib['tiempo_estimado_meses'] : null,
                    ':tr' => isset($ib['total_resultados_programa']) ? (int)$ib['total_resultados_programa'] : null,
                ]);
                $results['detalle'][] = "✓ Datos del programa actualizados en programas (ficha $idFicha)";
            }

            // 1. Fases
            $faseMap = []; 
            $stmtCheckFase = $this->db->prepare("SELECT id_fase FROM fases_proyecto WHERE nombre_fase = :n AND (id_ficha = :f OR (:f2 IS NULL AND id_ficha IS NULL)) LIMIT 1");
            $stmtInsertFase = $this->db->prepare("INSERT INTO fases_proyecto (nombre_fase, orden, descripcion, id_ficha) VALUES (:n, :o, :d, :f)");

            foreach ($data['fases'] ?? [] as $fase) {
                $nombre = trim($fase['nombre_fase'] ?? '');
                if (!$nombre) continue;

                $stmtCheckFase->execute([':n' => $nombre, ':f' => $idFicha, ':f2' => $idFicha]);
                $existing = $stmtCheckFase->fetch();

                if ($existing) {
                    $faseMap[$nombre] = (int)$existing['id_fase'];
                } else {
                    $stmtInsertFase->execute([
                        ':n' => $nombre,
                        ':o' => (int)($fase['orden'] ?? 1),
                        ':d' => $fase['descripcion'] ?? '',
                        ':f' => $idFicha,
                    ]);
                    $faseMap[$nombre] = (int)$this->db->lastInsertId();
                    $results['fases_insertadas']++;
                }
            }

            // 2. Actividades
            $actMap = []; 
            $stmtCheckAct = $this->db->prepare("SELECT id_actividad FROM actividades_fase WHERE nombre = :n AND id_fase = :f LIMIT 1");
            $stmtInsertAct = $this->db->prepare("INSERT INTO actividades_fase (nombre, descripcion, id_fase, id_ficha) VALUES (:n, :d, :f, :fi)");

            foreach ($data['actividades'] ?? [] as $act) {
                $faseNombre  = trim($act['fase_nombre'] ?? '');
                $actNombre   = mb_substr(trim($act['nombre'] ?? ''), 0, 255);
                $idFaseLocal = $faseMap[$faseNombre] ?? null;

                if (!$idFaseLocal || !$actNombre) continue;

                $mapKey = $faseNombre . '||' . $actNombre;
                if (isset($actMap[$mapKey])) continue;

                $stmtCheckAct->execute([':n' => $actNombre, ':f' => $idFaseLocal]);
                $existing = $stmtCheckAct->fetch();

                if ($existing) {
                    $actMap[$mapKey] = (int)$existing['id_actividad'];
                } else {
                    $stmtInsertAct->execute([
                        ':n'  => $actNombre,
                        ':d'  => $act['descripcion'] ?? '',
                        ':f'  => $idFaseLocal,
                        ':fi' => $idFicha,
                    ]);
                    $actMap[$mapKey] = (int)$this->db->lastInsertId();
                    $results['actividades_insertadas']++;
                }
            }

            // 3. Relaciones (BULK INSERT)
            if (!empty($data['registros'])) {
                $batchRelaciones = [];
                $relInserted = [];
                $hasCodigos = true;

                try {
                    $this->db->query("SELECT codigo_competencia FROM fase_competencia_resultado LIMIT 1");
                } catch (\Exception $e) {
                    $hasCodigos = false;
                }

                $flushRelaciones = function() use (&$batchRelaciones, &$results, $hasCodigos) {
                    if (empty($batchRelaciones)) return;
                    $flat = [];
                    if ($hasCodigos) {
                        $placeholders = implode(',', array_fill(0, count($batchRelaciones), '(?,?,?,?,?,?)'));
                        foreach ($batchRelaciones as $r) array_push($flat, $r['a'], $r['fi'], $r['nc'], $r['nr'], $r['cc'], $r['cr']);
                        $sql = "INSERT INTO fase_competencia_resultado (id_actividad, id_ficha, nombre_competencia, nombre_resultado, codigo_competencia, codigo_resultado) VALUES $placeholders";
                    } else {
                        $placeholders = implode(',', array_fill(0, count($batchRelaciones), '(?,?,?,?)'));
                        foreach ($batchRelaciones as $r) array_push($flat, $r['a'], $r['fi'], $r['nc'], $r['nr']);
                        $sql = "INSERT INTO fase_competencia_resultado (id_actividad, id_ficha, nombre_competencia, nombre_resultado) VALUES $placeholders";
                    }

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
