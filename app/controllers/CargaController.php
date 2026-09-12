<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/services/import/ImportAdapterInterface.php';
require_once dirname(__DIR__) . '/services/import/ExcelAdapter.php';
require_once dirname(__DIR__) . '/services/import/CsvAdapter.php';

use Services\Import\ExcelAdapter;
use Services\Import\CsvAdapter;

class CargaController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function index(): void {
        require dirname(__DIR__) . '/views/carga/index.php';
    }

    public function ajaxUploadExcel(): void {
        requireMethod('POST');
        verificar_rate_limit(15, 60, 'upload_excel');

        ini_set('memory_limit', '512M');
        set_time_limit(300);

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => true, 'message' => 'No se recibió archivo o hubo un error en la subida.'], 400);
        }

        $file = $_FILES['archivo'];
        $ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            jsonResponse(['error' => true, 'message' => 'Solo se permiten archivos .xlsx, .xls o .csv'], 400);
        }

        $tmpDir = dirname(__DIR__, 2) . '/tmp_uploads';
        if (!is_dir($tmpDir)) {
            @mkdir($tmpDir, 0755, true);
        }

        $tmpFile = $tmpDir . '/upload_' . uniqid('', true) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
            jsonResponse(['error' => true, 'message' => 'No se pudo guardar el archivo temporal'], 500);
        }

        // Detectar o recibir fecha de corte del reporte
        $fechaCorte = trim($_POST['fecha_corte'] ?? '');
        if (empty($fechaCorte)) {
            // Extraer del nombre de archivo (ej. 07092026, 06042026, 2026-09-07)
            if (preg_match('/(\d{2})(\d{2})(\d{4})/', $file['name'], $mF)) {
                $dia = (int)$mF[1];
                $mes = (int)$mF[2];
                $ano = (int)$mF[3];
                if (checkdate($mes, $dia, $ano)) {
                    $fechaCorte = sprintf('%04d-%02d-%02d', $ano, $mes, $dia);
                }
            } elseif (preg_match('/(\d{4})[-_](\d{2})[-_](\d{2})/', $file['name'], $mF)) {
                $fechaCorte = "{$mF[1]}-{$mF[2]}-{$mF[3]}";
            }
        }
        if (empty($fechaCorte)) {
            $fechaCorte = date('Y-m-d');
        }

        try {
            if ($ext === 'xlsx' || $ext === 'xls') {
                $adapter = new ExcelAdapter($ext);
            } else {
                $adapter = new CsvAdapter();
            }

            $raw = $adapter->parse($tmpFile);
            @unlink($tmpFile);

            if (empty($raw)) {
                jsonResponse(['error' => true, 'message' => 'El archivo está vacío o no se pudo leer.'], 400);
            }

            $removeAccents = fn($s) => str_replace(
                ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'],
                ['a','e','i','o','u','n','a','e','i','o','u','n'], $s
            );

            // ── Detectar fila de encabezados ──────────────────────────────────────
            $headerIndex = 0;
            $metaFicha   = '';
            $metaPrograma= '';

            foreach (array_slice($raw, 0, 15) as $i => $row) {
                $str = strtolower(implode(' ', $row));
                if (str_contains($str, 'documento') || str_contains($str, 'competencia') || str_contains($str, 'tipo_doc') || str_contains($str, 'nombre')) {
                    $headerIndex = $i;
                    break;
                }
            }

            // Extraer metadata (ficha/programa del encabezado)
            foreach (array_slice($raw, 0, $headerIndex) as $row) {
                foreach ($row as $k => $cell) {
                    $str = strtolower($removeAccents(trim((string)$cell)));
                    if (str_contains($str, 'ficha')) {
                        preg_match('/\d{5,}/', (string)$cell, $m);
                        if (!empty($m[0])) { $metaFicha = $m[0]; continue; }
                        for ($j = 1; $j <= 3; $j++) {
                            if (isset($row[$k+$j])) { preg_match('/\d{5,}/', $row[$k+$j], $m2); if (!empty($m2[0])) { $metaFicha = $m2[0]; break; } }
                        }
                    }
                    if (str_contains($str, 'programa') || str_contains($str, 'denominacion')) {
                        $parts = explode(':', (string)$cell, 2);
                        if (count($parts) > 1 && trim($parts[1]) !== '') { $metaPrograma = trim($parts[1]); continue; }
                        for ($j = 1; $j <= 3; $j++) {
                            if (isset($row[$k+$j]) && trim($row[$k+$j]) !== '') { $metaPrograma = trim($row[$k+$j]); break; }
                        }
                    }
                }
            }

            // ── Construir mapa de columnas ────────────────────────────────────────
            $rawHeaders = $raw[$headerIndex];
            $headers = [];
            $seen    = [];
            foreach ($rawHeaders as $h) {
                $ch = strtolower(trim(preg_replace('/[\s\/\.\-\n\r]+/', '_', $removeAccents((string)$h))));
                if (empty($ch)) $ch = 'col_' . uniqid('', true);
                if (isset($seen[$ch])) { $seen[$ch]++; $ch .= '_' . $seen[$ch]; } else { $seen[$ch] = 1; }
                $headers[] = $ch;
            }

            $allRows = [];
            foreach (array_slice($raw, $headerIndex + 1) as $row) {
                if (array_filter($row, fn($v) => trim((string)$v) !== '')) {
                    $paddedRow = array_slice(array_pad(array_map('trim', $row), count($headers), ''), 0, count($headers));
                    $allRows[] = array_combine($headers, $paddedRow);
                }
            }

            if (empty($allRows)) {
                jsonResponse(['error' => true, 'message' => 'No se encontraron filas de datos válidas.'], 400);
            }

            // ── Detectar columnas ─────────────────────────────────────────────────
            $cols    = array_keys($allRows[0]);
            $findCol = function(array $cols, array $candidates) {
                foreach ($candidates as $c) {
                    foreach ($cols as $col) { if (str_contains($col, $c)) return $col; }
                }
                return null;
            };

            $colDoc       = $findCol($cols, ['numero_de_documento','numero_documento','num_doc','identificacion','cedula','documento']);
            $colTipo      = $findCol($cols, ['tipo_de_documento','tipo_doc','tipo_d']);
            $colNombres   = $findCol($cols, ['nombres','nombre_aprendiz','nombre']);
            $colApellidos = $findCol($cols, ['apellidos','apellido_aprendiz','apellido']);
            $colEstado    = $findCol($cols, ['estado']);
            $colFicha     = $findCol($cols, ['ficha_de_caracterizacion','ficha','id_ficha','numero_ficha']);
            $colPrograma  = $findCol($cols, ['denominacion','programa','formacion']);
            $colComp      = $findCol($cols, ['competencia']);
            $colResultado = $findCol($cols, ['resultado_de_aprendizaje','resultado']);
            $colJuicio    = $findCol($cols, ['juicio_de_evaluacion','juicio','tipo_juicio','estado_juicio']);
            $colFecha     = $findCol($cols, ['fecha_y_hora','fecha']);
            $colFuncDoc   = $findCol($cols, ['documento_funcionario','doc_func','instructor_doc']);
            $colFuncNom   = $findCol($cols, ['funcionario','instructor','nombre_func']);

            if (!$colDoc) {
                jsonResponse(['error' => true, 'message' => 'No se encontró columna de documento del aprendiz.'], 400);
            }

            $db = $this->db;
            $db->beginTransaction();

            $db->exec("INSERT IGNORE INTO funcionarios(documento, nombre) VALUES(9999999, 'Sin asignar')");

            $errores         = [];
            $cntAprendices   = 0;
            $cntProgramas    = 0;
            $cntJuicios      = 0;
            $cntFuncionarios = 0;
            $cntAprobados    = 0;
            $cntPorEvaluar   = 0;
            $cntNoAprobados  = 0;
            $row_num         = 1;

            $programasCache    = [];
            $funcionariosCache = [];
            $fichasAfectadas   = [];
            $aprendicesVistos  = []; // doc => estado

            $lastDoc = $lastNombres = $lastApellidos = $lastTipo = $lastEstado = '';
            $lastFicha = 0;

            $batchAprendices   = [];
            $batchProgramas    = [];
            $batchFuncionarios = [];
            $batchJuicioData   = [];

            $BATCH_SIZE = 500;

            $flushAprendices = function() use (&$batchAprendices, $db) {
                if (empty($batchAprendices)) return;
                $placeholders = implode(',', array_fill(0, count($batchAprendices), '(?,?,?,?,?,?)'));
                $flat = [];
                foreach ($batchAprendices as $r) {
                    array_push($flat, $r['doc'], $r['tipo'], $r['nombres'], $r['apellidos'], $r['estado'], $r['ficha']);
                }
                $db->prepare("INSERT INTO aprendices(documento,tipo_documento,nombres,apellidos,estado,id_ficha) VALUES $placeholders ON DUPLICATE KEY UPDATE tipo_documento=VALUES(tipo_documento),nombres=VALUES(nombres),apellidos=VALUES(apellidos),estado=VALUES(estado),id_ficha=VALUES(id_ficha)")
                   ->execute($flat);
                $batchAprendices = [];
            };

            $flushProgramas = function() use (&$batchProgramas, $db, &$cntProgramas) {
                if (empty($batchProgramas)) return;
                $placeholders = implode(',', array_fill(0, count($batchProgramas), '(?,?)'));
                $flat = [];
                foreach ($batchProgramas as $r) {
                    array_push($flat, $r['ficha'], $r['nombre']);
                }
                $db->prepare("INSERT INTO programas(id_ficha,nombre) VALUES $placeholders ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)")
                   ->execute($flat);
                $cntProgramas += count($batchProgramas);
                $batchProgramas = [];
            };

            $flushFuncionarios = function() use (&$batchFuncionarios, $db, &$cntFuncionarios) {
                if (empty($batchFuncionarios)) return;
                $placeholders = implode(',', array_fill(0, count($batchFuncionarios), '(?,?)'));
                $flat = [];
                foreach ($batchFuncionarios as $r) {
                    array_push($flat, $r['doc'], $r['nombre']);
                }
                $db->prepare("INSERT INTO funcionarios(documento,nombre) VALUES $placeholders ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)")
                   ->execute($flat);
                $cntFuncionarios += count($batchFuncionarios);
                $batchFuncionarios = [];
            };

            foreach ($allRows as $row) {
                $row_num++;

                $doc = preg_replace('/\D/', '', $row[$colDoc] ?? '');

                $fichaRaw   = $colFicha   ? preg_replace('/\D/', '', $row[$colFicha] ?? '') : '';
                if (empty($fichaRaw)) $fichaRaw = $metaFicha;
                $progNombre = $colPrograma ? trim($row[$colPrograma] ?? '') : '';
                if (empty($progNombre)) $progNombre = $metaPrograma;
                if (empty($progNombre)) $progNombre = 'Sin programa';
                $ficha = (int)($fichaRaw ?: (crc32($progNombre) & 0x7FFFFFFF));

                if (!empty($ficha)) {
                    $fichasAfectadas[$ficha] = true;
                }

                if (empty($doc)) {
                    if (!empty($lastDoc)) {
                        [$doc, $nombres, $apellidos, $tipo, $estado, $ficha] =
                            [$lastDoc, $lastNombres, $lastApellidos, $lastTipo, $lastEstado, $lastFicha];
                    } else {
                        $errores[] = "Fila {$row_num}: documento vacío o inválido";
                        continue;
                    }
                } else {
                    $nombres   = $colNombres   ? trim($row[$colNombres]   ?? '') : '';
                    $apellidos = $colApellidos ? trim($row[$colApellidos] ?? '') : '';
                    if (empty($nombres) && empty($apellidos)) {
                        $parts     = explode(' ', trim($row[array_key_first($row)] ?? ''), 3);
                        $nombres   = $parts[0] ?? 'Sin nombre';
                        $apellidos = implode(' ', array_slice($parts, 1)) ?: 'Sin apellido';
                    }
                    $rawE  = strtoupper(trim($row[$colEstado] ?? ''));
                    $estado = 'En formación';
                    if (str_contains($rawE,'RETIR') || str_contains($rawE,'DESERC') || str_contains($rawE,'CANCEL')) $estado = 'Retirado';
                    elseif (str_contains($rawE,'TRASLAD')) $estado = 'Trasladado';
                    elseif (str_contains($rawE,'CERTIFI') || str_contains($rawE,'EGRESAD')) $estado = 'Egresado';
                    $tipo = trim($row[$colTipo] ?? 'Cédula de ciudadanía') ?: 'Cédula de ciudadanía';

                    [$lastDoc, $lastNombres, $lastApellidos, $lastTipo, $lastEstado, $lastFicha] =
                        [$doc, $nombres, $apellidos, $tipo, $estado, $ficha];
                }

                $aprendicesVistos[$doc] = $estado;

                if (!isset($programasCache[$ficha])) {
                    $batchProgramas[]       = ['ficha' => $ficha, 'nombre' => $progNombre];
                    $programasCache[$ficha] = true;
                }

                $batchAprendices[] = [
                    'doc' => $doc, 'tipo' => $tipo, 'nombres' => $nombres ?: 'Sin nombre',
                    'apellidos' => $apellidos ?: 'Sin apellido', 'estado' => $estado, 'ficha' => $ficha
                ];

                if (!$colComp && !$colResultado) {
                    if (count($batchAprendices) >= $BATCH_SIZE) {
                        $flushProgramas();
                        $flushAprendices();
                    }
                    continue;
                }

                $compRaw = $colComp      ? trim($row[$colComp]      ?? '') : '';
                $resRaw  = $colResultado ? trim($row[$colResultado]  ?? '') : '';

                $compCodigo = ''; $compNombre = $compRaw ?: 'Sin competencia';
                if (str_contains($compRaw, ' - ')) { [$compCodigo, $compNombre] = array_map('trim', explode(' - ', $compRaw, 2)); }

                $resCodigo = ''; $resNombre = $resRaw ?: 'Sin resultado';
                if (str_contains($resRaw, ' ')) {
                    [$p0, $p1] = array_pad(explode(' ', $resRaw, 2), 2, '');
                    if (preg_match('/^[0-9]+$/', $p0)) { $resCodigo = trim($p0); $resNombre = trim($p1); }
                }
                $resNombre = ltrim($resNombre, "- \t\n\r\0\x0B");

                // Parsear documento y nombre del funcionario / instructor
                $funcDoc = $colFuncDoc ? preg_replace('/\D/', '', $row[$colFuncDoc] ?? '') : '';
                $funcNom = $colFuncNom ? trim($row[$colFuncNom] ?? '') : 'Sin asignar';
                if ($funcNom === '.' || $funcNom === '-' || empty($funcNom)) {
                    $funcNom = 'Sin asignar';
                    $funcDoc = '9999999';
                } elseif (preg_match('/^(\d+)\s*[-_]\s*(.+)$/', $funcNom, $m)) {
                    if (empty($funcDoc)) $funcDoc = $m[1];
                    $funcNom = trim($m[2]);
                } elseif (str_contains($funcNom, ' - ')) {
                    $p = explode(' - ', $funcNom, 2);
                    if (empty($funcDoc)) $funcDoc = preg_replace('/\D/', '', $p[0]);
                    $funcNom = trim($p[1]);
                } elseif (str_contains($funcNom, '-')) {
                    $p = explode('-', $funcNom, 2);
                    if (empty($funcDoc)) $funcDoc = preg_replace('/\D/', '', $p[0]);
                    $funcNom = trim($p[1]);
                }

                if (empty($funcDoc)) {
                    $funcDoc = ($funcNom !== 'Sin asignar') ? (string)(abs(crc32($funcNom)) & 0x7FFFFFFF) : '9999999';
                }

                if (!isset($funcionariosCache[$funcDoc])) {
                    $batchFuncionarios[]         = ['doc' => (int)$funcDoc, 'nombre' => $funcNom ?: 'Instructor'];
                    $funcionariosCache[$funcDoc] = true;
                }

                $rawJ = strtoupper(trim($row[$colJuicio] ?? ''));
                $tipoJuicio = 'Por evaluar';
                if (str_contains($rawJ,'APROBADO') && !str_contains($rawJ,'NO APROBADO')) {
                    $tipoJuicio = 'Aprobado';
                    $cntAprobados++;
                } elseif (str_contains($rawJ,'NO APROBADO') || str_contains($rawJ,'DEFICIENTE')) {
                    $tipoJuicio = 'No aprobado';
                    $cntNoAprobados++;
                } else {
                    $cntPorEvaluar++;
                }

                $fechaJuicio = $colFecha ? trim($row[$colFecha] ?? '') : '';
                if (is_numeric($fechaJuicio) && (float)$fechaJuicio > 10000) {
                    $fechaJuicio = gmdate('Y-m-d H:i:s', (int)(($fechaJuicio - 25569) * 86400));
                }
                if (empty($fechaJuicio)) {
                    $fechaJuicio = date('Y-m-d H:i:s');
                }

                $batchJuicioData[] = [
                    'doc'        => $doc,
                    'ficha'      => $ficha,
                    'funcDoc'    => (int)$funcDoc,
                    'tipoJuicio' => $tipoJuicio,
                    'fecha'      => $fechaJuicio,
                    'compCodigo' => $compCodigo,
                    'compNombre' => $compNombre,
                    'resCodigo'  => $resCodigo,
                    'resNombre'  => $resNombre
                ];

                if (count($batchAprendices) >= $BATCH_SIZE) {
                    $flushProgramas();
                    $flushFuncionarios();
                    $flushAprendices();
                }
            }

            $flushProgramas();
            $flushFuncionarios();
            $flushAprendices();

            // ── Fusión Inteligente de Corte: Proteger avances (lo Aprobado nunca retrocede) ──
            $fichasNuevas        = [];
            $fichasActualizadas  = [];
            $nuevosAprobados     = 0;
            $antiguosIgnorados   = 0;

            if (!empty($fichasAfectadas) && !empty($batchJuicioData)) {
                $fichasKeys = array_keys($fichasAfectadas);
                $inFichas = implode(',', array_fill(0, count($fichasKeys), '?'));

                // Verificar qué fichas ya existían previamente con juicios en la BD
                $stmtCheckFichas = $db->prepare("SELECT DISTINCT id_ficha FROM juicios WHERE id_ficha IN ($inFichas)");
                $stmtCheckFichas->execute($fichasKeys);
                $fichasConJuicios = $stmtCheckFichas->fetchAll(PDO::FETCH_COLUMN);
                $fichasConJuiciosSet = array_flip($fichasConJuicios);

                foreach ($fichasKeys as $fk) {
                    if (isset($fichasConJuiciosSet[$fk])) {
                        $fichasActualizadas[] = $fk;
                    } else {
                        $fichasNuevas[] = $fk;
                    }
                }

                // 1. Cargar evaluaciones previas existentes en la BD para estas fichas
                $sqlPrev = "SELECT j.id_juicio, j.documento_aprendiz, j.id_ficha, j.tipo_juicio, j.fecha_juicio, j.id_funcionario,
                                   r.id_resultado, r.codigo as res_codigo, r.nombre as res_nombre,
                                   c.id_competencia, c.codigo as comp_codigo, c.nombre as comp_nombre
                            FROM juicios j
                            JOIN resultados r ON r.id_juicio = j.id_juicio
                            JOIN competencias c ON c.id_resultado = r.id_resultado
                            WHERE j.id_ficha IN ($inFichas)";
                $stmtPrev = $db->prepare($sqlPrev);
                $stmtPrev->execute($fichasKeys);
                $prevRows = $stmtPrev->fetchAll(PDO::FETCH_ASSOC);

                // Mapa clave: doc_aprendiz | codigo_resultado (o nombre si no tiene código)
                $mapPrev = [];
                foreach ($prevRows as $pr) {
                    $k = trim($pr['documento_aprendiz']) . '|' . (trim($pr['res_codigo']) ?: trim($pr['res_nombre']));
                    $mapPrev[$k] = $pr;
                }

                // 2. Preparar sentencias para actualización e inserción
                $stmtUpdateJ = $db->prepare("UPDATE juicios SET tipo_juicio = ?, fecha_juicio = ?, id_funcionario = ? WHERE id_juicio = ?");
                $stmtUpdateR = $db->prepare("UPDATE resultados SET nombre = ?, codigo = ? WHERE id_resultado = ?");
                $stmtUpdateC = $db->prepare("UPDATE competencias SET nombre = ?, codigo = ? WHERE id_competencia = ?");

                $stmtInsertJ = $db->prepare("INSERT INTO juicios(tipo_juicio,fecha_juicio,id_funcionario,id_ficha,documento_aprendiz) VALUES(?,?,?,?,?)");
                $stmtInsertR = $db->prepare("INSERT INTO resultados(nombre,codigo,id_juicio) VALUES(?,?,?)");
                $stmtInsertC = $db->prepare("INSERT INTO competencias(nombre,codigo,id_aprendiz,id_ficha,id_resultado) VALUES(?,?,?,?,?)");

                $vistosEnArchivo = [];

                foreach ($batchJuicioData as $j) {
                    $k = trim($j['doc']) . '|' . (trim($j['resCodigo']) ?: trim($j['resNombre']));

                    // Evitar procesar dos veces el mismo resultado para el mismo aprendiz en un mismo archivo
                    if (isset($vistosEnArchivo[$k])) {
                        continue;
                    }
                    $vistosEnArchivo[$k] = true;

                    try {
                        if (isset($mapPrev[$k])) {
                            $prev = $mapPrev[$k];
                            $tipoFinal = $j['tipoJuicio'];
                            $fechaFinal = $j['fecha'];
                            $funcFinal  = $j['funcDoc'];

                            // REGLA DE ORO: Si ya estaba Aprobado en la BD y el corte entrante dice 'Por evaluar' o 'No aprobado',
                            // PRESERVAR el estado Aprobado y su instructor/fecha original para proteger los avances reales.
                            if ($prev['tipo_juicio'] === 'Aprobado' && $j['tipoJuicio'] !== 'Aprobado') {
                                $tipoFinal  = 'Aprobado';
                                $fechaFinal = $prev['fecha_juicio'];
                                $funcFinal  = (int)$prev['id_funcionario'];
                                $antiguosIgnorados++;
                            } else {
                                // Si el archivo entrante aprueba el juicio:
                                if ($j['tipoJuicio'] === 'Aprobado' && $prev['tipo_juicio'] !== 'Aprobado') {
                                    $tipoFinal  = 'Aprobado';
                                    $fechaFinal = $j['fecha'];
                                    $funcFinal  = $j['funcDoc'];
                                    $nuevosAprobados++;
                                }
                            }

                            // Actualizar juicio existente sin duplicar registros
                            $stmtUpdateJ->execute([$tipoFinal, $fechaFinal, $funcFinal, $prev['id_juicio']]);
                            if ($j['resNombre'] !== $prev['res_nombre'] || $j['resCodigo'] !== $prev['res_codigo']) {
                                $stmtUpdateR->execute([$j['resNombre'], $j['resCodigo'], $prev['id_resultado']]);
                            }
                            if ($j['compNombre'] !== $prev['comp_nombre'] || $j['compCodigo'] !== $prev['comp_codigo']) {
                                $stmtUpdateC->execute([$j['compNombre'], $j['compCodigo'], $prev['id_competencia']]);
                            }
                            $cntJuicios++;
                        } else {
                            // Registro completamente nuevo: insertar limpiamente
                            $stmtInsertJ->execute([$j['tipoJuicio'], $j['fecha'], $j['funcDoc'], $j['ficha'] ?: null, $j['doc'] ?: null]);
                            $idJ = $db->lastInsertId();
                            $stmtInsertR->execute([$j['resNombre'], $j['resCodigo'], $idJ]);
                            $idR = $db->lastInsertId();
                            $stmtInsertC->execute([$j['compNombre'], $j['compCodigo'], $j['doc'], $j['ficha'] ?: null, $idR]);
                            $cntJuicios++;
                            if ($j['tipoJuicio'] === 'Aprobado') {
                                $nuevosAprobados++;
                            }
                        }
                    } catch (PDOException $e) {
                        $errores[] = "Juicio aprendiz {$j['doc']}: " . $e->getMessage();
                    }
                }
            }

            $db->commit();
            $fechaSubida = date('Y-m-d H:i:s');

            // Registrar en historial_cortes_reportes
            try {
                $nombreArchivoOriginal = $file['name'];
                $totalFilasCount = count($allRows);
                $stmtHist = $db->prepare("INSERT INTO historial_cortes_reportes (id_ficha, nombre_archivo, fecha_corte, fecha_subida, total_filas, estado) VALUES (?, ?, ?, ?, ?, 'exitoso')");
                if (!empty($fichasKeys)) {
                    foreach ($fichasKeys as $fk) {
                        $stmtHist->execute([(int)$fk, $nombreArchivoOriginal, $fechaCorte, $fechaSubida, $totalFilasCount]);
                    }
                } else {
                    $stmtHist->execute([null, $nombreArchivoOriginal, $fechaCorte, $fechaSubida, $totalFilasCount]);
                }
            } catch (Throwable $eHist) {
                error_log("Error al registrar corte en historial: " . $eHist->getMessage());
            }

            // Consultar las métricas consolidadas reales de las fichas afectadas
            $stmtFichaStats = $db->prepare("
                SELECT
                    SUM(tipo_juicio = 'Aprobado')    AS aprobados,
                    SUM(tipo_juicio = 'Por evaluar') AS por_evaluar,
                    SUM(tipo_juicio = 'No aprobado') AS no_aprobados,
                    COUNT(*)                         AS total_juicios
                FROM juicios
                WHERE id_ficha IN ($inFichas)
            ");
            $stmtFichaStats->execute($fichasKeys);
            $statsFicha = $stmtFichaStats->fetch(PDO::FETCH_ASSOC);

            // Métricas exactas de aprendices únicos según su estado
            $totalActivos    = 0;
            $totalRetirados  = 0;
            $totalTrasladados= 0;
            $totalEgresados  = 0;
            foreach ($aprendicesVistos as $st) {
                if ($st === 'En formación') $totalActivos++;
                elseif ($st === 'Retirado') $totalRetirados++;
                elseif ($st === 'Trasladado') $totalTrasladados++;
                elseif ($st === 'Egresado') $totalEgresados++;
            }

            // Construir mensaje inteligente según si es ficha nueva, corte actualizado o corte antiguo
            $statusTipo = 'actualizada';
            $fichasTexto = implode(', ', $fichasKeys);

            if (!empty($fichasNuevas) && empty($fichasActualizadas)) {
                $statusTipo = 'nueva';
                $mensajePrincipal = "Ficha nueva {$fichasTexto} integrada correctamente.";
            } elseif ($antiguosIgnorados > 0 && $nuevosAprobados === 0) {
                $statusTipo = 'antiguo';
                $mensajePrincipal = "Ficha {$fichasTexto} procesada: El archivo contiene un corte anterior o desactualizado. Se protegieron y mantuvieron intactos los juicios aprobados ya vigentes sin retroceder estados.";
            } elseif ($nuevosAprobados > 0) {
                $statusTipo = 'actualizada';
                $mensajePrincipal = "Ficha {$fichasTexto} actualizada exitosamente con {$nuevosAprobados} nuevos juicios aprobados incorporados.";
            } else {
                $statusTipo = 'sin_cambios';
                $mensajePrincipal = "Ficha {$fichasTexto} verificada y actualizada sin alteraciones en los juicios vigentes.";
            }

            jsonResponse([
                'ok'                        => true,
                'error'                     => false,
                'status_tipo'               => $statusTipo,
                'message'                   => $mensajePrincipal,
                'fichas_texto'              => $fichasTexto,
                'fichas_nuevas'             => $fichasNuevas,
                'fichas_actualizadas'       => $fichasActualizadas,
                'nuevos_aprobados'          => $nuevosAprobados,
                'antiguos_ignorados'        => $antiguosIgnorados,
                'total_filas'               => count($allRows),
                'total_aprendices_activos'  => $totalActivos,
                'total_juicios_aprobados'   => (int)($statsFicha['aprobados'] ?? 0),
                'total_juicios_por_evaluar' => (int)($statsFicha['por_evaluar'] ?? 0),
                'total_juicios_no_aprobados'=> (int)($statsFicha['no_aprobados'] ?? 0),
                'total_programas'           => count($fichasAfectadas) ?: 1,
                'total_retirados'           => $totalRetirados,
                'total_trasladados'         => $totalTrasladados,
                'total_egresados'           => $totalEgresados,
                'total_funcionarios'        => count($funcionariosCache),
                'fichas_actualizadas_count' => count($fichasAfectadas),
                'total_aprendices_unicos'   => count($aprendicesVistos),
                'juicios'                   => (int)($statsFicha['total_juicios'] ?? $cntJuicios),
                'fecha_corte'               => date('d/m/Y', strtotime($fechaCorte)),
                'fecha_corte_raw'           => $fechaCorte,
                'fecha_subida'              => date('d/m/Y h:i:s a', strtotime($fechaSubida)),
                'fecha_subida_raw'          => $fechaSubida,
                'nombre_archivo'            => $nombreArchivoOriginal,
                'columnas_detectadas'       => $cols,
                'errores'                   => $errores
            ]);

        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            @unlink($tmpFile);
            jsonResponse(['error' => true, 'message' => 'Error durante el procesamiento: ' . $e->getMessage()], 500);
        }
    }

    public function ajaxHistorialCortes(): void {
        verificar_rate_limit(60, 60, 'historial_cortes');
        $idFicha = isset($_GET['id_ficha']) && $_GET['id_ficha'] !== '' ? (int)$_GET['id_ficha'] : null;
        $params = [];
        $sql = "SELECT h.id_corte, h.id_ficha, p.nombre as programa_nombre, h.nombre_archivo, 
                       DATE_FORMAT(h.fecha_corte, '%d/%m/%Y') as fecha_corte_formato,
                       h.fecha_corte,
                       DATE_FORMAT(h.fecha_subida, '%d/%m/%Y %h:%i %p') as fecha_subida_formato,
                       h.fecha_subida,
                       h.total_filas, h.estado 
                FROM historial_cortes_reportes h
                LEFT JOIN programas p ON p.id_ficha = h.id_ficha";
        if ($idFicha) {
            $sql .= " WHERE h.id_ficha = ?";
            $params[] = $idFicha;
        }
        $sql .= " ORDER BY h.fecha_corte DESC, h.fecha_subida DESC LIMIT 50";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $cortes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(['ok' => true, 'cortes' => $cortes]);
    }
}
