<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;

class PaymentMethods extends Model
{
    /** @var PaymentMethod[] */
    protected array $methods = [];

    public function all(): array
    {
        return $this->methods;
    }

    /** @throws InvalidArgumentException */
    public function add(PaymentMethod $method, string $invoiceType): self
    {
        if (!$method->isAllowedForInvoiceType($invoiceType)) {
            throw new InvalidArgumentException(
                sprintf('Payment method "%s" is not allowed for invoice type "%s".', $method->getType(), $invoiceType)
            );
        }

        $this->methods[] = $method;

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
}
