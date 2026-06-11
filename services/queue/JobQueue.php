<?php

namespace Services\Queue;

class JobQueue {
    private \PDO $db;

    public function __construct(\PDO $db) {
        $this->db = $db;
    }

    /**
     * Añade un nuevo trabajo a la cola.
     * @param string $tipo Tipo de trabajo (ej: 'excel_aprendices').
     * @param string $rutaArchivo Ruta al archivo que se procesará.
     * @return int El ID del trabajo insertado.
     */
    public function enqueue(string $tipo, string $rutaArchivo): int {
        $stmt = $this->db->prepare("INSERT INTO trabajos_importacion (tipo, ruta_archivo, estado) VALUES (:tipo, :ruta, 'pendiente')");
        $stmt->execute([
            ':tipo' => $tipo,
            ':ruta' => $rutaArchivo
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Obtiene el estado y progreso de un trabajo.
     * @param int $jobId El ID del trabajo.
     * @return array|null Los detalles del trabajo o null si no existe.
     */
    public function getStatus(int $jobId): ?array {
        $stmt = $this->db->prepare("SELECT id, estado, progreso, resultado, errores FROM trabajos_importacion WHERE id = :id");
        $stmt->execute([':id' => $jobId]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Obtiene el próximo trabajo pendiente y lo marca como 'procesando'.
     */
    public function claimNextJob(): ?array {
        try {
            $this->db->beginTransaction();
            $stmt = $this->db->query("SELECT * FROM trabajos_importacion WHERE estado = 'pendiente' ORDER BY id ASC LIMIT 1 FOR UPDATE");
            $job = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($job) {
                $upd = $this->db->prepare("UPDATE trabajos_importacion SET estado = 'procesando' WHERE id = :id");
                $upd->execute([':id' => $job['id']]);
                $this->db->commit();
                return $job;
            }
            $this->db->commit();
            return null;
        } catch (\Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }

    public function updateProgress(int $jobId, int $progreso) {
        $stmt = $this->db->prepare("UPDATE trabajos_importacion SET progreso = :progreso WHERE id = :id");
        $stmt->execute([':progreso' => $progreso, ':id' => $jobId]);
    }

    public function completeJob(int $jobId, array $resultado) {
        $stmt = $this->db->prepare("UPDATE trabajos_importacion SET estado = 'completado', progreso = 100, resultado = :res WHERE id = :id");
        $stmt->execute([':res' => json_encode($resultado, JSON_UNESCAPED_UNICODE), ':id' => $jobId]);
    }

    public function failJob(int $jobId, string $errores) {
        $stmt = $this->db->prepare("UPDATE trabajos_importacion SET estado = 'error', errores = :err WHERE id = :id");
        $stmt->execute([':err' => $errores, ':id' => $jobId]);
    }

    /**
     * Registra un error especfico en una fila del archivo importado.
     */
    public function logError(int $jobId, ?int $fila, string $mensajeError) {
        $stmt = $this->db->prepare("INSERT INTO logs_importacion (job_id, fila, mensaje_error) VALUES (:job_id, :fila, :mensaje)");
        $stmt->execute([
            ':job_id'  => $jobId,
            ':fila'    => $fila,
            ':mensaje' => $mensajeError
        ]);
    }
}
