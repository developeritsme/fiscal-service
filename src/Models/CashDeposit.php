<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Traits\HasUUID;
use DeveloperItsMe\FiscalService\Validation\ValidationHelper;

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

    public function validate(): void
    {
        $errors = [];

        ValidationHelper::required($errors, $this->date, 'date', 'Date');
        ValidationHelper::requiredAndPattern($errors, $this->idNumber, ValidationHelper::TIN, 'idNumber', 'ID number', 'TIN');
        ValidationHelper::required($errors, $this->amount, 'amount', 'Amount');
        ValidationHelper::requiredAndPattern($errors, $this->enu, ValidationHelper::REGISTRATION_CODE, 'enu', 'TCR code (ENU)', 'registration code');

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    public function toArray(): array
    {
        $this->validate();

        return [
            'uuid'       => $this->uuid,
            'date'       => $this->date->toIso8601String(),
            'id_number'  => $this->idNumber,
            'operation'  => $this->operation,
            'amount'     => $this->amount,
            'tcr_code'   => $this->enu,
        ];
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
