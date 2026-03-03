<?php

namespace DeveloperItsMe\FiscalService\Traits;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;

trait HasSubsequentDelivery
{
    /** @var string|null */
    protected $subsequentDeliveryType;

    public function setSubsequentDeliveryType($type): self
    {
        if (!in_array($type, $this->subsequentDeliveryTypes())) {
            throw new InvalidArgumentException(
                sprintf('Invalid subsequent delivery type: "%s". Allowed values: %s.', $type, implode(', ', $this->subsequentDeliveryTypes()))
            );
        }

        $this->subsequentDeliveryType = $type;

        return $this;
    }

    protected function subsequentDeliveryTypes(): array
    {
        return [
            'NOINTERNET',
            'BOUNDBOOK',
            'SERVICE',
            'TECHNICALERROR',
            'BUSINESSNEEDS',
        ];
    }
}
