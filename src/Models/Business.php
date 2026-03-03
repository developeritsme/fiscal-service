<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Contracts\Validatable;
use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Traits\Vatable;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

abstract class Business extends Model implements Validatable
{
    use Vatable;

    public const ID_TYPE_PIB = 'TIN';
    public const ID_TYPE_ID = 'ID';
    public const ID_TYPE_PASSPORT = 'PASS';
    public const ID_TYPE_VAT_NUMBER = 'VAT';
    public const ID_TYPE_TAX_NUMBER = 'TAX';
    public const ID_TYPE_SOCIAL_SECURITY_NUMBER = 'SOC';

    protected ?string $idNumber = null;

    protected ?string $name = null;

    protected ?string $address = null;

    protected ?string $town = null;

    protected string $country = Countries::ME;

    public function __construct(string $name, string $idNumber, bool $isVat = true)
    {
        $this->setName($name)
            ->setIdNumber($idNumber)
            ->setIsVat($isVat);
    }

    public function setIdNumber(string $id): self
    {
        $this->idNumber = $id;

        return $this;
    }

    public function getIdNumber(): string
    {
        return $this->idNumber;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setAddress(string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function setTown(string $town): self
    {
        $this->town = $town;

        return $this;
    }

    /** @throws InvalidArgumentException */
    public function setCountry(string $country): self
    {
        if (!in_array($country, Countries::codes())) {
            throw new InvalidArgumentException(
                sprintf('Invalid country: "%s".', $country)
            );
        }

        $this->country = $country;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'name'      => $this->name,
            'id_number' => $this->idNumber,
            'id_type'   => $this->country === Countries::ME ? self::ID_TYPE_PIB : self::ID_TYPE_TAX_NUMBER,
            'address'   => $this->address,
            'town'      => $this->town,
            'country'   => $this->country,
            'is_vat'    => $this->getIsVat(),
        ];
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, $this->getXmlNodeName(), null);
        if ($this->address) {
            $writer->writeAttribute('Address', $this->address);
        }

        $writer->writeAttribute('Country', $this->country);
        $writer->writeAttribute('IDNum', $this->idNumber);
        $writer->writeAttribute('IDType', $this->country == Countries::ME ? self::ID_TYPE_PIB : self::ID_TYPE_TAX_NUMBER);
        $writer->writeAttribute('Name', $this->name);
        if ($this->town) {
            $writer->writeAttribute('Town', $this->town);
        }
        $writer->endElement();

        return $writer->outputMemory();
    }

    /** @throws ValidationException */
    public function validate(): void
    {
        $errors = [];

        ValidationHelper::required($errors, $this->name, 'name', 'Name');
        ValidationHelper::required($errors, $this->idNumber, 'idNumber', 'ID number');

        if ($this->country === Countries::ME) {
            ValidationHelper::pattern($errors, $this->idNumber, ValidationHelper::TIN, 'idNumber', 'ID number', 'TIN');
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    abstract protected function getXmlNodeName(): string;
}
