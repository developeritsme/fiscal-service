<?php

namespace DeveloperItsMe\FiscalService\Models;

use DeveloperItsMe\FiscalService\Contracts\Validatable;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class SupplyPeriod extends Model implements Validatable
{
    protected string $start;

    protected ?string $end;

    public function __construct(string $start, ?string $end = null)
    {
        $this->start = $start;
        $this->end = $end;
    }

    /** @throws ValidationException */
    public function validate(): void
    {
        $errors = [];

        ValidationHelper::requiredAndPattern($errors, $this->start, ValidationHelper::DATE, 'start', 'Start date', 'date');
        ValidationHelper::pattern($errors, $this->end, ValidationHelper::DATE, 'end', 'End date', 'date');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        return [
            'start' => $this->start,
            'end'   => $this->end ?? $this->start,
        ];
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'SupplyDateOrPeriod', null);
        $writer->writeAttribute('Start', $this->start);
        $writer->writeAttribute('End', $this->end ?? $this->start);
        $writer->endElement();

        return $writer->outputMemory();
    }
}
