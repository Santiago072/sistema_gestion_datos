<?php
require 'config/database.php';
$db = getDB();
$sql = file_get_contents('migrations/create_logs_importacion_table.sql');
$db->exec($sql);
echo "Tabla logs_importacion creada exitosamente en sena_juicios\n";
