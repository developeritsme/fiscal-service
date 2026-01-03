<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasDecimals;
use DeveloperItsMe\FiscalService\Traits\HasGroupedItems;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;
use DeveloperItsMe\FiscalService\Traits\Vatable;

class Items extends Model
{
    use HasDecimals;
    use HasGroupedItems;
    use HasXmlWriter;
    use Vatable;

    /** @var bool */
    protected $includeVat = true;

    /** @var array */
    protected $items = [];

    public function all(): array
    {
        return $this->items;
    }

    public function add(Item $item): void
    {
        $this->items[] = $item;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'Items', null);

        /** @var \DeveloperItsMe\FiscalService\Models\Item $item */
        foreach ($this->items as $item) {
            $writer->writeRaw(
                $item->setDecimals($this->decimals)
                    ->setIsVat($this->getIsVat())
                    ->toXML()
            );
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    protected function getGroupableItems(): array
    {
        return $this->items;
    }
}
