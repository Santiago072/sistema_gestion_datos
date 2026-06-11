<?php
/**
 * Worker en segundo plano para procesar tareas asíncronas de la cola.
 * Uso (CLI): php worker.php
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../services/import/ImportAdapterInterface.php';
require_once __DIR__ . '/../services/import/ExcelAdapter.php';
require_once __DIR__ . '/../services/import/CsvAdapter.php';
require_once __DIR__ . '/../services/queue/JobQueue.php';

use Services\Import\ExcelAdapter;
use Services\Import\CsvAdapter;
use Services\Queue\JobQueue;

$db = getDB();
$queue = new JobQueue($db);

echo "Buscando trabajos pendientes...\n";
$job = $queue->claimNextJob();

if (!$job) {
    echo "No hay trabajos pendientes.\n";
    exit;
}

echo "Procesando trabajo ID: {$job['id']} (Tipo: {$job['tipo']})\n";

try {
    if ($job['tipo'] === 'excel_aprendices') {
        procesarExcelAprendices($job, $db, $queue);
    } else {
        throw new \Exception("Tipo de trabajo desconocido: {$job['tipo']}");
    }
} catch (\Exception $e) {
    $queue->failJob($job['id'], $e->getMessage());
    echo "Error procesando el trabajo: " . $e->getMessage() . "\n";
}

function procesarExcelAprendices(array $job, \PDO $db, JobQueue $queue) {
    $filePath = $job['ruta_archivo'];
    if (!file_exists($filePath)) {
        throw new \Exception("El archivo a procesar no existe: " . $filePath);
    }

    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($ext === 'xlsx' || $ext === 'xls') {
        $adapter = new ExcelAdapter($ext);
    } else {
        $adapter = new CsvAdapter();
    }

    $raw = $adapter->parse($filePath);

    // Buscar encabezados
    $headerIndex = 0;
    foreach (array_slice($raw, 0, 15) as $i => $row) {
        $str = strtolower(implode(' ', $row));
        if (str_contains($str, 'documento') || str_contains($str, 'nombre') || str_contains($str, 'competencia') || str_contains($str, 'tipo_doc')) {
            $headerIndex = $i;
            break;
        }
    }

    // Helper para limpiar acentos
    $removeAccents = function($string) {
        $from = ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'];
        $to   = ['a','e','i','o','u','n','a','e','i','o','u','n'];
        return str_replace($from, $to, $string);
    };

    // EXTRAER METADATA
    $metaFicha = '';
    $metaPrograma = '';
    foreach (array_slice($raw, 0, $headerIndex) as $row) {
        foreach ($row as $k => $cell) {
            $str = strtolower($removeAccents(trim((string)$cell)));
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

    $rawHeaders = $raw[$headerIndex];
    $headers = [];
    $seen = [];
    foreach ($rawHeaders as $h) {
        $cleanH = strtolower(trim(preg_replace('/[\s\/\.\-\n\r]+/', '_', $removeAccents($h))));
        if (empty($cleanH)) $cleanH = 'col_' . uniqid();
        if (isset($seen[$cleanH])) {
            $seen[$cleanH]++;
            $cleanH .= '_' . $seen[$cleanH];
        } else {
            $seen[$cleanH] = 1;
        }
        $headers[] = $cleanH;
    }

    $allRows = [];
    foreach (array_slice($raw, $headerIndex + 1) as $row) {
        if (array_filter($row, fn($v) => trim((string)$v) !== '')) {
            $paddedRow = array_pad(array_map('trim', $row), count($headers), '');
            if (count($paddedRow) > count($headers)) {
                $paddedRow = array_slice($paddedRow, 0, count($headers));
            }
            $allRows[] = array_combine($headers, $paddedRow);
        }
    }

    if (empty($allRows)) {
        throw new \Exception('No se encontraron filas de datos válidas.');
    }

    $cols = array_keys($allRows[0]);
    $findCol = function(array $cols, array $candidates) {
        foreach ($candidates as $c) {
            foreach ($cols as $col) {
                if (str_contains($col, $c)) return $col;
            }
        }
        return null;
    };

    $colDoc       = $findCol($cols, ['numero_de_documento', 'numero_documento', 'num_doc', 'identificacion', 'cedula', 'documento']);
    $colTipo      = $findCol($cols, ['tipo_de_documento', 'tipo_doc', 'tipo_d']);
    $colNombres   = $findCol($cols, ['nombres', 'nombre_aprendiz', 'nombre']);
    $colApellidos = $findCol($cols, ['apellidos', 'apellido_aprendiz', 'apellido']);
    $colEstado    = $findCol($cols, ['estado']);
    $colFicha     = $findCol($cols, ['ficha_de_caracterizacion', 'ficha', 'id_ficha', 'numero_ficha']);
    $colPrograma  = $findCol($cols, ['denominacion', 'programa', 'formacion']);
    $colComp      = $findCol($cols, ['competencia']);
    $colResultado = $findCol($cols, ['resultado_de_aprendizaje', 'resultado']);
    $colJuicio    = $findCol($cols, ['juicio_de_evaluacion', 'juicio', 'tipo_juicio', 'estado_juicio']);
    $colFecha     = $findCol($cols, ['fecha_y_hora', 'fecha']);
    $colFuncDoc   = $findCol($cols, ['documento_funcionario', 'doc_func', 'instructor_doc']);
    $colFuncNom   = $findCol($cols, ['funcionario', 'instructor', 'nombre_func']);

    if (!$colDoc) {
        throw new \Exception('No se encontró columna de documento del aprendiz.');
    }

    $stmtPrograma = $db->prepare("INSERT INTO programas(id_ficha, nombre) VALUES(:ficha,:nombre) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
    $stmtAprendiz = $db->prepare("INSERT INTO aprendices(documento,tipo_documento,nombres,apellidos,estado,id_ficha) VALUES(:doc,:tipo,:nombres,:apellidos,:estado,:ficha) ON DUPLICATE KEY UPDATE tipo_documento=VALUES(tipo_documento), nombres=VALUES(nombres), apellidos=VALUES(apellidos), estado=VALUES(estado), id_ficha=VALUES(id_ficha)");
    $stmtFuncionario = $db->prepare("INSERT IGNORE INTO funcionarios(documento,nombre) VALUES(:doc,:nombre)");
    $stmtJuicio = $db->prepare("INSERT INTO juicios(tipo_juicio, fecha_juicio, id_funcionario, id_ficha, documento_aprendiz) VALUES(:tipo, :fecha, :func, :ficha, :aprendiz)");
    $stmtResultado = $db->prepare("INSERT INTO resultados(nombre,codigo,id_juicio) VALUES(:nombre,:codigo,:id_juicio)");
    $stmtCompetencia = $db->prepare("INSERT INTO competencias(nombre, codigo, id_aprendiz, id_ficha, id_resultado) VALUES(:nombre, :codigo, :aprendiz, :ficha, :resultado)");

    $db->exec("INSERT IGNORE INTO funcionarios(documento, nombre) VALUES(9999999, 'Sin asignar')");

    $cntAprendices   = 0;
    $cntProgramas    = 0;
    $cntJuicios      = 0;
    $cntFuncionarios = 0;
    $errores         = [];
    $row_num         = 1;

    $programasCache    = [];
    $funcionariosCache = [];

    $lastDoc       = '';
    $lastNombres   = '';
    $lastApellidos = '';
    $lastTipo      = '';
    $lastEstado    = '';
    $lastFicha     = '';

    $totalRows = count($allRows);

    $db->beginTransaction();
    $batchCount = 0;

    foreach ($allRows as $index => $row) {
        $row_num++;
        $batchCount++;
        
        // Reportar progreso y comitear en lotes de 500 para máximo rendimiento
        if ($batchCount >= 500) {
            $db->commit();
            $db->beginTransaction();
            $batchCount = 0;
            
            $progreso = (int)(($index / $totalRows) * 100);
            $queue->updateProgress($job['id'], $progreso);
        }

        $doc = preg_replace('/\D/', '', $row[$colDoc] ?? '');
        $fichaRaw   = $colFicha    ? preg_replace('/\D/', '', $row[$colFicha]   ?? '') : '';
        if (empty($fichaRaw)) $fichaRaw = $metaFicha;

        $progNombre = $colPrograma ? trim($row[$colPrograma] ?? '') : '';
        if (empty($progNombre)) $progNombre = $metaPrograma;
        if (empty($progNombre)) $progNombre = 'Sin programa';
        $ficha = (int)($fichaRaw ?: crc32($progNombre) & 0x7FFFFFFF);

        if (empty($doc)) {
            if (!empty($lastDoc)) {
                $doc       = $lastDoc;
                $nombres   = $lastNombres;
                $apellidos = $lastApellidos;
                $tipo      = $lastTipo;
                $estado    = $lastEstado;
                $ficha     = $lastFicha;
            } else {
                $msg = "documento vaco o invǭlido";
                $errores[] = "Fila {$row_num}: " . $msg;
                $queue->logError($job['id'], $row_num, $msg);
                continue; 
            }
        } else {
            $nombres   = $colNombres   ? trim($row[$colNombres]   ?? '') : '';
            $apellidos = $colApellidos ? trim($row[$colApellidos] ?? '') : '';

            if (empty($nombres) && empty($apellidos)) {
                $fullName  = trim($row[array_key_first($row)] ?? '');
                $parts     = explode(' ', $fullName, 3);
                $nombres   = $parts[0] ?? 'Sin nombre';
                $apellidos = implode(' ', array_slice($parts, 1)) ?: 'Sin apellido';
            }

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
            } catch (\PDOException $e) {
                $programasCache[$ficha] = true;
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
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $errores[] = "Fila {$row_num} (aprendiz {$doc}): " . $msg;
            $queue->logError($job['id'], $row_num, "Error guardando aprendiz {$doc}: " . $msg);
        }

        if (!$colComp && !$colResultado) continue;

        $compRaw   = $colComp      ? trim($row[$colComp]      ?? '') : '';
        $resRaw    = $colResultado ? trim($row[$colResultado]  ?? '') : '';

        $compCodigo = '';
        $compNombre = $compRaw ?: 'Sin competencia';
        if (str_contains($compRaw, ' - ')) {
            $parts = explode(' - ', $compRaw, 2);
            $compCodigo = trim($parts[0]);
            $compNombre = trim($parts[1]);
        }

        $resCodigo = '';
        $resNombre = $resRaw ?: 'Sin resultado';
        if (str_contains($resRaw, ' ')) {
            $parts = explode(' ', $resRaw, 2);
            if (preg_match('/[0-9]+/', $parts[0])) {
                $resCodigo = trim($parts[0]);
                $resNombre = trim($parts[1]);
            }
        }

        $funcDoc = $colFuncDoc ? preg_replace('/\D/', '', $row[$colFuncDoc] ?? '') : '';
        $funcNom = $colFuncNom ? trim($row[$colFuncNom] ?? '') : 'Instructor';
        
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
                $funcDoc = (string)(abs(crc32($funcNom)) & 0x7FFFFFFF);
            } else {
                $funcDoc = '9999999';
            }
        }

        if (!isset($funcionariosCache[$funcDoc])) {
            try {
                $stmtFuncionario->execute([':doc' => (int)$funcDoc, ':nombre' => $funcNom ?: 'Instructor']);
                $cntFuncionarios++;
            } catch (\PDOException $e) {}
            $funcionariosCache[$funcDoc] = true;
        }

        $rawJuicio = strtoupper(trim($row[$colJuicio] ?? ''));
        $tipoJuicio = 'Por evaluar';
        if (str_contains($rawJuicio, 'APROBADO') && !str_contains($rawJuicio, 'NO APROBADO')) {
            $tipoJuicio = 'Aprobado';
        } elseif (str_contains($rawJuicio, 'NO APROBADO') || str_contains($rawJuicio, 'DEFICIENTE')) {
            $tipoJuicio = 'No aprobado';
        }
        
        $fechaJuicio = $colFecha ? trim($row[$colFecha] ?? '') : '';
        if (is_numeric($fechaJuicio) && $fechaJuicio > 10000) {
            $unix = ($fechaJuicio - 25569) * 86400;
            $fechaJuicio = gmdate('Y-m-d H:i:s', $unix);
        }
        if (empty($fechaJuicio)) $fechaJuicio = date('Y-m-d H:i:s');

        try {
            $stmtJuicio->execute([':tipo' => $tipoJuicio, ':fecha' => $fechaJuicio, ':func' => (int)$funcDoc, ':ficha' => $ficha ?: null, ':aprendiz' => $doc ?: null]);
            $idJuicio = $db->lastInsertId();

            $stmtResultado->execute([':nombre' => $resNombre, ':codigo' => $resCodigo, ':id_juicio' => $idJuicio]);
            $idResultado = $db->lastInsertId();

            $stmtCompetencia->execute([':nombre' => $compNombre, ':codigo' => $compCodigo, ':aprendiz' => $doc, ':ficha' => $ficha ?: null, ':resultado' => $idResultado]);
            $cntJuicios++;
        } catch (\PDOException $e) {
            $msg = $e->getMessage();
            $errores[] = "Fila {$row_num} (juicio {$doc}): " . $msg;
            $queue->logError($job['id'], $row_num, "Error guardando juicio para aprendiz {$doc}: " . $msg);
        }
    }
    
    // Commit del último lote
    $db->commit();

    // Finalizar Trabajo
    $resultadoFinal = [
        'total_filas'  => $totalRows,
        'programas'    => $cntProgramas,
        'aprendices'   => $cntAprendices,
        'juicios'      => $cntJuicios,
        'funcionarios' => $cntFuncionarios,
        'errores'      => count($errores) > 0 ? $errores : null,
    ];

    $queue->completeJob($job['id'], $resultadoFinal);
    
    // Eliminar archivo temporal si se configuró así
    // @unlink($filePath);
    
    echo "Trabajo {$job['id']} procesado con éxito.\n";
}
