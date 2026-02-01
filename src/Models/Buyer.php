<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class Buyer extends Business
{
    public function validate(): void
    {
        $errors = [];

        if ($this->country === Countries::ME) {
            ValidationHelper::pattern($errors, $this->idNumber, ValidationHelper::TIN, 'idNumber', 'ID number', 'TIN');
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    protected function getXmlNodeName(): string
    {
        return 'Buyer';
    }
}
