<?php

namespace Services\Import;

class CsvAdapter implements ImportAdapterInterface {
    
    public function parse(string $filePath): array {
        $raw = [];
        $handle = @fopen($filePath, 'r');
        
        if (!$handle) {
            throw new \Exception('No se pudo abrir el archivo CSV');
        }

        while (($row = fgetcsv($handle, 2000, ';')) !== false) {
            $raw[] = $row;
        }
        fclose($handle);

        if (empty($raw)) {
            throw new \Exception('CSV vacío');
        }

        return $raw;
    }
}
