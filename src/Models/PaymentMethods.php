<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasGroupedItems;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

class PaymentMethods extends Model
{
    use HasGroupedItems;
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

    protected function getGroupableItems(): array
    {
        return $this->methods;
    }
}
