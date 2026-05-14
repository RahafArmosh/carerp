<?php

namespace App\Exceptions;

use Exception;

class PartialImportException extends Exception
{
    protected $successCount;
    protected $errors;

    public function __construct($message, $successCount, $errors = [])
    {
        parent::__construct($message);
        $this->successCount = $successCount;
        $this->errors = $errors;
    }

    public function getSuccessCount()
    {
        return $this->successCount;
    }

    public function getErrors()
    {
        return $this->errors;
    }
}

