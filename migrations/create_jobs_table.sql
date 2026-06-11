CREATE TABLE IF NOT EXISTS trabajos_importacion (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tipo VARCHAR(50) NOT NULL COMMENT 'Ej: excel_aprendices, pdf_fases',
    ruta_archivo VARCHAR(255) NOT NULL,
    estado ENUM('pendiente', 'procesando', 'completado', 'error') DEFAULT 'pendiente',
    progreso INT DEFAULT 0,
    resultado JSON NULL,
    errores TEXT NULL,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    actualizado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
