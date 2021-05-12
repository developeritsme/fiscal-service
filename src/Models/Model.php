<?php

namespace DeveloperItsMe\FiscalService\Models;

abstract class Model
{
    protected function boolToString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    abstract public function toXML(): string;
}
