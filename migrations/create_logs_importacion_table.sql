CREATE TABLE IF NOT EXISTS logs_importacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id INT NOT NULL,
    fila INT NULL,
    mensaje_error TEXT NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (job_id) REFERENCES trabajos_importacion(id) ON DELETE CASCADE
);
