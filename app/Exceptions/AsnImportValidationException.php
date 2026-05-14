<?php

namespace App\Exceptions;

use Exception;

class AsnImportValidationException extends Exception
{
    protected $validationErrors;

    public function __construct($message, $validationErrors = [])
    {
        parent::__construct($message);
        $this->validationErrors = $validationErrors;
    }

    public function getValidationErrors(): array
    {
        return $this->validationErrors;
    }
}
