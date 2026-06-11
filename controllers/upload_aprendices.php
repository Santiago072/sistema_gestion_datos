<?php
/**
 * UPLOAD MASIVO DESDE EXCEL (.xlsx) o CSV
 * ─────────────────────────────────────────
 * El archivo Excel de Sofia Plus puede tener estas columnas
 * (se detectan por encabezado, sin importar el orden):
 *
 * APRENDICES:
 *   documento | tipo_documento | nombres | apellidos | estado | ficha | programa
 *
 * COMPETENCIAS / RESULTADOS / JUICIOS (hoja "Juicios" o columnas adicionales):
 *   competencia | resultado_aprendizaje | tipo_juicio | fecha_juicio | documento_funcionario | nombre_funcionario
 *
 * Si el archivo solo tiene datos de aprendices → inserta solo en aprendices+programas
 * Si también tiene competencias/juicios → distribuye a todas las tablas
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/import/ImportAdapterInterface.php';
require_once __DIR__ . '/../services/import/ExcelAdapter.php';
require_once __DIR__ . '/../services/import/CsvAdapter.php';

use Services\Import\ExcelAdapter;
use Services\Import\CsvAdapter;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['archivo'])) {
    echo json_encode(['error' => true, 'message' => 'No se recibió archivo']); exit;
}

$file = $_FILES['archivo'];
$ext  = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ['xlsx', 'xls', 'csv'])) {
    echo json_encode(['error' => true, 'message' => 'Solo se permiten archivos .xlsx, .xls o .csv']); exit;
}

$db = getDB();

// ──────────────────────────────────────────
// 1. LEER FILAS DEL ARCHIVO
// ──────────────────────────────────────────
$allRows = [];

try {
    if ($ext === 'xlsx' || $ext === 'xls') {
        $adapter = new ExcelAdapter($ext);
    } else {
        $adapter = new CsvAdapter();
    }
    $raw = $adapter->parse($file['tmp_name']);
} catch (\Exception $e) {
    echo json_encode(['error' => true, 'message' => $e->getMessage()]); exit;
}

// Buscar la fila de encabezados en las primeras 15 filas
$headerIndex = 0;
foreach (array_slice($raw, 0, 15) as $i => $row) {
    $str = strtolower(implode(' ', $row));
    if (str_contains($str, 'documento') || str_contains($str, 'nombre') || str_contains($str, 'competencia') || str_contains($str, 'tipo_doc')) {
        $headerIndex = $i;
        break;
    }
}

// Remover acentos para nombres
function removeAccents($string) {
    $from = ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'];
    $to   = ['a','e','i','o','u','n','a','e','i','o','u','n'];
    return str_replace($from, $to, $string);
}

// EXTRAER METADATA (Ficha y Programa) de las filas previas al encabezado
$metaFicha = '';
$metaPrograma = '';
foreach (array_slice($raw, 0, $headerIndex) as $row) {
    foreach ($row as $k => $cell) {
        $str = strtolower(removeAccents(trim((string)$cell)));
        // Ficha
        if (str_contains($str, 'ficha')) {
            preg_match('/\d{5,}/', $cell, $m);
            if (!empty($m[0])) $metaFicha = $m[0];
            else {
                for ($j = 1; $j <= 3; $j++) {
                    if (isset($row[$k+$j])) {
                        preg_match('/\d{5,}/', $row[$k+$j], $m2);
                        if (!empty($m2[0])) { $metaFicha = $m2[0]; break; }
                    }
                }
            }
        }
        // Programa / Denominación
        if (str_contains($str, 'programa') || str_contains($str, 'denominacion')) {
            $parts = explode(':', $cell, 2);
            if (count($parts) > 1 && trim($parts[1]) !== '') $metaPrograma = trim($parts[1]);
            else {
                for ($j = 1; $j <= 3; $j++) {
                    if (isset($row[$k+$j]) && trim($row[$k+$j]) !== '') {
                        $metaPrograma = trim($row[$k+$j]);
                        break;
                    }
                }
            }
        }
    }
}

// Normalizar encabezados
$rawHeaders = $raw[$headerIndex];
$headers = [];
$seen = [];
foreach ($rawHeaders as $h) {
    // Reemplazar saltos de línea, barras, puntos y guiones por guiones bajos
    $cleanH = strtolower(trim(preg_replace('/[\s\/\.\-\n\r]+/', '_', removeAccents($h))));
    if (empty($cleanH)) $cleanH = 'col_' . uniqid();
    if (isset($seen[$cleanH])) {
        $seen[$cleanH]++;
        $cleanH .= '_' . $seen[$cleanH];
    } else {
        $seen[$cleanH] = 1;
    }
    $headers[] = $cleanH;
}

// Extraer filas de datos
foreach (array_slice($raw, $headerIndex + 1) as $row) {
    if (array_filter($row, fn($v) => trim((string)$v) !== '')) {
        $paddedRow = array_pad(array_map('trim', $row), count($headers), '');
        // Cortar la fila al tamaño exacto de los encabezados para evitar ValueError en array_combine
        if (count($paddedRow) > count($headers)) {
            $paddedRow = array_slice($paddedRow, 0, count($headers));
        }
        $allRows[] = array_combine($headers, $paddedRow);
    }
}

if (empty($allRows)) {
    echo json_encode(['error' => true, 'message' => 'No se encontraron filas de datos']); exit;
}

// ──────────────────────────────────────────
// 2. DETECTAR COLUMNAS DISPONIBLES
// ──────────────────────────────────────────
$cols = array_keys($allRows[0]);

function findCol(array $cols, array $candidates): ?string {
    foreach ($candidates as $c) {
        foreach ($cols as $col) {
            if (str_contains($col, $c)) return $col;
        }
    }
    return null;
}

// CUIDADO: buscar 'numero_documento' antes de 'documento' para que no cruce con 'tipo_documento'
$colDoc       = findCol($cols, ['numero_de_documento', 'numero_documento', 'num_doc', 'identificacion', 'cedula', 'documento']);
$colTipo      = findCol($cols, ['tipo_de_documento', 'tipo_doc', 'tipo_d']);
$colNombres   = findCol($cols, ['nombres', 'nombre_aprendiz', 'nombre']);
$colApellidos = findCol($cols, ['apellidos', 'apellido_aprendiz', 'apellido']);
$colEstado    = findCol($cols, ['estado']);
$colFicha     = findCol($cols, ['ficha_de_caracterizacion', 'ficha', 'id_ficha', 'numero_ficha']);
$colPrograma  = findCol($cols, ['denominacion', 'programa', 'formacion']);
$colComp      = findCol($cols, ['competencia']);
$colResultado = findCol($cols, ['resultado_de_aprendizaje', 'resultado']);
$colJuicio    = findCol($cols, ['juicio_de_evaluacion', 'juicio', 'tipo_juicio', 'estado_juicio']);
$colFecha     = findCol($cols, ['fecha_y_hora', 'fecha']);
$colFuncDoc   = findCol($cols, ['documento_funcionario', 'doc_func', 'instructor_doc']);
$colFuncNom   = findCol($cols, ['funcionario', 'instructor', 'nombre_func']);

if (!$colDoc) {
    echo json_encode(['error' => true, 'message' => 'No se encontró columna de documento del aprendiz. Columnas detectadas: ' . implode(', ', $cols)]); exit;
}

// ──────────────────────────────────────────
// 3. PREPARED STATEMENTS
// ──────────────────────────────────────────
$stmtPrograma = $db->prepare(
    "INSERT INTO programas(id_ficha, nombre) VALUES(:ficha,:nombre)
     ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)"
);
$stmtAprendiz = $db->prepare(
    "INSERT INTO aprendices(documento,tipo_documento,nombres,apellidos,estado,id_ficha)
     VALUES(:doc,:tipo,:nombres,:apellidos,:estado,:ficha)
     ON DUPLICATE KEY UPDATE
       tipo_documento=VALUES(tipo_documento),
       nombres=VALUES(nombres),
       apellidos=VALUES(apellidos),
       estado=VALUES(estado),
       id_ficha=VALUES(id_ficha)"
);
$stmtFuncionario = $db->prepare(
    "INSERT IGNORE INTO funcionarios(documento,nombre) VALUES(:doc,:nombre)"
);
$stmtJuicio = $db->prepare(
    "INSERT INTO juicios(tipo_juicio, fecha_juicio, id_funcionario, id_ficha, documento_aprendiz)
     VALUES(:tipo, :fecha, :func, :ficha, :aprendiz)"
);
$stmtResultado = $db->prepare(
    "INSERT INTO resultados(nombre,codigo,id_juicio) VALUES(:nombre,:codigo,:id_juicio)"
);
$stmtCompetencia = $db->prepare(
    "INSERT INTO competencias(nombre, codigo, id_aprendiz, id_ficha, id_resultado)
     VALUES(:nombre, :codigo, :aprendiz, :ficha, :resultado)"
);


// Asegurar que el instructor "Sin asignar" exista siempre para evitar fallos de Foreign Key
$db->exec("INSERT IGNORE INTO funcionarios(documento, nombre) VALUES(9999999, 'Sin asignar')");


// ──────────────────────────────────────────
// 4. PROCESAR FILAS
// ──────────────────────────────────────────
$estadosValidos  = ['En formación', 'Retirado', 'Trasladado', 'Egresado'];
$juiciosValidos  = ['Aprobado', 'Por evaluar', 'No aprobado'];

$cntAprendices   = 0;
$cntProgramas    = 0;
$cntJuicios      = 0;
$cntFuncionarios = 0;
$errores         = [];
$row_num         = 1;

// Cache para evitar re-insertar programas/funcionarios
$programasCache    = [];
$funcionariosCache = [];

// Variables para recordar el último aprendiz (por celdas combinadas en Excel)
$lastDoc       = '';
$lastNombres   = '';
$lastApellidos = '';
$lastTipo      = '';
$lastEstado    = '';
$lastFicha     = '';

foreach ($allRows as $row) {
    $row_num++;

    // ── Documento ──
    $doc = preg_replace('/\D/', '', $row[$colDoc] ?? '');
    
    // ── Programa ──
    $fichaRaw   = $colFicha    ? preg_replace('/\D/', '', $row[$colFicha]   ?? '') : '';
    if (empty($fichaRaw)) $fichaRaw = $metaFicha;

    $progNombre = $colPrograma ? trim($row[$colPrograma] ?? '') : '';
    if (empty($progNombre)) $progNombre = $metaPrograma;
    
    if (empty($progNombre)) $progNombre = 'Sin programa';
    $ficha      = (int)($fichaRaw ?: crc32($progNombre) & 0x7FFFFFFF); // ID sintético si no hay ficha

    // Si el documento está vacío, intentamos usar el anterior (celdas combinadas)
    if (empty($doc)) {
        if (!empty($lastDoc)) {
            $doc       = $lastDoc;
            $nombres   = $lastNombres;
            $apellidos = $lastApellidos;
            $tipo      = $lastTipo;
            $estado    = $lastEstado;
            $ficha     = $lastFicha;
        } else {
            $errores[] = "Fila {$row_num}: documento vacío o inválido"; 
            continue; 
        }
    } else {
        // Es un nuevo aprendiz, leemos sus datos
        $nombres   = $colNombres   ? trim($row[$colNombres]   ?? '') : '';
        $apellidos = $colApellidos ? trim($row[$colApellidos] ?? '') : '';

        // Sofia Plus a veces junta nombres y apellidos en una sola columna
        if (empty($nombres) && empty($apellidos)) {
            $fullName  = trim($row[array_key_first($row)] ?? '');
            $parts     = explode(' ', $fullName, 3);
            $nombres   = $parts[0] ?? 'Sin nombre';
            $apellidos = implode(' ', array_slice($parts, 1)) ?: 'Sin apellido';
        }

        // Estado del aprendiz
        $rawEstado = strtoupper(trim($row[$colEstado] ?? ''));
        $estado = 'En formación';
        if (str_contains($rawEstado, 'RETIR') || str_contains($rawEstado, 'DESERC') || str_contains($rawEstado, 'CANCEL')) {
            $estado = 'Retirado';
        } elseif (str_contains($rawEstado, 'TRASLAD')) {
            $estado = 'Trasladado';
        } elseif (str_contains($rawEstado, 'CERTIFI') || str_contains($rawEstado, 'EGRESAD')) {
            $estado = 'Egresado';
        }

        $tipo   = trim($row[$colTipo] ?? 'Cédula de ciudadanía');

        // Guardar para la siguiente fila (por si está combinada)
        $lastDoc       = $doc;
        $lastNombres   = $nombres;
        $lastApellidos = $apellidos;
        $lastTipo      = $tipo;
        $lastEstado    = $estado;
        $lastFicha     = $ficha;
    }

    if ($progNombre && !isset($programasCache[$ficha])) {
        try {
            $stmtPrograma->execute([':ficha' => $ficha, ':nombre' => $progNombre]);
            $programasCache[$ficha] = true;
            $cntProgramas++;
        } catch (PDOException $e) {
            $programasCache[$ficha] = true; // ya existe, ignorar
        }
    }


    try {
        $stmtAprendiz->execute([
            ':doc'      => $doc,
            ':tipo'     => $tipo ?: 'Cédula de ciudadanía',
            ':nombres'  => $nombres  ?: 'Sin nombre',
            ':apellidos'=> $apellidos?: 'Sin apellido',
            ':estado'   => $estado,
            ':ficha'    => $ficha,
        ]);
        $cntAprendices++;
    } catch (PDOException $e) {
        $errores[] = "Fila {$row_num} (aprendiz {$doc}): " . $e->getMessage();
        continue;
    }

    // ── Si hay datos de competencias/juicios ──
    if (!$colComp && !$colResultado) continue;

    $compRaw   = $colComp      ? trim($row[$colComp]      ?? '') : '';
    $resRaw    = $colResultado ? trim($row[$colResultado]  ?? '') : '';

    // Separar código y nombre de Competencia
    $compCodigo = '';
    $compNombre = $compRaw ?: 'Sin competencia';
    if (str_contains($compRaw, ' - ')) {
        $parts = explode(' - ', $compRaw, 2);
        $compCodigo = trim($parts[0]);
        $compNombre = trim($parts[1]);
    }

    // Separar código y nombre de Resultado
    $resCodigo = '';
    $resNombre = $resRaw ?: 'Sin resultado';
    if (str_contains($resRaw, ' ')) {
        $parts = explode(' ', $resRaw, 2);
        if (preg_match('/[0-9]+/', $parts[0])) {
            $resCodigo = trim($parts[0]);
            $resNombre = trim($parts[1]);
        }
    }

    // Funcionario
    $funcDoc = $colFuncDoc ? preg_replace('/\D/', '', $row[$colFuncDoc] ?? '') : '';
    $funcNom = $colFuncNom ? trim($row[$colFuncNom] ?? '') : 'Instructor';
    
    // Limpiar nombres de instructores (quitar CC y separar por guión)
    if ($funcNom === '.' || $funcNom === '-' || empty($funcNom)) {
        $funcNom = 'Sin asignar';
        $funcDoc = '9999999';
    } elseif (str_contains($funcNom, ' - ')) {
        $parts = explode(' - ', $funcNom, 2);
        if (empty($funcDoc)) $funcDoc = preg_replace('/\D/', '', $parts[0]);
        $funcNom = trim($parts[1]);
    } elseif (str_contains($funcNom, '-')) {
        $parts = explode('-', $funcNom, 2);
        if (empty($funcDoc)) $funcDoc = preg_replace('/\D/', '', $parts[0]);
        $funcNom = trim($parts[1]);
    }

    if (empty($funcDoc)) {
        if ($funcNom !== 'Sin asignar' && $funcNom !== 'Instructor') {
            $funcDoc = (string)(abs(crc32($funcNom)) & 0x7FFFFFFF); // ID único basado en el nombre
        } else {
            $funcDoc = '9999999'; // genérico
        }
    }

    if (!isset($funcionariosCache[$funcDoc])) {
        try {
            $stmtFuncionario->execute([':doc' => (int)$funcDoc, ':nombre' => $funcNom ?: 'Instructor']);
            $cntFuncionarios++;
        } catch (PDOException) {}
        $funcionariosCache[$funcDoc] = true;
    }

    // Juicio
    $rawJuicio = strtoupper(trim($row[$colJuicio] ?? ''));
    $tipoJuicio = 'Por evaluar';
    if (str_contains($rawJuicio, 'APROBADO') && !str_contains($rawJuicio, 'NO APROBADO')) {
        $tipoJuicio = 'Aprobado';
    } elseif (str_contains($rawJuicio, 'NO APROBADO') || str_contains($rawJuicio, 'DEFICIENTE')) {
        $tipoJuicio = 'No aprobado';
    }
    
    $fechaJuicio = $colFecha ? trim($row[$colFecha] ?? '') : '';
    if (is_numeric($fechaJuicio) && $fechaJuicio > 10000) {
        $unix = ($fechaJuicio - 25569) * 86400; // Convertir número de serie Excel a Unix Timestamp
        $fechaJuicio = gmdate('Y-m-d H:i:s', $unix);
    }
    if (empty($fechaJuicio)) $fechaJuicio = date('Y-m-d H:i:s');

    try {
        $db->beginTransaction();

        $stmtJuicio->execute([':tipo' => $tipoJuicio, ':fecha' => $fechaJuicio, ':func' => (int)$funcDoc, ':ficha' => $ficha ?: null, ':aprendiz' => $doc ?: null]);
        $idJuicio = $db->lastInsertId();

        $stmtResultado->execute([':nombre' => $resNombre, ':codigo' => $resCodigo, ':id_juicio' => $idJuicio]);
        $idResultado = $db->lastInsertId();

        $stmtCompetencia->execute([':nombre' => $compNombre, ':codigo' => $compCodigo, ':aprendiz' => $doc, ':ficha' => $ficha ?: null, ':resultado' => $idResultado]);

        $db->commit();
        $cntJuicios++;
    } catch (PDOException $e) {
        $db->rollBack();
        $errores[] = "Fila {$row_num} (juicio {$doc}): " . $e->getMessage();
    }
}

// ──────────────────────────────────────────
// 5. RESPUESTA
// ──────────────────────────────────────────
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
