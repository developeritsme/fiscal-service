<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\Vatable;

abstract class Business extends Model
{
    use Vatable;

    public const ID_TYPE_PIB = 'TIN';
    public const ID_TYPE_ID = 'ID';
    public const ID_TYPE_PASSPORT = 'PASS';
    public const ID_TYPE_VAT_NUMBER = 'VAT';
    public const ID_TYPE_TAX_NUMBER = 'TAX';
    public const ID_TYPE_SOCIAL_SECURITY_NUMBER = 'SOC';

    /** @var string */
    protected $idNumber;

    /** @var string */
    protected $name;

    /** @var string */
    protected $address;

    /** @var string */
    protected $town;

    /** @var string */
    protected $country = Countries::ME;

    public function __construct($name, $idNumber, $isVat = true)
    {
        $this->setName($name)
            ->setIdNumber($idNumber)
            ->setIsVat($isVat);
    }

    public function setIdNumber($id): self
    {
        $this->idNumber = $id;

        return $this;
    }

    public function getIdNumber(): string
    {
        return $this->idNumber;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setAddress($address): self
    {
        $this->address = $address;

        return $this;
    }

    public function setTown($town): self
    {
        $this->town = $town;

        return $this;
    }

    public function setCountry($country): self
    {
        if (in_array($country, Countries::codes())) {
            $this->country = $country;
        }

        return $this;
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

    abstract protected function getXmlNodeName(): string;
}
