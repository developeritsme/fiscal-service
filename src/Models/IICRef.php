<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

class IICRef extends Model
{
    /** @var string */
    protected $iic;

    /** @var Carbon */
    protected $dateTime;

    /** @var float|null */
    protected $amount;

    public function __construct($iic, $issueDateTime, $amount = null)
    {
        $this->iic = $iic;
        $this->dateTime = Carbon::parse($issueDateTime);
        $this->amount = $amount;
    }

    public function validate(): void
    {
        $errors = [];

        ValidationHelper::requiredAndPattern($errors, $this->iic, ValidationHelper::HEX_32, 'iic', 'IIC reference', 'HEX-32');
        ValidationHelper::required($errors, $this->dateTime, 'dateTime', 'Issue date/time');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        return [
            'iic'       => $this->iic,
            'date_time' => $this->dateTime->toIso8601String(),
            'amount'    => $this->amount,
        ];
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'IICRef', null);
        $writer->writeAttribute('IIC', $this->iic);
        $writer->writeAttribute('IssueDateTime', $this->dateTime->toIso8601String());
        if ($this->amount !== null) {
            $writer->writeAttribute('Amount', $this->formatNumber($this->amount, 2));
        }
        $writer->endElement();

        return $writer->outputMemory();
    }
}
