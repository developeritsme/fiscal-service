<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Fiscal;
use DeveloperItsMe\FiscalService\Traits\HasSoftwareCode;
use DeveloperItsMe\FiscalService\Traits\HasUUID;

class Invoice extends Model
{
    use HasUUID;
    use HasSoftwareCode;

    public const TYPE_CASH = 'CASH';
    public const TYPE_NONCASH = 'NONCASH';

    public static $qrBaseUrl;

    /** @var Carbon */
    protected $dateTime;

    /** @var string */
    protected $type = self::TYPE_CASH;

    /** @var bool */
    protected $isSimplified = false;

    /** @var int */
    protected $number;

    /** @var string */
    protected $enu;

    /** @var string */
    protected $operatorCode;

    /** @var string */
    protected $businessUnitCode;

    /**
     * IKOF code - issuer code
     *
     * @var string
     */
    protected $issuerCode;

    /** @var \DeveloperItsMe\FiscalService\Models\Seller */
    protected $seller;

    /** @var \DeveloperItsMe\FiscalService\Models\PaymentMethods */
    protected $paymentMethods;

    /** @var \DeveloperItsMe\FiscalService\Models\Items */
    protected $items;

    /** @var \DeveloperItsMe\FiscalService\Models\SameTaxes */
    protected $taxes;

    /** @var array */
    protected $totals = [];

    /** @var string */
    protected $iicSignature;

    public function __construct()
    {
        $this->paymentMethods = new PaymentMethods();
        $this->items = new Items();
        $this->dateTime = Carbon::now();
    }

    public function setDateTime($dateTime): self
    {
        $this->dateTime = Carbon::parse($dateTime);

        return $this;
    }

    public function setType($type): self
    {
        if (in_array($type, [self::TYPE_CASH, self::TYPE_NONCASH])) {
            $this->type = $type;
        }

        return $this;
    }

    public function setNumber(int $number): self
    {
        if ($number >= 0) {
            $this->number = $number;
        }

        return $this;
    }

    public function setEnu($enu): self
    {
        $this->enu = $enu;

        return $this;
    }

    public function setOperatorCode($code): self
    {
        $this->operatorCode = $code;

        return $this;
    }

    public function setBusinessUnitCode($code): self
    {
        $this->businessUnitCode = $code;

        return $this;
    }

    public function setIssuerCode($code): self
    {
        $this->issuerCode = $code;

        return $this;
    }

    public function addPaymentMethod(PaymentMethod $paymentMethod): self
    {
        $this->paymentMethods->add($paymentMethod, $this->type);

        return $this;
    }

    public function addItem(Item $item): self
    {
        $this->items->add($item);

        return $this;
    }

    public function getItems(): array
    {
        return $this->items->all();
    }

    public function setSeller(Seller $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function number(): string
    {
        return implode('/', [$this->businessUnitCode, $this->number, $this->dateTime->year, $this->enu]);
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        if (!$this->dateTime) {
            $this->dateTime = Carbon::now();
        }

        //Header
        $writer->startElementNs(null, 'Header', null);
        $writer->writeAttribute('SendDateTime', $this->dateTime->toIso8601String());
        $writer->writeAttribute('UUID', $this->uuid ?? $this->generateUUID());
        $writer->endElement();

        $writer->startElementNs(null, 'Invoice', null);
        $writer->writeAttribute('BusinUnitCode', $this->businessUnitCode);
        $writer->writeAttribute('IssueDateTime', $this->dateTime->toIso8601String());

        //todo: IKOF Potpis
        $writer->writeAttribute('IICSignature', $this->iicSignature);
        $writer->writeAttribute('IIC', $this->issuerCode);
        $writer->writeAttribute('InvNum', $this->number());
        $writer->writeAttribute('InvOrdNum', $this->number);
        //todo:
        $writer->writeAttribute('IsIssuerInVAT', $this->boolToString($this->seller->getIsVat()));
        $writer->writeAttribute('IsReverseCharge', $this->boolToString(false));
        $writer->writeAttribute('IsSimplifiedInv', $this->boolToString($this->isSimplified));
        $writer->writeAttribute('OperatorCode', $this->operatorCode);
        $writer->writeAttribute('SoftCode', $this->softwareCode);
        $writer->writeAttribute('TCRCode', $this->enu);
        $writer->writeAttribute('TotPrice', $this->formatNumber($this->totals('total')));
        $writer->writeAttribute('TotPriceWoVAT', $this->formatNumber($this->totals('base')));
        $writer->writeAttribute('TotVATAmt', $this->formatNumber($this->totals('vat')));

        $writer->writeAttribute('TypeOfInv', $this->type);

        $writer->writeRaw($this->paymentMethods->toXML());

        $writer->writeRaw($this->seller->toXML());

        $writer->writeRaw($this->items->toXML());

        $writer->writeRaw($this->taxes->toXML());

        $writer->endElement();

        return $writer->outputMemory();
    }

    public function concatenate($total): string
    {
        return implode('|', [
            $this->seller->getIdNumber(),
            ($this->dateTime ?? $this->dateTime = Carbon::now())->toIso8601String(),
            $this->number,
            $this->businessUnitCode,
            $this->enu,
            $this->softwareCode,
            $total,
        ]);
    }

    public function generateIIC($pkey)
    {
        $data = hash('sha256', $this->concatenate($this->totals('total')));

        openssl_sign($data, $this->iicSignature, $pkey, OPENSSL_ALGO_SHA256);

        $this->iicSignature = strtoupper(bin2hex($this->iicSignature));

        $this->issuerCode = strtoupper(md5($this->iicSignature));
    }

    public function setIicSignature($signature): self
    {
        $this->iicSignature = $signature;

        return $this;
    }

    protected function totals($key = null)
    {
        if (empty($this->totals)) {
            $this->taxes = SameTaxes::make($this->getItems());
            $this->totals = $this->taxes->getTotals();
        }

        return !empty($key) && array_key_exists($key, $this->totals) ? $this->totals[$key] : $this->totals;
    }

    public function url()
    {
        $query = [
            'iic'  => $this->issuerCode,
            'tin'  => $this->seller->getIdNumber(),
            'crtd' => $this->dateTime->tz('Europe/Podgorica')->toIso8601String(),
            'ord'  => $this->number,
            'bu'   => $this->businessUnitCode,
            'cr'   => $this->enu,
            'sw'   => $this->softwareCode,
            'prc'  => $this->formatNumber($this->totals('total')),
        ];

        return static::$qrBaseUrl . '?' . http_build_query($query);
    }

    public function ikof()
    {
        return $this->issuerCode;
    }

    public function toArray()
    {

    }
}
