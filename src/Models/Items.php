<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasDecimals;
use DeveloperItsMe\FiscalService\Traits\Vatable;

class Items extends Model
{
    use HasDecimals;
    use Vatable;

    protected bool $includeVat = true;

    /** @var Item[] */
    protected array $items = [];

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

        /** @var Item $item */
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
}
