<?php

namespace DeveloperItsMe\FiscalService\Models;

class SameTaxes extends Model
{
    protected $items = [];

    protected $grouped = [];

    protected $totals = [];

    public function __construct(array $items)
    {
        $this->setItems($items);
    }

    public static function make(array $items): self
    {
        return new static($items);
    }

    public function setItems(array $items): self
    {
        $this->items = $items;

        return $this->setGroupedItems();
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'SameTaxes', null);

        foreach ($this->grouped as $rate => $item) {
            $writer->startElementNs(null, 'SameTax', null);
            $writer->writeAttribute('NumOfItems', count($item['vats']));
            $writer->writeAttribute('PriceBefVAT', $this->formatNumber(array_sum($item['prices']), 2));
            $writer->writeAttribute('VATAmt', $this->formatNumber(array_sum($item['vats']), 2));
            $writer->writeAttribute('VATRate', $this->formatNumber($rate, 2));
            $writer->endElement();
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    protected function setGroupedItems(): self
    {
        $this->totals = [];
        /** @var \DeveloperItsMe\FiscalService\Models\Item $item */
        foreach ($this->items as $item) {
            $this->grouped[$item->getVatRate()]['prices'][] = $basePrice = $item->totalBasePrice();
            $this->grouped[$item->getVatRate()]['vats'][] = $item->totalPrice() - $basePrice;
        }

        ksort($this->grouped, SORT_NUMERIC);

        return $this;
    }

    public function getTotals(): array
    {
        if (empty($this->totals)) {
            $this->totals = [
                'total' => 0,
                'base'  => 0,
                'vat'   => 0,
            ];
            foreach ($this->grouped as $item) {
                $this->totals['base'] += $base = array_sum($item['prices']);
                $this->totals['vat'] += $vat = array_sum($item['vats']);
                $this->totals['total'] += $base + $vat;
            }
        }

        return $this->totals;
    }
}
