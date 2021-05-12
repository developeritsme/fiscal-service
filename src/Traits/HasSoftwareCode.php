<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait HasSoftwareCode
{
    /** @var string */
    protected $softwareCode;

    public function setSoftwareCode($code): self
    {
        $this->softwareCode = $code;

        return $this;
    }
}
