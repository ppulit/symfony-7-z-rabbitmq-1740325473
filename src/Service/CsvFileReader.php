<?php

namespace App\Service;

class CsvFileReader
{
    public function readFile(string $filePath, bool $skipFirstRow = false): \Generator
    {
        if (($handle = fopen($filePath, 'r')) === false) {
            throw new \RuntimeException("Nie udało się otworzyć pliku: $filePath");
        }

        if ($skipFirstRow) {
            fgetcsv($handle);
        }

        while (($data = fgetcsv($handle)) !== false) {
            yield $data;
        }

        fclose($handle);
    }

    public function countLinesInFile(string $filePath): int
    {
        return count(file($filePath));
    }
}
