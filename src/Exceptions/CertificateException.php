<?php

namespace DeveloperItsMe\FiscalService\Exceptions;

class CertificateException extends FiscalException
{
    /** @var array */
    protected $opensslErrors = [];

    public function __construct(string $message = '', array $opensslErrors = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->opensslErrors = $opensslErrors;
        parent::__construct($message, $code, $previous);
    }

    public function getOpensslErrors(): array
    {
        return $this->opensslErrors;
    }
}
