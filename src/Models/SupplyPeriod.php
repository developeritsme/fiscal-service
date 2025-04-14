<?php

namespace DeveloperItsMe\FiscalService\Models;

class SupplyPeriod extends Model
{
    /** @var string */
    protected $start;

    /**
     * @var string
     */
    protected $end;

    public function __construct($start, $end = null)
    {
        $this->start = $start;
        $this->end = $end;
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
