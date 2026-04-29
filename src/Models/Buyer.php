<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class Buyer extends Business
{
    /** @throws InvalidArgumentException */
    public function setCountry(?string $country): self
    {
        if ($country === null) {
            return $this;
        }

        if (!in_array($country, Countries::codes())) {
            throw new InvalidArgumentException(
                sprintf('Invalid country: "%s".', $country)
            );
        }

        $this->country = $country;

        return $this;
    }

    /** @throws ValidationException */
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
