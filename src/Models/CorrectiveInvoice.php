<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class CorrectiveInvoice extends Model
{
    public const TYPE_CORRECTIVE = 'CORRECTIVE';
    public const TYPE_ERROR_CORRECTIVE = 'ERROR_CORRECTIVE';

    /** @var string */
    protected $ikof;

    /** @var Carbon */
    protected $dateTime;

    /** @var string */
    protected $type;

    public function __construct($ikof, $issueDateTime, $type = self::TYPE_CORRECTIVE)
    {
        $this->ikof = $ikof;
        $this->dateTime = Carbon::parse($issueDateTime);
        $this->type = $type;
    }

    public function validate(): void
    {
        $errors = [];

        ValidationHelper::requiredAndPattern($errors, $this->ikof, ValidationHelper::HEX_32, 'ikof', 'IIC reference (IKOF)', 'HEX-32');
        ValidationHelper::required($errors, $this->dateTime, 'dateTime', 'Issue date/time');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        return [
            'ikof'      => $this->ikof,
            'date_time' => $this->dateTime->toIso8601String(),
            'type'      => $this->type,
        ];
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'CorrectiveInv', null);
        $writer->writeAttribute('IICRef', $this->ikof);
        $writer->writeAttribute('IssueDateTime', $this->dateTime->toIso8601String());
        $writer->writeAttribute('Type', $this->type);
        $writer->endElement();

        return $writer->outputMemory();
    }
}
