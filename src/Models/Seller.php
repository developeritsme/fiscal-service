<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class Seller extends Business
{
    /** @throws InvalidArgumentException */
    public function setCountry(string $country): self
    {
        if ($country !== Countries::ME) {
            throw new InvalidArgumentException('Seller country is always MNE.');
        }

        return $this;
    }

    /** @throws InvalidArgumentException */
    public function setIdNumber(string $id): self
    {
        if (!preg_match(ValidationHelper::TIN, (string) $id)) {
            throw new InvalidArgumentException(
                sprintf('ID number must match TIN format (8 or 13 digits), got "%s".', $id)
            );
        }

        return parent::setIdNumber($id);
    }

    protected function getXmlNodeName(): string
    {
        return 'Seller';
    }
}
