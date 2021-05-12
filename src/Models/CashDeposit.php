<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Traits\HasUUID;

class CashDeposit extends Model
{
    use HasUUID;

    public const OPERATION_INITIAL = 'INITIAL';
    public const OPERATION_WITHDRAW = 'WITHDRAW';

    /** @var Carbon */
    protected $date;

    /** @var string */
    protected $idNumber;

    /** @var string */
    protected $operation = self::OPERATION_INITIAL;

    /** @var float */
    protected $amount;

    /** @var string */
    protected $enu;

    public function setDate($date): self
    {
        $this->date = Carbon::parse($date);

        return $this;
    }

    public function setIdNumber($id): self
    {
        $this->idNumber = $id;

        return $this;
    }

    public function setAmount($amount): self
    {
        $this->amount = floatval($amount);

        return $this;
    }

    public function setOperation($operation): self
    {
        if (in_array($operation, [self::OPERATION_INITIAL, self::OPERATION_WITHDRAW])) {
            $this->operation = $operation;
        }

        return $this;
    }

    public function setEnu($enu): self
    {
        $this->enu = $enu;

        return $this;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        //Header
        $writer->startElementNs(null, 'Header', null);
        $writer->writeAttribute('SendDateTime', Carbon::now()->toIso8601String());
        $writer->writeAttribute('UUID', $this->uuid ?? $this->generateUUID());
        $writer->endElement();

        $writer->startElementNs(null, 'CashDeposit', null);
        $writer->writeAttribute('CashAmt', $this->formatNumber($this->amount));
        $writer->writeAttribute('ChangeDateTime', $this->date->toIso8601String());
        $writer->writeAttribute('IssuerTIN', $this->idNumber);
        $writer->writeAttribute('Operation', $this->operation);
        $writer->writeAttribute('TCRCode', $this->enu);
        $writer->endElement();

        return $writer->outputMemory();
    }
}
