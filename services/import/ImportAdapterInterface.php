<?php

namespace Services\Import;

interface ImportAdapterInterface {
    /**
     * Extrae las filas de un archivo y las devuelve como un arreglo.
     * @param string $filePath Ruta al archivo.
     * @return array Las filas extraídas del archivo (array bidimensional).
     * @throws \Exception Si hay un error al leer el archivo.
     */
    public function parse(string $filePath): array;
}
