<?php

namespace DeveloperItsMe\FiscalService\Exceptions;

class SignatureException extends FiscalException
{
    /** @var string */
    protected $reason;

    public function __construct(string $reason, int $code = 0, ?\Throwable $previous = null)
    {
        $this->reason = $reason;
        parent::__construct('Signature verification failed: ' . $reason, $code, $previous);
    }

    public function getReason(): string
    {
        return $this->reason;
    }
}
