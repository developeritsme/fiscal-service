<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait HasSoftwareCode
{
    protected ?string $softwareCode = null;

    public function setSoftwareCode(string $code): self
    {
        $this->softwareCode = $code;

        return $this;
    }

    /**
     * @return string|$this
     */
    public function softwareCode(?string $code = null)
    {
        return empty($code) ? $this->softwareCode : $this->setSoftwareCode($code);
    }
}
