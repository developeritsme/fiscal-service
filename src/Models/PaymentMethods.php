<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class PaymentMethods extends Model
{
    use HasXmlWriter;

    /** @var array */
    protected $methods = [];

    public function all(): array
    {
        return $this->methods;
    }

    public function add(PaymentMethod $method, $invoiceType): self
    {
        if ($method->isAllowedForInvoiceType($invoiceType)) {
            $this->methods[] = $method;
        }

        return $this;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'PayMethods', null);
        foreach ($this->methods as $method) {
            $writer->writeRaw($method->toXML());
        }
        $writer->endElement();

        return $writer->outputMemory();
    }

    protected function setGroupedItems()
    {
        /** @var \DeveloperItsMe\FiscalService\Models\Item $item */
        foreach ($this->methods as $item) {
            $this->grouped[$item->getVatRate()]['prices'][] = $basePrice = $item->totalBasePrice();
            $this->grouped[$item->getVatRate()]['vats'][] = $item->totalPrice() - $basePrice;
        }

        ksort($this->grouped, SORT_NUMERIC);
    }

    public function getTotals(): array
    {
        $totals = [
            'total' => 0,
            'base'  => 0,
            'vat'   => 0,
        ];
        foreach ($this->grouped as $item) {
            $totals['base'] += $base = array_sum($item['prices']);
            $totals['vat'] += $vat = array_sum($item['vats']);
            $totals['total'] += $base + $vat;
        }

        return $totals;
    }
}
