<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait Vatable
{
    /** @var boolean */
    protected $isVat;

    public function setIsVat($isVat): self
    {
        $this->isVat = $isVat;

        return $this;
    }

    public function getIsVat(): bool
    {
        return $this->isVat;
    }
}
