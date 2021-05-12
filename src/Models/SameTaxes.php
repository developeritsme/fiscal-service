<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class SameTaxes extends Model
{
    use HasXmlWriter;

    protected $items = [];

    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public static function make(array $items): self
    {
        return new static($items);
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'SameTaxes', null);

        foreach ($this->getGroupedItems() as $rate => $item) {
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

    protected function getGroupedItems(): array
    {
        $grouped = [];
        /** @var \DeveloperItsMe\FiscalService\Models\Item $item */
        foreach ($this->items as $item) {
            $grouped[$item->getVatRate()]['prices'][] = $basePrice = $item->totalBasePrice();
            $grouped[$item->getVatRate()]['vats'][] = $item->totalPrice() - $basePrice;
        }

        ksort($grouped, SORT_NUMERIC);

        return $grouped;
    }
}
