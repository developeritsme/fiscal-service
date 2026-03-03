<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait Vatable
{
    protected bool $isVat;

    public function setIsVat(bool $isVat): self
    {
        $this->isVat = $isVat;

        return $this;
    }

    public function getIsVat(): bool
    {
        return $this->isVat;
    }
}
