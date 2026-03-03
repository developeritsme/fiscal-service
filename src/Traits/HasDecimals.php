<?php

namespace DeveloperItsMe\FiscalService\Traits;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;

trait HasDecimals
{
    /** @var int */
    protected $decimals = 2;

    public function setDecimals(int $decimals): self
    {
        if (!in_array($decimals, [2, 3, 4], true)) {
            throw new InvalidArgumentException(
                sprintf('Invalid decimals: %d. Allowed values: 2, 3, 4.', $decimals)
            );
        }

        $this->decimals = $decimals;

        return $this;
    }
}
