<?php

namespace App\Validator;

interface CsvFileValidator
{
    public function validateRow(array $row): bool;
}
