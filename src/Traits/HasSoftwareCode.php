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

    /**
     * @param null $code
     *
     * @return string|$this
     */
    public function softwareCode($code = null)
    {
        return empty($code) ? $this->softwareCode : $this->setSoftwareCode($code);
    }
}
