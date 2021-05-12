<?php

namespace DeveloperItsMe\FiscalService\Models;

class SameTaxes extends Model
{
    protected $items = [];

    protected $grouped = [];

    public function __construct(array $items)
    {
        $this->items = $items;
        $this->setGroupedItems();
    }

    public static function make(array $items): self
    {
        return new static($items);
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'SameTaxes', null);

        foreach ($this->grouped as $rate => $item) {
            $writer->startElementNs(null, 'SameTax', null);
            $writer->writeAttribute('NumOfItems', count($item['vats']));
            $writer->writeAttribute('PriceBefVAT', number_format(array_sum($item['prices']), 2));
            $writer->writeAttribute('VATAmt', number_format(array_sum($item['vats']), 2));
            $writer->writeAttribute('VATRate', number_format($rate, 2));
            $writer->endElement();
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    protected function setGroupedItems()
    {
        /** @var \DeveloperItsMe\FiscalService\Models\Item $item */
        foreach ($this->items as $item) {
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
