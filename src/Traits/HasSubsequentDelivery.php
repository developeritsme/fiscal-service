<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait HasSubsequentDelivery
{
    /** @var string|null */
    protected $subsequentDeliveryType;

    public function setSubsequentDeliveryType($type): self
    {
        if (in_array($type, $this->subsequentDeliveryTypes())) {
            $this->subsequentDeliveryType = $type;
        }

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
