<?php

namespace App\Imports;

use InvalidArgumentException;
use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Reader\XLSX\Reader;
use ZipArchive;

class ModelProfilesPreview
{
    public function read(string $path): array
    {
        $this->checkArchive($path);
        $reader = new Reader;

        try {
            $reader->open($path);
            foreach ($reader->getSheetIterator() as $sheet) {
                if (mb_strtolower(trim($sheet->getName())) !== 'perfiles') {
                    continue;
                }

                $headers = [];
                $rows = [];
                $names = [];
                foreach ($sheet->getRowIterator() as $number => $row) {
                    if ($number > 501 || count($row->getCells()) > 60) {
                        throw new InvalidArgumentException('El archivo admite hasta 500 filas de datos y 60 columnas.');
                    }
                    $values = [];
                    foreach ($row->getCells() as $cell) {
                        if ($cell instanceof FormulaCell) {
                            throw new InvalidArgumentException("La fila {$number} contiene fórmulas. Reemplazalas por valores antes de subir el archivo.");
                        }
                        $value = $cell->getValue();
                        $value = is_scalar($value) ? trim((string) $value) : '';
                        if (mb_strlen($value) > 10000) {
                            throw new InvalidArgumentException("La fila {$number} contiene una celda demasiado extensa.");
                        }
                        $values[] = $value;
                    }
                    if (count(array_filter($values, fn ($value) => $value !== '')) === 0) {
                        continue;
                    }
                    if ($headers === []) {
                        $headers = $values;
                        while (end($headers) === '') {
                            array_pop($headers);
                        }
                        if (in_array('', $headers, true) || count(array_unique($headers)) !== count($headers)) {
                            throw new InvalidArgumentException('Los encabezados deben tener nombres únicos y no estar vacíos.');
                        }
                        foreach (['Nombre', 'Tipo', 'Página'] as $required) {
                            if (! in_array($required, $headers, true)) {
                                throw new InvalidArgumentException("Falta la columna obligatoria: {$required}.");
                            }
                        }
                        continue;
                    }
                    if (count(array_filter(array_slice($values, count($headers)), fn ($value) => $value !== '')) > 0) {
                        throw new InvalidArgumentException("La fila {$number} tiene datos sin encabezado.");
                    }
                    $data = array_combine($headers, array_slice(array_pad($values, count($headers), ''), 0, count($headers)));
                    $warnings = [];
                    foreach (['Nombre', 'Tipo', 'Página', 'WhatsApp', 'Ubicación'] as $field) {
                        if (trim($data[$field] ?? '') === '') {
                            $warnings[] = "Falta {$field}.";
                        }
                    }
                    if (! in_array($data['Tipo'], ['Creadora', 'Escort'], true)) {
                        $warnings[] = 'Tipo de publicación no reconocido.';
                    }
                    $name = mb_strtolower($data['Nombre']);
                    $names[$name] = ($names[$name] ?? 0) + 1;
                    $rows[] = ['number' => $number, 'data' => $data, 'warnings' => $warnings];
                }
                if ($rows === []) {
                    throw new InvalidArgumentException('La hoja Perfiles no contiene registros.');
                }
                foreach ($rows as &$row) {
                    if ($names[mb_strtolower($row['data']['Nombre'])] > 1) {
                        $row['warnings'][] = 'Nombre repetido: revisar la correspondencia antes de importar.';
                    }
                }
                unset($row);

                return ['headers' => $headers, 'rows' => $rows];
            }
            throw new InvalidArgumentException('No se encontró una hoja llamada Perfiles.');
        } finally {
            $reader->close();
        }
    }

    private function checkArchive(string $path): void
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw new InvalidArgumentException('El archivo no es un Excel XLSX válido.');
        }
        try {
            $size = 0;
            if ($zip->numFiles > 1000) {
                throw new InvalidArgumentException('El archivo contiene demasiados elementos.');
            }
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->statIndex($i);
                $size += $entry['size'];
                if ($size > 30 * 1024 * 1024) {
                    throw new InvalidArgumentException('El contenido descomprimido del Excel supera 30 MB.');
                }
            }
        } finally {
            $zip->close();
        }
    }
}
