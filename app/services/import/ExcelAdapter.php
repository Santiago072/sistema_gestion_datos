<?php

namespace Services\Import;

require_once __DIR__ . '/../../libs/SimpleXLSX.php';
require_once __DIR__ . '/../../libs/SimpleXLS.php';

use Shuchkin\SimpleXLSX;
use Shuchkin\SimpleXLS;

class ExcelAdapter implements ImportAdapterInterface {
    
    private string $extension;

    public function __construct(string $extension) {
        $this->extension = strtolower($extension);
    }

    public function parse(string $filePath): array {
        if ($this->extension === 'xlsx') {
            $xlsx = SimpleXLSX::parse($filePath);
            $error = SimpleXLSX::parseError();
        } else {
            $xlsx = SimpleXLS::parse($filePath);
            $error = SimpleXLS::parseError();
        }

        if (!$xlsx) {
            throw new \Exception('No se pudo leer el archivo Excel: ' . $error);
        }

        // Leer primera hoja
        $raw = $xlsx->rows(0);
        
        if (empty($raw)) {
            throw new \Exception('El archivo Excel está vacío');
        }

        return $raw;
    }
}
