<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Traits\HasDecimals;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;
use DeveloperItsMe\FiscalService\Traits\Vatable;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

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

    /** @var float */
    protected $rebate = 0;

    /** @var bool */
    protected $rebateReducesBase = true;

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

    public function setUnitPrice($unitPrice): self
    {
        $this->unitPrice = $unitPrice;

        return $this;
    }

    public function setRebate($percent, $reducesBasePrice = true): self
    {
        $this->rebate = $percent;
        $this->rebateReducesBase = $reducesBasePrice;

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
        $base = $this->unitPrice / (1 + $this->vatRate / 100);

        if ($this->rebateReducesBase) {
            return $base * (1 - $this->rebate / 100);
        }

        return $base;
    }

    public function effectiveUnitPrice(): float
    {
        if ($this->rebateReducesBase) {
            return $this->baseUnitPrice() * (1 + $this->vatRate / 100);
        }

        return $this->unitPrice * (1 - $this->rebate / 100);
    }

    public function totalPrice(): float
    {
        return $this->quantity * $this->effectiveUnitPrice();
    }

    public function totalBasePrice(): float
    {
        return $this->quantity * $this->baseUnitPrice();
    }

    public function validate(): void
    {
        $errors = [];

        ValidationHelper::required($errors, $this->name, 'name', 'Name');
        ValidationHelper::required($errors, $this->unitPrice, 'unitPrice', 'Unit price');
        ValidationHelper::required($errors, $this->vatRate, 'vatRate', 'VAT rate');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        return [
            'name'                => $this->name,
            'code'                => $this->code,
            'unit'                => $this->unit,
            'quantity'            => $this->quantity,
            'unit_price'          => $this->unitPrice,
            'vat_rate'            => $this->vatRate,
            'rebate'              => $this->rebate,
            'rebate_reduces_base' => $this->rebateReducesBase,
            'base_unit_price'     => $this->baseUnitPrice(),
            'total_price'         => $this->totalPrice(),
            'total_base_price'    => $this->totalBasePrice(),
        ];
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
        $writer->writeAttribute('R', $this->formatNumber($this->rebate, $this->decimals));
        $writer->writeAttribute('RR', $this->rebateReducesBase ? 'true' : 'false');
        $writer->writeAttribute('U', $this->unit);

        $writer->writeAttribute('UPB', $this->formatNumber($this->baseUnitPrice(), $this->decimals));
        $writer->writeAttribute('UPA', $this->formatNumber($this->effectiveUnitPrice(), $this->decimals));

        if ($this->getIsVat()) {
            $writer->writeAttribute('VA', $this->formatNumber($this->totalPrice() - $this->totalBasePrice(), $this->decimals));
            $writer->writeAttribute('VR', $this->formatNumber($this->vatRate, $this->decimals));
        }

        $writer->endElement();

        return $writer->outputMemory();
    }
}
