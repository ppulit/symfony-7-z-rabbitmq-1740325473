<?php

namespace App\Validator;

class ClientFileValidator implements CsvFileValidator
{
    public function validateRow(array $row): bool
    {
        if (4 !== count($row)) {
            return false;
        }

        if (!is_numeric($row[0])) {
            return false;
        }

        if (empty($row[1])) {
            return false;
        }

        if (!filter_var($row[2], FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        if (empty($row[3])) {
            return false;
        }

        return true;
    }
}
