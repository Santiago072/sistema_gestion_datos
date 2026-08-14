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
        set_time_limit(300);

        if (empty($_FILES['archivo']) || $_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            jsonResponse(['error' => true, 'message' => 'No se recibió archivo válido'], 400);
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

        try {
            if ($ext === 'xlsx' || $ext === 'xls') {
                $adapter = new ExcelAdapter($ext);
            } else {
                $adapter = new CsvAdapter();
            }

            $raw = $adapter->parse($tmpFile);
            @unlink($tmpFile);

            $removeAccents = fn($s) => str_replace(
                ['á','é','í','ó','ú','ñ','Á','É','Í','Ó','Ú','Ñ'],
                ['a','e','i','o','u','n','a','e','i','o','u','n'], $s
            );

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

            foreach (array_slice($raw, 0, $headerIndex) as $row) {
                foreach ($row as $k => $cell) {
                    $str = strtolower($removeAccents(trim((string)$cell)));
                    if (str_contains($str, 'ficha')) {
                        preg_match('/\d{5,}/', (string)$cell, $m);
                        if (!empty($m[0])) { $metaFicha = $m[0]; continue; }
                        for ($j = 1; $j <= 3; $j++) {
                            if (isset($row[$k+$j])) {
                                preg_match('/\d{5,}/', $row[$k+$j], $m2);
                                if (!empty($m2[0])) { $metaFicha = $m2[0]; break; }
                            }
                        }
                    }
                    if (str_contains($str, 'programa') || str_contains($str, 'denominacion')) {
                        $parts = explode(':', (string)$cell, 2);
                        if (!empty($parts[1])) {
                            $metaPrograma = trim($parts[1]);
                        } else {
                            $metaPrograma = trim((string)$cell);
                        }
                    }
                }
            }

            $headers = array_map(fn($h) => strtolower($removeAccents(trim((string)$h))), $raw[$headerIndex] ?? []);
            $dataRows = array_slice($raw, $headerIndex + 1);

            $col = fn($name) => array_search($name, $headers, true);
            $c_doc      = $col('documento');
            $c_tdoc     = $col('tipo_doc') ?? $col('tipo documento');
            $c_nom      = $col('nombre');
            $c_ape      = $col('apellidos') ?? $col('apellido');
            $c_est      = $col('estado');
            $c_ficha    = $col('ficha') ?? $col('id_ficha') ?? $col('numero_ficha');
            $c_prog     = $col('programa') ?? $col('nombre_programa');
            $c_juicio   = $col('tipo_juicio') ?? $col('juicio');
            $c_comp     = $col('competencia') ?? $col('codigo_competencia');
            $c_ncomp    = $col('nombre_competencia') ?? $col('denominacion_competencia');
            $c_res      = $col('resultado') ?? $col('codigo_resultado');
            $c_nres     = $col('nombre_resultado') ?? $col('denominacion_resultado');
            $c_func     = $col('funcionario') ?? $col('nombre_funcionario');
            $c_dfunc    = $col('documento_funcionario') ?? $col('doc_funcionario');
            $c_fjuicio  = $col('fecha_juicio') ?? $col('fecha_hora_juicio') ?? $col('fecha');

            $db = $this->db;
            $db->beginTransaction();

            $programasVistos    = [];
            $aprendicesVistos   = [];
            $funcionariosVistos = [];
            $competenciasVistas = [];
            $resultadosVistos   = [];
            $juiciosInsertar    = [];

            $totalProcesados = 0;
            $validos = 0;

            foreach ($dataRows as $row) {
                $doc = trim((string)($row[$c_doc] ?? ''));
                if (!$doc) continue;

                $totalProcesados++;
                $tdoc   = trim((string)($row[$c_tdoc] ?? 'CC'));
                $nombres= trim((string)($row[$c_nom]  ?? ''));
                $apell  = trim((string)($row[$c_ape]  ?? ''));
                $estado = trim((string)($row[$c_est]  ?? 'En formación'));
                $ficha  = trim((string)($row[$c_ficha] ?? '')) ?: $metaFicha;
                $prog   = trim((string)($row[$c_prog]  ?? '')) ?: $metaPrograma ?: 'PROGRAMA ' . $ficha;

                if (!$ficha) $ficha = '0000000';

                if (!isset($programasVistos[$ficha])) {
                    $programasVistos[$ficha] = $prog;
                }

                if (!isset($aprendicesVistos[$doc])) {
                    $aprendicesVistos[$doc] = [$tdoc, $nombres, $apell, $estado, $ficha];
                }

                $docFunc = trim((string)($row[$c_dfunc] ?? ''));
                $nomFunc = trim((string)($row[$c_func]  ?? ''));
                if ($docFunc && !isset($funcionariosVistos[$docFunc])) {
                    $funcionariosVistos[$docFunc] = $nomFunc ?: 'Funcionario ' . $docFunc;
                }

                $codComp = trim((string)($row[$c_comp]  ?? ''));
                $nomComp = trim((string)($row[$c_ncomp] ?? '')) ?: $codComp;
                $codRes  = trim((string)($row[$c_res]   ?? ''));
                $nomRes  = trim((string)($row[$c_nres]  ?? '')) ?: $codRes;
                $tipoJ   = trim((string)($row[$c_juicio] ?? 'Por evaluar'));
                $fechaJ  = trim((string)($row[$c_fjuicio]?? '')) ?: date('Y-m-d H:i:s');

                $juiciosInsertar[] = [
                    'doc'     => $doc,
                    'ficha'   => $ficha,
                    'comp'    => $codComp,
                    'ncomp'   => $nomComp,
                    'res'     => $codRes,
                    'nres'    => $nomRes,
                    'juicio'  => $tipoJ,
                    'fecha'   => $fechaJ,
                    'dfunc'   => $docFunc
                ];

                $validos++;
            }

            // Insertar programas
            $stmtProg = $db->prepare("INSERT INTO programas (id_ficha, nombre) VALUES (:ficha, :nombre) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
            foreach ($programasVistos as $f => $n) {
                $stmtProg->execute([':ficha' => $f, ':nombre' => $n]);
            }

            // Insertar aprendices
            $stmtApr = $db->prepare("INSERT INTO aprendices (documento, tipo_documento, nombres, apellidos, estado, id_ficha)
                VALUES (:doc, :tdoc, :nom, :ape, :est, :ficha)
                ON DUPLICATE KEY UPDATE tipo_documento=VALUES(tipo_documento), nombres=VALUES(nombres), apellidos=VALUES(apellidos), estado=VALUES(estado), id_ficha=VALUES(id_ficha)");
            foreach ($aprendicesVistos as $doc => $d) {
                $stmtApr->execute([':doc' => $doc, ':tdoc' => $d[0], ':nom' => $d[1], ':ape' => $d[2], ':est' => $d[3], ':ficha' => $d[4]]);
            }

            // Insertar funcionarios
            if (!empty($funcionariosVistos)) {
                $stmtFunc = $db->prepare("INSERT INTO funcionarios (documento, nombre) VALUES (:doc, :nom) ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)");
                foreach ($funcionariosVistos as $d => $n) {
                    $stmtFunc->execute([':doc' => $d, ':nom' => $n]);
                }
            }

            // Insertar juicios en lotes con columnas exactas de sena_juicios
            $stmtJuicio = $db->prepare("INSERT INTO juicios (tipo_juicio, fecha_juicio, id_funcionario, id_ficha, documento_aprendiz) VALUES (:tipo, :fecha, :func, :ficha, :doc)");
            $stmtRes    = $db->prepare("INSERT INTO resultados (id_juicio, codigo, nombre) VALUES (:id_juicio, :cod, :nom)");
            $stmtComp   = $db->prepare("INSERT INTO competencias (id_resultado, codigo, nombre, id_aprendiz, id_ficha) VALUES (:id_res, :cod, :nom, :doc, :ficha)");

            foreach ($juiciosInsertar as $j) {
                $funcId = !empty($j['dfunc']) ? (int)$j['dfunc'] : null;
                $stmtJuicio->execute([
                    ':tipo'  => $j['juicio'],
                    ':fecha' => $j['fecha'],
                    ':func'  => $funcId,
                    ':ficha' => (int)$j['ficha'],
                    ':doc'   => $j['doc']
                ]);
                $idJuicio = $db->lastInsertId();

                $stmtRes->execute([
                    ':id_juicio' => $idJuicio,
                    ':cod'       => $j['res'],
                    ':nom'       => $j['nres']
                ]);
                $idRes = $db->lastInsertId();

                $stmtComp->execute([
                    ':id_res' => $idRes,
                    ':cod'    => $j['comp'],
                    ':nom'    => $j['ncomp'],
                    ':doc'    => $j['doc'],
                    ':ficha'  => (int)$j['ficha']
                ]);
            }

            $db->commit();

            jsonResponse([
                'error'   => false,
                'message' => "Procesamiento completado con éxito",
                'data'    => [
                    'total_procesados' => $totalProcesados,
                    'validos'          => $validos,
                    'aprendices'       => count($aprendicesVistos),
                    'programas'        => count($programasVistos)
                ]
            ]);
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            @unlink($tmpFile);
            jsonResponse(['error' => true, 'message' => 'Error durante el procesamiento: ' . $e->getMessage()], 500);
        }
    }
}
