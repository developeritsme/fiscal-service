<?php

namespace DeveloperItsMe\FiscalService\Exceptions;

class ValidationException extends FiscalException
{
    /** @var array<string, string[]> */
    protected array $errors;

    /**
     * @param array<string, string[]> $errors
     */
    public function __construct(array $errors)
    {
        $this->errors = $errors;

        parent::__construct('Validation failed: ' . implode(' ', $this->getMessages()));
    }

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return string[]
     */
    public function getMessages(): array
    {
        return array_merge(...array_values($this->errors));
    }
}
