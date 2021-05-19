<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class Items extends Model
{
    use HasXmlWriter;

    /** @var array */
    protected $items = [];

    public function all(): array
    {
        return $this->items;
    }

    public function add(Item $item)
    {
        $this->items[] = $item;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'Items', null);
        foreach ($this->items as $item) {
            $writer->writeRaw($item->toXML());
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
