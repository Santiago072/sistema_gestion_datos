<?php
/**
 * UPLOAD MASIVO DESDE EXCEL (.xlsx/.xls) o CSV
 * ─────────────────────────────────────────────
 * Versión optimizada: Bulk INSERT por lotes + una sola transacción
 * para máximo rendimiento en XAMPP / MariaDB.
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/import/ImportAdapterInterface.php';
require_once __DIR__ . '/../services/import/ExcelAdapter.php';
require_once __DIR__ . '/../services/import/CsvAdapter.php';

use Services\Import\ExcelAdapter;
use Services\Import\CsvAdapter;

header('Content-Type: application/json; charset=utf-8');
set_time_limit(300); // 5 minutos máximo

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo'])) {
    echo json_encode(['error' => true, 'message' => 'No se recibió archivo']); exit;
}

$file = $_FILES['archivo'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['error' => true, 'message' => 'Solo se permiten archivos .xlsx, .xls o .csv']); exit;
}

// ── Guardar archivo temporal ──────────────────────────────────────────────────
$tmpDir = __DIR__ . '/../tmp_uploads';
if (!is_dir($tmpDir)) @mkdir($tmpDir, 0755, true);

$tmpFile = $tmpDir . '/upload_' . uniqid() . '.' . $ext;
if (!move_uploaded_file($file['tmp_name'], $tmpFile)) {
    echo json_encode(['error' => true, 'message' => 'No se pudo guardar el archivo temporal']); exit;
}

// ── Leer archivo ──────────────────────────────────────────────────────────────
if ($ext === 'xlsx' || $ext === 'xls') {
    $adapter = new ExcelAdapter($ext);
} else {
    $adapter = new CsvAdapter();
}

$raw = $adapter->parse($tmpFile);
@unlink($tmpFile); // limpiar temporal

// ── Helper para limpiar acentos ───────────────────────────────────────────────
$removeAccents = fn($s) => str_replace(
    ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'],
    ['a','e','i','o','u','n','a','e','i','o','u','n'], $s
);

// ── Detectar fila de encabezados ──────────────────────────────────────────────
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

// ── Construir mapa de columnas ────────────────────────────────────────────────
$rawHeaders = $raw[$headerIndex];
$headers = [];
$seen    = [];
foreach ($rawHeaders as $h) {
    $ch = strtolower(trim(preg_replace('/[\s\/\.\-\n\r]+/', '_', $removeAccents((string)$h))));
    if (empty($ch)) $ch = 'col_' . uniqid();
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
    echo json_encode(['error' => true, 'message' => 'No se encontraron filas de datos válidas.']); exit;
}

// ── Detectar columnas ─────────────────────────────────────────────────────────
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
    echo json_encode(['error' => true, 'message' => 'No se encontró columna de documento del aprendiz.']); exit;
}

// ── Procesar filas y construir lotes ──────────────────────────────────────────
$db = getDB();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Pre-insertar funcionario genérico
$db->exec("INSERT IGNORE INTO funcionarios(documento, nombre) VALUES(9999999, 'Sin asignar')");

$errores         = [];
$cntAprendices   = 0;
$cntProgramas    = 0;
$cntJuicios      = 0;
$cntFuncionarios = 0;
$row_num         = 1;

// Caches para evitar queries repetidas
$programasCache    = [];
$funcionariosCache = [];

// Datos "anterior" para celdas combinadas en Excel
$lastDoc = $lastNombres = $lastApellidos = $lastTipo = $lastEstado = '';
$lastFicha = 0;

// Lotes para bulk insert
$batchAprendices   = [];
$batchProgramas    = [];
$batchFuncionarios = [];
$batchJuicioData   = []; // datos completos para juicios (necesitan ID del juicio)

$BATCH_SIZE = 500;

// Función para vaciar lote de aprendices
$flushAprendices = function() use (&$batchAprendices, $db, &$cntAprendices) {
    if (empty($batchAprendices)) return;
    $placeholders = implode(',', array_fill(0, count($batchAprendices), '(?,?,?,?,?,?)'));
    $flat = [];
    foreach ($batchAprendices as $r) {
        array_push($flat, $r['doc'], $r['tipo'], $r['nombres'], $r['apellidos'], $r['estado'], $r['ficha']);
    }
    $db->prepare("INSERT INTO aprendices(documento,tipo_documento,nombres,apellidos,estado,id_ficha) VALUES $placeholders ON DUPLICATE KEY UPDATE tipo_documento=VALUES(tipo_documento),nombres=VALUES(nombres),apellidos=VALUES(apellidos),estado=VALUES(estado),id_ficha=VALUES(id_ficha)")
       ->execute($flat);
    $cntAprendices += count($batchAprendices);
    $batchAprendices = [];
};

$flushProgramas = function() use (&$batchProgramas, $db, &$cntProgramas) {
    if (empty($batchProgramas)) return;
    $placeholders = implode(',', array_fill(0, count($batchProgramas), '(?,?)'));
    $flat = [];
    foreach ($batchProgramas as $r) array_push($flat, $r['ficha'], $r['nombre']);
    $db->prepare("INSERT INTO programas(id_ficha,nombre) VALUES $placeholders ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)")
       ->execute($flat);
    $cntProgramas += count($batchProgramas);
    $batchProgramas = [];
};

$flushFuncionarios = function() use (&$batchFuncionarios, $db, &$cntFuncionarios) {
    if (empty($batchFuncionarios)) return;
    $placeholders = implode(',', array_fill(0, count($batchFuncionarios), '(?,?)'));
    $flat = [];
    foreach ($batchFuncionarios as $r) array_push($flat, $r['doc'], $r['nombre']);
    $db->prepare("INSERT IGNORE INTO funcionarios(documento,nombre) VALUES $placeholders")
       ->execute($flat);
    $cntFuncionarios += count($batchFuncionarios);
    $batchFuncionarios = [];
};

// ── TRANSACCIÓN ÚNICA ─────────────────────────────────────────────────────────
$db->beginTransaction();

try {
    foreach ($allRows as $row) {
        $row_num++;

        // Documento
        $doc = preg_replace('/\D/', '', $row[$colDoc] ?? '');

        // Ficha / Programa
        $fichaRaw   = $colFicha   ? preg_replace('/\D/', '', $row[$colFicha] ?? '') : '';
        if (empty($fichaRaw)) $fichaRaw = $metaFicha;
        $progNombre = $colPrograma ? trim($row[$colPrograma] ?? '') : '';
        if (empty($progNombre)) $progNombre = $metaPrograma;
        if (empty($progNombre)) $progNombre = 'Sin programa';
        $ficha = (int)($fichaRaw ?: (crc32($progNombre) & 0x7FFFFFFF));

        // Celdas combinadas — heredar aprendiz anterior
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

        // Acumular programa (sin duplicados)
        if (!isset($programasCache[$ficha])) {
            $batchProgramas[]       = ['ficha' => $ficha, 'nombre' => $progNombre];
            $programasCache[$ficha] = true;
        }

        // Acumular aprendiz
        $batchAprendices[] = ['doc' => $doc, 'tipo' => $tipo, 'nombres' => $nombres ?: 'Sin nombre',
                              'apellidos' => $apellidos ?: 'Sin apellido', 'estado' => $estado, 'ficha' => $ficha];

        // ── Juicio / Competencia / Resultado ────────────────────────────────
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

        $funcDoc = $colFuncDoc ? preg_replace('/\D/', '', $row[$colFuncDoc] ?? '') : '';
        $funcNom = $colFuncNom ? trim($row[$colFuncNom] ?? '') : 'Sin asignar';
        if ($funcNom === '.' || $funcNom === '-' || empty($funcNom)) { $funcNom = 'Sin asignar'; $funcDoc = '9999999'; }
        elseif (str_contains($funcNom, ' - ')) { $p = explode(' - ', $funcNom, 2); if (empty($funcDoc)) $funcDoc = preg_replace('/\D/','',$p[0]); $funcNom = trim($p[1]); }
        elseif (str_contains($funcNom, '-'))   { $p = explode('-', $funcNom, 2);   if (empty($funcDoc)) $funcDoc = preg_replace('/\D/','',$p[0]); $funcNom = trim($p[1]); }
        if (empty($funcDoc)) $funcDoc = ($funcNom !== 'Sin asignar') ? (string)(abs(crc32($funcNom)) & 0x7FFFFFFF) : '9999999';

        if (!isset($funcionariosCache[$funcDoc])) {
            $batchFuncionarios[]       = ['doc' => (int)$funcDoc, 'nombre' => $funcNom ?: 'Instructor'];
            $funcionariosCache[$funcDoc] = true;
        }

        $rawJ = strtoupper(trim($row[$colJuicio] ?? ''));
        $tipoJuicio = 'Por evaluar';
        if (str_contains($rawJ,'APROBADO') && !str_contains($rawJ,'NO APROBADO')) $tipoJuicio = 'Aprobado';
        elseif (str_contains($rawJ,'NO APROBADO') || str_contains($rawJ,'DEFICIENTE')) $tipoJuicio = 'No aprobado';

        $fechaJuicio = $colFecha ? trim($row[$colFecha] ?? '') : '';
        if (is_numeric($fechaJuicio) && $fechaJuicio > 10000) $fechaJuicio = gmdate('Y-m-d H:i:s', ($fechaJuicio - 25569) * 86400);
        if (empty($fechaJuicio)) $fechaJuicio = date('Y-m-d H:i:s');

        // Los juicios necesitan el ID del aprendiz => los insertamos al final cuando los aprendices ya existen
        $batchJuicioData[] = ['doc' => $doc, 'ficha' => $ficha, 'funcDoc' => (int)$funcDoc,
                              'tipoJuicio' => $tipoJuicio, 'fecha' => $fechaJuicio,
                              'compCodigo' => $compCodigo, 'compNombre' => $compNombre,
                              'resCodigo'  => $resCodigo,  'resNombre'  => $resNombre];

        // Vaciar lotes al llegar al tamaño máximo
        if (count($batchAprendices) >= $BATCH_SIZE) {
            $flushProgramas();
            $flushFuncionarios();
            $flushAprendices();
        }
    } // fin foreach

    // Vaciar lo que queda
    $flushProgramas();
    $flushFuncionarios();
    $flushAprendices();

    // ── Insertar juicios, resultados y competencias ───────────────────────────
    // Se hace después para que aprendices y funcionarios ya existan
    if (!empty($batchJuicioData)) {
        $stmtJ = $db->prepare("INSERT INTO juicios(tipo_juicio,fecha_juicio,id_funcionario,id_ficha,documento_aprendiz) VALUES(?,?,?,?,?)");
        $stmtR = $db->prepare("INSERT INTO resultados(nombre,codigo,id_juicio) VALUES(?,?,?)");
        $stmtC = $db->prepare("INSERT INTO competencias(nombre,codigo,id_aprendiz,id_ficha,id_resultado) VALUES(?,?,?,?,?)");

        foreach ($batchJuicioData as $j) {
            try {
                $stmtJ->execute([$j['tipoJuicio'], $j['fecha'], $j['funcDoc'], $j['ficha'] ?: null, $j['doc'] ?: null]);
                $idJ = $db->lastInsertId();
                $stmtR->execute([$j['resNombre'], $j['resCodigo'], $idJ]);
                $idR = $db->lastInsertId();
                $stmtC->execute([$j['compNombre'], $j['compCodigo'], $j['doc'], $j['ficha'] ?: null, $idR]);
                $cntJuicios++;
            } catch (PDOException $e) {
                $errores[] = "Juicio aprendiz {$j['doc']}: " . $e->getMessage();
            }
        }
    }

    $db->commit();

} catch (Throwable $e) {
    $db->rollBack();
    echo json_encode(['error' => true, 'message' => 'Error crítico: ' . $e->getMessage()]); exit;
}

// ── Respuesta ─────────────────────────────────────────────────────────────────
echo json_encode([
    'ok'           => true,
    'total_filas'  => count($allRows),
    'programas'    => $cntProgramas,
    'aprendices'   => $cntAprendices,
    'juicios'      => $cntJuicios,
    'funcionarios' => $cntFuncionarios,
    'errores'      => $errores,
    'columnas_detectadas' => $cols,
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
