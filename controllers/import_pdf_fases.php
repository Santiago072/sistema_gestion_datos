<?php
/**
 * API: Importar datos del PDF procesado a la BD
 * v2 — Soporta resultado_codigo y competencia_codigo extraídos por Python
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'JSON inválido o vacío']);
    exit;
}

$db      = getDB();
$idFicha = !empty($data['id_ficha']) ? (int)$data['id_ficha'] : null;

$results = [
    'ok'                      => false,
    'fases_insertadas'        => 0,
    'actividades_insertadas'  => 0,
    'relaciones_insertadas'   => 0,
    'errores'                 => [],
    'detalle'                 => [],
];

try {
    $db->beginTransaction();
    
    if ($idFicha) {
        // Limpiar datos previos del PDF para evitar duplicados si se resube el archivo
        $db->prepare("DELETE FROM fase_competencia_resultado WHERE id_ficha = ?")->execute([$idFicha]);
        $db->prepare("DELETE FROM actividades_fase WHERE id_ficha = ?")->execute([$idFicha]);
        $db->prepare("DELETE FROM fases_proyecto WHERE id_ficha = ?")->execute([$idFicha]);
    }

    // ═══════════════════════════════════════════════════════════
    // 0. GUARDAR / ACTUALIZAR datos enriquecidos del programa en `programas`
    //    (los datos vienen de informacion_basica extraídos del PDF)
    // ═══════════════════════════════════════════════════════════
    if ($idFicha && !empty($data['informacion_basica'])) {
        $ib = $data['informacion_basica'];
        $db->prepare(
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

    // ═══════════════════════════════════════════════════════════
    // 1. FASES — insertar las 4 fases únicas vinculadas al programa
    // ═══════════════════════════════════════════════════════════
    $faseMap = []; // nombre_fase => id_fase

    $stmtCheckFase = $db->prepare(
        "SELECT id_fase FROM fases_proyecto
         WHERE nombre_fase = :n
           AND (id_ficha = :f OR (:f2 IS NULL AND id_ficha IS NULL))
         LIMIT 1"
    );
    $stmtInsertFase = $db->prepare(
        "INSERT INTO fases_proyecto (nombre_fase, orden, descripcion, id_ficha)
         VALUES (:n, :o, :d, :f)"
    );

    foreach ($data['fases'] ?? [] as $fase) {
        $nombre = trim($fase['nombre_fase'] ?? '');
        if (!$nombre) continue;

        $stmtCheckFase->execute([':n' => $nombre, ':f' => $idFicha, ':f2' => $idFicha]);
        $existing = $stmtCheckFase->fetch();

        if ($existing) {
            $faseMap[$nombre] = (int)$existing['id_fase'];
            $results['detalle'][] = "Fase '{$nombre}' ya existía (ID: {$existing['id_fase']})";
        } else {
            $stmtInsertFase->execute([
                ':n' => $nombre,
                ':o' => (int)($fase['orden'] ?? 1),
                ':d' => $fase['descripcion'] ?? '',
                ':f' => $idFicha,
            ]);
            $newId = (int)$db->lastInsertId();
            $faseMap[$nombre] = $newId;
            $results['fases_insertadas']++;
            $results['detalle'][] = "✓ Fase '{$nombre}' insertada (ID: $newId)";
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 2. ACTIVIDADES — sin duplicados dentro de la misma fase+programa
    // ═══════════════════════════════════════════════════════════
    $actMap = []; // "fase||nombre" => id_actividad

    $stmtCheckAct = $db->prepare(
        "SELECT id_actividad FROM actividades_fase
         WHERE nombre = :n AND id_fase = :f LIMIT 1"
    );
    $stmtInsertAct = $db->prepare(
        "INSERT INTO actividades_fase (nombre, descripcion, id_fase, id_ficha)
         VALUES (:n, :d, :f, :fi)"
    );

    foreach ($data['actividades'] ?? [] as $act) {
        $faseNombre  = trim($act['fase_nombre'] ?? '');
        $actNombre   = mb_substr(trim($act['nombre'] ?? ''), 0, 255);
        $idFaseLocal = $faseMap[$faseNombre] ?? null;

        if (!$idFaseLocal) {
            $results['errores'][] = "Fase no encontrada para actividad: " . mb_substr($actNombre, 0, 60);
            continue;
        }
        if (!$actNombre) continue;

        $mapKey = $faseNombre . '||' . $actNombre;
        if (isset($actMap[$mapKey])) continue;

        $stmtCheckAct->execute([':n' => $actNombre, ':f' => $idFaseLocal]);
        $existing = $stmtCheckAct->fetch();

        if ($existing) {
            $actMap[$mapKey] = (int)$existing['id_actividad'];
            $results['detalle'][] = "Actividad ya existía: " . mb_substr($actNombre, 0, 60);
        } else {
            $stmtInsertAct->execute([
                ':n'  => $actNombre,
                ':d'  => $act['descripcion'] ?? '',
                ':f'  => $idFaseLocal,
                ':fi' => $idFicha,
            ]);
            $newId = (int)$db->lastInsertId();
            $actMap[$mapKey] = $newId;
            $results['actividades_insertadas']++;
            $results['detalle'][] = "✓ Actividad en '{$faseNombre}': " . mb_substr($actNombre, 0, 60);
        }
    }

    // ═══════════════════════════════════════════════════════════
    // 3. RELACIONES fase_competencia_resultado
    //    Ahora incluye resultado_codigo y competencia_codigo
    // ═══════════════════════════════════════════════════════════
    if (!empty($data['registros'])) {

        $stmtInsertRel = $db->prepare(
            "INSERT INTO fase_competencia_resultado
             (id_actividad, id_ficha, id_competencia, id_resultado,
              nombre_competencia, nombre_resultado,
              codigo_competencia, codigo_resultado)
             VALUES (:a, :fi, NULL, NULL, :nc, :nr, :cc, :cr)"
        );

        // Verificar si las columnas de código existen (por si la migración no se corrió)
        try {
            $db->query("SELECT codigo_competencia FROM fase_competencia_resultado LIMIT 1");
            $hasCodigos = true;
        } catch (\Exception $e) {
            $hasCodigos = false;
            $stmtInsertRel = $db->prepare(
                "INSERT INTO fase_competencia_resultado
                 (id_actividad, id_ficha, id_competencia, id_resultado,
                  nombre_competencia, nombre_resultado)
                 VALUES (:a, :fi, NULL, NULL, :nc, :nr)"
            );
        }

        $relInserted = [];

        foreach ($data['registros'] as $reg) {
            $actNombre  = mb_substr(trim($reg['actividad']   ?? ''), 0, 255);
            $faseNombre = trim($reg['fase'] ?? '');
            $mapKey     = $faseNombre . '||' . $actNombre;
            $actId      = $actMap[$mapKey] ?? null;

            // Intentar buscar por nombre de actividad sin la fase si no coincide
            if (!$actId) {
                foreach ($actMap as $k => $v) {
                    if (str_ends_with($k, '||' . $actNombre)) {
                        $actId = $v;
                        break;
                    }
                }
            }
            if (!$actId) {
                continue; // Silenciosamente ignorar si no hay actividad
            }

            $compNombre    = !empty($reg['competencia'])        ? trim($reg['competencia'])        : null;
            $resNombre     = !empty($reg['resultado_nombre'])   ? trim($reg['resultado_nombre'])   : null;
            $compCodigo    = !empty($reg['competencia_codigo']) ? trim($reg['competencia_codigo']) : null;
            $resCodigo     = !empty($reg['resultado_codigo'])   ? trim($reg['resultado_codigo'])   : null;

            if (!$compNombre && !$resNombre) continue;

            // Clave de deduplicación
            $relKey = $actId . '||' . ($compCodigo ?? $compNombre ?? '') . '||' . ($resCodigo ?? $resNombre ?? '');
            if (isset($relInserted[$relKey])) continue;

            try {
                if ($hasCodigos) {
                    $stmtInsertRel->execute([
                        ':a'  => $actId,
                        ':fi' => $idFicha,
                        ':nc' => $compNombre,
                        ':nr' => $resNombre,
                        ':cc' => $compCodigo,
                        ':cr' => $resCodigo,
                    ]);
                } else {
                    $stmtInsertRel->execute([
                        ':a'  => $actId,
                        ':fi' => $idFicha,
                        ':nc' => $compNombre,
                        ':nr' => $resNombre,
                    ]);
                }
                $relInserted[$relKey] = true;
                $results['relaciones_insertadas']++;
            } catch (\PDOException $e) {
                $results['errores'][] = "Error relación: " . $e->getMessage();
            }
        }
    }

    $db->commit();

    $results['ok']     = true;
    $results['resumen'] = "✓ {$results['fases_insertadas']} fases · "
        . "{$results['actividades_insertadas']} actividades · "
        . "{$results['relaciones_insertadas']} relaciones importadas"
        . (count($results['errores']) ? " · ⚠ " . count($results['errores']) . " errores" : "");

    echo json_encode($results, JSON_UNESCAPED_UNICODE);

} catch (\Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    http_response_code(500);
    echo json_encode([
        'ok'    => false,
        'error' => $e->getMessage(),
    ], JSON_UNESCAPED_UNICODE);
}
