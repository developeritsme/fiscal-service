<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

abstract class Business extends Model
{
    use HasXmlWriter;

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

    public function __construct($name, $idNumber)
    {
        $this->setName($name)
            ->setIdNumber($idNumber);
    }

    public function setIdNumber($id): self
    {
        $this->idNumber = $id;

        return $this;
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

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, $this->getXmlNodeName(), null);
        if ($this->address) {
            $writer->writeAttribute('Address', 'ADRESA PRODAVAOCA');
        }
        //todo:
        $writer->writeAttribute('Country', 'MNE');
        $writer->writeAttribute('IDNum', $this->idNumber);
        $writer->writeAttribute('IDType', self::ID_TYPE_PIB);
        $writer->writeAttribute('Name', $this->name);
        if ($this->town) {
            $writer->writeAttribute('Town', $this->town);
        }
        $writer->endElement();

        return $writer->outputMemory();
    }

    abstract protected function getXmlNodeName(): string;
}
