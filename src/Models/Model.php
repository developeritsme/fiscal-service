<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;

abstract class Model
{
    use HasXmlWriter;

    protected function boolToString(bool $value): string
    {
        return $value ? 'true' : 'false';
    }

    protected function formatNumber($value, $decimals = 2): string
    {
        return number_format($value, $decimals, '.', '');
    }

    abstract public function toXML(): string;
}
