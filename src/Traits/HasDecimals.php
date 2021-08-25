<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait HasDecimals
{
    /** @var int */
    protected $decimals = 2;

    public function setDecimals(int $decimals): self
    {
        if ($decimals === 2 || $decimals === 4) {
            $this->decimals = $decimals;
        }

        return $this;
    }
}
