<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasDecimals;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;
use DeveloperItsMe\FiscalService\Traits\Vatable;

class Item extends Model
{
    use HasDecimals;
    use HasXmlWriter;
    use Vatable;

    /** @var string */
    protected $code;

    /** @var string */
    protected $name;

    /** @var string */
    protected $unit = 'unit';

    /** @var float */
    protected $quantity = 1.0;

    /** @var float */
    protected $unitPrice;

    /** @var float */
    protected $vatRate;

    public function __construct($name = null, $vatRate = null)
    {
        $this->setName($name)
            ->setVatRate($vatRate);
    }

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

    public function setUnitPrice($unitPrice, $vatAndDiscountIncluded = true, $rebate = 0): self
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
        $writer->writeAttribute('N', substr($this->name, 0, 50));
        $writer->writeAttribute('PA', $this->formatNumber($this->totalPrice(), $this->decimals));
        $writer->writeAttribute('PB', $this->formatNumber($this->totalBasePrice(), $this->decimals));
        $writer->writeAttribute('Q', $this->formatNumber($this->quantity, 2));

        //todo:
        $writer->writeAttribute('R', '0');
        $writer->writeAttribute('RR', 'true');

        $writer->writeAttribute('U', $this->unit);

        $writer->writeAttribute('UPB', $this->formatNumber($this->baseUnitPrice(), $this->decimals));
        $writer->writeAttribute('UPA', $this->formatNumber($this->unitPrice, $this->decimals));

        if ($this->getIsVat()) {
            $writer->writeAttribute('VA', $this->formatNumber($this->totalPrice() - $this->totalBasePrice(), $this->decimals));
            $writer->writeAttribute('VR', $this->formatNumber($this->vatRate, $this->decimals));
        }

        $writer->endElement();

        return $writer->outputMemory();
    }
}
