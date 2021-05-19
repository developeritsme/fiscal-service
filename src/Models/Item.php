<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class Item extends Model
{
    use HasXmlWriter;

    /** @var string */
    protected $code;

    /** @var string */
    protected $name;

    /** @var string */
    protected $unit = 'piece';

    /** @var float */
    protected $quantity = 1.0;

    /** @var float */
    protected $unitPrice;

    /** @var float */
    protected $vatRate;

    public function setCode($code): self
    {
        $this->code = $code;

        return $this;
    }

    public function setName($name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setUnit($unit): self
    {
        $this->unit = $unit;

        return $this;
    }

    public function setQuantity($quantity): self
    {
        $this->quantity = $quantity;

        return $this;
    }

    public function setUnitPrice($unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function setVatRate($percent): self
    {
        $this->vatRate = $percent;

        return $this;
    }

    public function getVatRate(): float
    {
        return $this->vatRate;
    }

    public function baseUnitPrice(): float
    {
        return $this->unitPrice / (1 + $this->vatRate / 100);
    }

    public function totalPrice(): float
    {
        return $this->quantity * $this->unitPrice;
    }

    public function totalBasePrice(): float
    {
        return $this->quantity * $this->baseUnitPrice();
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'I', null);
        if ($this->code) {
            $writer->writeAttribute('C', $this->code);
        }
        $writer->writeAttribute('N', $this->name);
        $writer->writeAttribute('PA', number_format($this->totalPrice(), 2));
        $writer->writeAttribute('PB', number_format($this->totalBasePrice(), 2));
        $writer->writeAttribute('Q', number_format($this->quantity, 1));

        //todo:
        $writer->writeAttribute('R', '0');
        $writer->writeAttribute('RR', 'true');

        $writer->writeAttribute('U', $this->unit);

        $writer->writeAttribute('UPB', number_format($this->baseUnitPrice(), 2));
        $writer->writeAttribute('UPA', number_format($this->unitPrice, 2));

        $writer->writeAttribute('VA', number_format($this->totalPrice() - $this->totalBasePrice(), 2));
        $writer->writeAttribute('VR', number_format($this->vatRate, 2));

        $writer->endElement();

        return $writer->outputMemory();
    }
}
