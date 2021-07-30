<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;

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
