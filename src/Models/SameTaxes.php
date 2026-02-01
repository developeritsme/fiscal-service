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

        foreach ($this->grouped as $group) {
            $writer->startElementNs(null, 'SameTax', null);
            $writer->writeAttribute('NumOfItems', count($group['vats']));
            $writer->writeAttribute('PriceBefVAT', $this->formatNumber(array_sum($group['prices']), 2));
            $writer->writeAttribute('VATAmt', $this->formatNumber(array_sum($group['vats']), 2));
            $writer->writeAttribute('VATRate', $this->formatNumber($group['rate'], 2));
            if ($group['exempt']) {
                $writer->writeAttribute('ExemptFromVAT', $group['exempt']);
            }
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
            $rate = $item->getVatRate();
            $exempt = $item->getExemptFromVAT();
            $key = $rate . ($exempt ? '|' . $exempt : '');

            $this->grouped[$key]['prices'][] = $basePrice = $item->totalBasePrice();
            $this->grouped[$key]['vats'][] = $item->totalPrice() - $basePrice;
            $this->grouped[$key]['rate'] = $rate;
            $this->grouped[$key]['exempt'] = $exempt;
        }

        uasort($this->grouped, function ($a, $b) {
            return $a['rate'] <=> $b['rate']
                ?: ($a['exempt'] ?? '') <=> ($b['exempt'] ?? '');
        });

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
