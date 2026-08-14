<?php
require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/config/seguridad.php';
require_once dirname(__DIR__) . '/libs/SimpleXLSX.php';
require_once dirname(__DIR__) . '/libs/SimpleXLS.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;

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
            jsonResponse(['error' => true, 'message' => 'No se recibió ningún archivo o hubo un error en la subida.'], 400);
        }

        $file     = $_FILES['archivo'];
        $origName = $file['name'];
        $tmpPath  = $file['tmp_name'];
        $ext      = strtolower(pathinfo($origName, PATHINFO_EXTENSION));

        if (!in_array($ext, ['xlsx', 'xls', 'csv'], true)) {
            jsonResponse(['error' => true, 'message' => "Formato no permitido (.{$ext}). Suba .xlsx, .xls o .csv"], 400);
        }

        // ── 1. Extraer filas brutas ──────────────────────────────────────────
        $rawRows = [];
        if ($ext === 'xlsx') {
            $xlsx = SimpleXLSX::parse($tmpPath);
            if (!$xlsx) {
                jsonResponse(['error' => true, 'message' => 'Error al leer archivo XLSX: ' . SimpleXLSX::parseError()], 400);
            }
            $rawRows = $xlsx->rows();
        } elseif ($ext === 'xls') {
            $xls = SimpleXLS::parse($tmpPath);
            if (!$xls) {
                jsonResponse(['error' => true, 'message' => 'Error al leer archivo XLS: ' . SimpleXLS::parseError()], 400);
            }
            $rawRows = $xls->rows();
        } elseif ($ext === 'csv') {
            $content = file_get_contents($tmpPath);
            $encoding = mb_detect_encoding($content, ['UTF-8', 'ISO-8859-1', 'Windows-1252'], true) ?: 'UTF-8';
            if ($encoding !== 'UTF-8') {
                $content = mb_convert_encoding($content, 'UTF-8', $encoding);
            }
            $firstLine = strtok($content, "\r\n");
            $sep = (substr_count($firstLine, ';') >= substr_count($firstLine, ',')) ? ';' : ',';
            $handle = fopen('php://memory', 'r+');
            fwrite($handle, $content);
            rewind($handle);
            while (($data = fgetcsv($handle, 0, $sep)) !== false) {
                $rawRows[] = $data;
            }
            fclose($handle);
        }

        if (empty($rawRows)) {
            jsonResponse(['error' => true, 'message' => 'El archivo está vacío.'], 400);
        }

        // ── 2. Detección de Metadatos y Encabezados SENA ──────────────────────
        $removeAccents = fn($s) => str_replace(
            ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'],
            ['a','e','i','o','u','n','a','e','i','o','u','n'], $s
        );

        $norm = fn($s) => trim(preg_replace('/\s+/', ' ', $removeAccents(mb_strtolower((string)$s, 'UTF-8'))));

        $metaFicha    = '';
        $metaPrograma = '';
        $headerRowIdx = null;

        foreach ($rawRows as $idx => $row) {
            $joined = $norm(implode(' ', $row));

            if (empty($metaFicha) && preg_match('/(?:ficha|codigo|no\.?)[:\s]*([0-9]{6,10})/i', $joined, $m)) {
                $metaFicha = $m[1];
            }
            if (empty($metaPrograma) && preg_match('/(?:programa(?:\s+de\s+formacion)?|denominacion)[:\s]+([^\n\r\t,;]+)/i', $joined, $m)) {
                $metaPrograma = trim($m[1]);
            }

            if ($headerRowIdx === null) {
                $hasDoc  = str_contains($joined, 'documento') || str_contains($joined, 'identificacion') || str_contains($joined, 'cedula');
                $hasNom  = str_contains($joined, 'nombre')    || str_contains($joined, 'aprendiz');
                $hasComp = str_contains($joined, 'competencia') || str_contains($joined, 'resultado') || str_contains($joined, 'juicio');
                if (($hasDoc && $hasNom) || ($hasDoc && $hasComp)) {
                    $headerRowIdx = $idx;
                }
            }
        }

        if ($headerRowIdx === null) {
            $headerRowIdx = 0;
        }

        $headers = array_map($norm, $rawRows[$headerRowIdx]);
        $dataRows = array_slice($rawRows, $headerRowIdx + 1);

        // Mapeo de columnas
        $colDoc = null; $colTipo = null; $colNombres = null; $colApellidos = null;
        $colEstado = null; $colFicha = null; $colPrograma = null; $colComp = null;
        $colResultado = null; $colJuicio = null; $colFuncDoc = null; $colFuncNom = null;
        $colFecha = null;

        foreach ($headers as $cIdx => $h) {
            if ($colDoc === null && (str_contains($h, 'documento') || str_contains($h, 'num_doc') || str_contains($h, 'identificacion') || $h === 'doc')) {
                if (!str_contains($h, 'funcionario') && !str_contains($h, 'instructor')) $colDoc = $cIdx;
            }
            if ($colTipo === null && (str_contains($h, 'tipo') && (str_contains($h, 'doc') || str_contains($h, 'ident')))) $colTipo = $cIdx;
            if ($colNombres === null && (str_contains($h, 'nombre') && !str_contains($h, 'programa') && !str_contains($h, 'competencia') && !str_contains($h, 'resultado') && !str_contains($h, 'funcionario') && !str_contains($h, 'instructor'))) $colNombres = $cIdx;
            if ($colApellidos === null && str_contains($h, 'apellido')) $colApellidos = $cIdx;
            if ($colEstado === null && (str_contains($h, 'estado') || str_contains($h, 'condicion'))) $colEstado = $cIdx;
            if ($colFicha === null && (str_contains($h, 'ficha') || str_contains($h, 'num_ficha') || str_contains($h, 'grupo'))) $colFicha = $cIdx;
            if ($colPrograma === null && (str_contains($h, 'programa') || str_contains($h, 'denominacion'))) $colPrograma = $cIdx;
            if ($colComp === null && str_contains($h, 'competencia')) $colComp = $cIdx;
            if ($colResultado === null && (str_contains($h, 'resultado') || str_contains($h, 'rap'))) $colResultado = $cIdx;
            if ($colJuicio === null && (str_contains($h, 'juicio') || str_contains($h, 'evaluacion') || str_contains($h, 'concepto') || $h === 'estado_juicio')) $colJuicio = $cIdx;
            if ($colFuncDoc === null && (str_contains($h, 'doc') && (str_contains($h, 'funcionario') || str_contains($h, 'instructor')))) $colFuncDoc = $cIdx;
            if ($colFuncNom === null && ((str_contains($h, 'funcionario') || str_contains($h, 'instructor')) && !str_contains($h, 'doc'))) $colFuncNom = $cIdx;
            if ($colFecha === null && (str_contains($h, 'fecha') || str_contains($h, 'registro'))) $colFecha = $cIdx;
        }

        if ($colDoc === null) {
            $colDoc = 0;
        }

        $db = $this->db;
        $db->beginTransaction();

        $programasCache    = [];
        $funcionariosCache = [];
        $aprendicesCache   = [];

        $batchProgramas    = [];
        $batchFuncionarios = [];
        $batchAprendices   = [];
        $batchJuicioData   = [];

        $cntProgramas    = 0;
        $cntAprendices   = 0;
        $cntJuicios      = 0;
        $cntFuncionarios = 0;
        $errores         = [];

        $flushProgramas = function() use (&$batchProgramas, &$cntProgramas, $db) {
            if (empty($batchProgramas)) return;
            $stmt = $db->prepare("INSERT INTO programas (id_ficha, nombre) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            foreach ($batchProgramas as $p) {
                $stmt->execute([$p['ficha'], $p['nombre']]);
                $cntProgramas++;
            }
            $batchProgramas = [];
        };

        $flushFuncionarios = function() use (&$batchFuncionarios, &$cntFuncionarios, $db) {
            if (empty($batchFuncionarios)) return;
            $stmt = $db->prepare("INSERT INTO funcionarios (documento, nombre) VALUES (?, ?) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            foreach ($batchFuncionarios as $f) {
                $stmt->execute([$f['doc'], $f['nombre']]);
                $cntFuncionarios++;
            }
            $batchFuncionarios = [];
        };

        $flushAprendices = function() use (&$batchAprendices, &$cntAprendices, $db) {
            if (empty($batchAprendices)) return;
            $stmt = $db->prepare("INSERT INTO aprendices (documento, tipo_documento, nombres, apellidos, estado, id_ficha)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE tipo_documento=VALUES(tipo_documento), nombres=VALUES(nombres), apellidos=VALUES(apellidos), estado=VALUES(estado), id_ficha=VALUES(id_ficha)");
            foreach ($batchAprendices as $a) {
                $stmt->execute([$a['doc'], $a['tipo'], $a['nombres'], $a['apellidos'], $a['estado'], $a['ficha']]);
                $cntAprendices++;
            }
            $batchAprendices = [];
        };

        $lastDoc = ''; $lastNombres = ''; $lastApellidos = ''; $lastTipo = ''; $lastEstado = ''; $lastFicha = 0;
        $rowNum = 0;

        try {
            foreach ($dataRows as $row) {
                $rowNum++;
                $doc = preg_replace('/\D/', '', (string)($row[$colDoc] ?? ''));

                $fichaRaw = $colFicha !== null ? preg_replace('/\D/', '', (string)($row[$colFicha] ?? '')) : '';
                if (empty($fichaRaw)) $fichaRaw = $metaFicha;
                $progNombre = $colPrograma !== null ? trim((string)($row[$colPrograma] ?? '')) : '';
                if (empty($progNombre)) $progNombre = $metaPrograma;
                if (empty($progNombre)) $progNombre = 'PROGRAMA ' . ($fichaRaw ?: '000000');

                $ficha = (int)($fichaRaw ?: (crc32($progNombre) & 0x7FFFFFFF));
                if ($ficha <= 0) $ficha = 9999999;

                // Soporte para celdas combinadas SENA (heredar aprendiz de fila anterior)
                if (empty($doc)) {
                    if (!empty($lastDoc)) {
                        [$doc, $nombres, $apellidos, $tipo, $estado, $ficha] = [$lastDoc, $lastNombres, $lastApellidos, $lastTipo, $lastEstado, $lastFicha];
                    } else {
                        continue;
                    }
                } else {
                    $nombres   = $colNombres !== null ? trim((string)($row[$colNombres] ?? '')) : '';
                    $apellidos = $colApellidos !== null ? trim((string)($row[$colApellidos] ?? '')) : '';
                    if (empty($nombres) && empty($apellidos)) {
                        $parts     = explode(' ', trim((string)($row[array_key_first($row)] ?? '')), 2);
                        $nombres   = $parts[0] ?? 'Sin nombre';
                        $apellidos = $parts[1] ?? 'Sin apellido';
                    }
                    $rawE = strtoupper(trim((string)($row[$colEstado] ?? '')));
                    $estado = 'En formación';
                    if (str_contains($rawE, 'RETIR') || str_contains($rawE, 'DESERC') || str_contains($rawE, 'CANCEL')) $estado = 'Retirado';
                    elseif (str_contains($rawE, 'TRASLAD')) $estado = 'Trasladado';
                    elseif (str_contains($rawE, 'CERTIFI') || str_contains($rawE, 'EGRESAD')) $estado = 'Egresado';

                    $tipo = ($colTipo !== null && !empty($row[$colTipo])) ? trim((string)$row[$colTipo]) : 'CC';

                    [$lastDoc, $lastNombres, $lastApellidos, $lastTipo, $lastEstado, $lastFicha] = [$doc, $nombres, $apellidos, $tipo, $estado, $ficha];
                }

                if (!isset($programasCache[$ficha])) {
                    $batchProgramas[] = ['ficha' => $ficha, 'nombre' => $progNombre];
                    $programasCache[$ficha] = true;
                }

                if (!isset($aprendicesCache[$doc])) {
                    $batchAprendices[] = [
                        'doc' => $doc, 'tipo' => $tipo, 'nombres' => $nombres ?: 'Sin nombre',
                        'apellidos' => $apellidos ?: 'Sin apellido', 'estado' => $estado, 'ficha' => $ficha
                    ];
                    $aprendicesCache[$doc] = true;
                }

                if ($colComp === null && $colResultado === null) {
                    continue;
                }

                $compRaw = $colComp !== null ? trim((string)($row[$colComp] ?? '')) : '';
                $resRaw  = $colResultado !== null ? trim((string)($row[$colResultado] ?? '')) : '';

                $compCodigo = ''; $compNombre = $compRaw ?: 'Sin competencia';
                if (str_contains($compRaw, ' - ')) {
                    [$compCodigo, $compNombre] = array_map('trim', explode(' - ', $compRaw, 2));
                }

                $resCodigo = ''; $resNombre = $resRaw ?: 'Sin resultado';
                if (str_contains($resRaw, ' ')) {
                    [$p0, $p1] = array_pad(explode(' ', $resRaw, 2), 2, '');
                    if (preg_match('/^[0-9]+$/', $p0)) {
                        $resCodigo = trim($p0); $resNombre = trim($p1);
                    }
                }
                $resNombre = ltrim($resNombre, "- \t\n\r\0\x0B");

                $funcDoc = $colFuncDoc !== null ? preg_replace('/\D/', '', (string)($row[$colFuncDoc] ?? '')) : '';
                $funcNom = $colFuncNom !== null ? trim((string)($row[$colFuncNom] ?? '')) : 'Sin asignar';
                if ($funcNom === '.' || $funcNom === '-' || empty($funcNom)) { $funcNom = 'Sin asignar'; $funcDoc = '9999999'; }
                elseif (str_contains($funcNom, ' - ')) { $p = explode(' - ', $funcNom, 2); if (empty($funcDoc)) $funcDoc = preg_replace('/\D/','',$p[0]); $funcNom = trim($p[1]); }
                elseif (str_contains($funcNom, '-'))   { $p = explode('-', $funcNom, 2);   if (empty($funcDoc)) $funcDoc = preg_replace('/\D/','',$p[0]); $funcNom = trim($p[1]); }
                if (empty($funcDoc)) $funcDoc = ($funcNom !== 'Sin asignar') ? (string)(abs(crc32($funcNom)) & 0x7FFFFFFF) : '9999999';

                if (!isset($funcionariosCache[$funcDoc])) {
                    $batchFuncionarios[] = ['doc' => (int)$funcDoc, 'nombre' => $funcNom ?: 'Instructor'];
                    $funcionariosCache[$funcDoc] = true;
                }

                $rawJ = strtoupper(trim((string)($row[$colJuicio] ?? '')));
                $tipoJuicio = 'Por evaluar';
                if (str_contains($rawJ, 'APROBADO') && !str_contains($rawJ, 'NO APROBADO')) $tipoJuicio = 'Aprobado';
                elseif (str_contains($rawJ, 'NO APROBADO') || str_contains($rawJ, 'DEFICIENTE')) $tipoJuicio = 'No aprobado';

                $fechaJuicio = $colFecha !== null ? trim((string)($row[$colFecha] ?? '')) : '';
                if (is_numeric($fechaJuicio) && (float)$fechaJuicio > 10000) {
                    $fechaJuicio = gmdate('Y-m-d H:i:s', (int)(($fechaJuicio - 25569) * 86400));
                }
                if (empty($fechaJuicio)) {
                    $fechaJuicio = date('Y-m-d H:i:s');
                }

                $batchJuicioData[] = [
                    'doc' => $doc, 'ficha' => $ficha, 'funcDoc' => (int)$funcDoc,
                    'tipoJuicio' => $tipoJuicio, 'fecha' => $fechaJuicio,
                    'compCodigo' => $compCodigo, 'compNombre' => $compNombre,
                    'resCodigo'  => $resCodigo,  'resNombre'  => $resNombre
                ];
            }

            // ── Flushear primero programas, funcionarios y aprendices ─────────
            $flushProgramas();
            $flushFuncionarios();
            $flushAprendices();

            // ── Insertar juicios, resultados y competencias con FKs aseguradas ──
            if (!empty($batchJuicioData)) {
                $stmtJ = $db->prepare("INSERT INTO juicios(tipo_juicio, fecha_juicio, id_funcionario, id_ficha, documento_aprendiz) VALUES (?, ?, ?, ?, ?)");
                $stmtR = $db->prepare("INSERT INTO resultados(nombre, codigo, id_juicio) VALUES (?, ?, ?)");
                $stmtC = $db->prepare("INSERT INTO competencias(nombre, codigo, id_aprendiz, id_ficha, id_resultado) VALUES (?, ?, ?, ?, ?)");

                foreach ($batchJuicioData as $j) {
                    $stmtJ->execute([$j['tipoJuicio'], $j['fecha'], $j['funcDoc'], $j['ficha'], $j['doc']]);
                    $idJ = $db->lastInsertId();
                    $stmtR->execute([$j['resNombre'], $j['resCodigo'], $idJ]);
                    $idR = $db->lastInsertId();
                    $stmtC->execute([$j['compNombre'], $j['compCodigo'], $j['doc'], $j['ficha'], $idR]);
                    $cntJuicios++;
                }
            }

            $db->commit();

            jsonResponse([
                'ok'                  => true,
                'error'               => false,
                'message'             => "Procesamiento completado con éxito",
                'total_filas'         => count($dataRows),
                'programas'           => count($programasCache),
                'aprendices'          => count($aprendicesCache),
                'funcionarios'        => count($funcionariosCache),
                'juicios'             => $cntJuicios,
                'columnas_detectadas' => array_values(array_filter($headers)),
                'errores'             => $errores,
                'data'                => [
                    'total_procesados' => count($dataRows),
                    'validos'          => count($batchJuicioData) ?: count($aprendicesCache),
                    'aprendices'       => count($aprendicesCache),
                    'programas'        => count($programasCache),
                    'juicios'          => $cntJuicios,
                    'funcionarios'     => count($funcionariosCache)
                ]
            ]);

        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            jsonResponse(['error' => true, 'message' => 'Error durante el procesamiento: ' . $e->getMessage()], 500);
        }
    }
}
