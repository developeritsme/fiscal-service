<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Traits\HasSoftwareCode;
use DeveloperItsMe\FiscalService\Traits\HasUUID;

class BusinessUnit extends Model
{
    use HasUUID;
    use HasSoftwareCode;

    public const TYPE_REGULAR = 'REGULAR';
    public const TYPE_VENDING = 'VENDING';

    /** @var string */
    protected $unitCode;

    /** @var string */
    protected $idNumber;

    /** @var string */
    protected $internalId;

    /** @var string */
    protected $maintainerCode;

    /** @var Carbon */
    protected $validFrom;

    /** @var Carbon */
    protected $validTo;

    /** @var string */
    protected $type = self::TYPE_REGULAR;

    public function setUnitCode($code): self
    {
        $this->unitCode = $code;

        return $this;
    }

    public function setIdNumber($id): self
    {
        $this->idNumber = $id;

        return $this;
    }

    public function setInternalId($id): self
    {
        $this->internalId = $id;

        return $this;
    }

    public function setMaintainerCode($code): self
    {
        $this->maintainerCode = $code;

        return $this;
    }

    public function setValidFrom($date): self
    {
        $this->validFrom = Carbon::parse($date);

        return $this;
    }

    public function setValidTo($date): self
    {
        $this->validTo = Carbon::parse($date);

        return $this;
    }

    public function setType($type): self
    {
        if (in_array($type, [self::TYPE_REGULAR, self::TYPE_VENDING])) {
            $this->type = $type;
        }

        return $this;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        //Header
        $writer->startElementNs(null, 'Header', null);
        $writer->writeAttribute('SendDateTime', Carbon::now()->toIso8601String());
        $writer->writeAttribute('UUID', $this->uuid ?? $this->generateUUID());
        $writer->endElement();

        $writer->startElementNs(null, 'TCR', null);
        $writer->writeAttribute('BusinUnitCode', $this->unitCode);
        $writer->writeAttribute('IssuerTIN', $this->idNumber);
        if ($this->maintainerCode) {
            $writer->writeAttribute('MaintainerCode', $this->maintainerCode);
        }
        if ($this->softwareCode) {
            $writer->writeAttribute('SoftCode', $this->softwareCode);
        }
        $writer->writeAttribute('TCRIntID', $this->internalId);

        $writer->writeAttribute('ValidFrom', ($this->validFrom ?? Carbon::now('Europe/Podgorica'))->format('Y-m-d'));

        if ($this->validTo) {
            $writer->writeAttribute('ValidTo', $this->validTo->format('Y-m-d'));
        }
        $writer->writeAttribute('Type', $this->type);
        $writer->endElement();

        return $writer->outputMemory();
    }
}
