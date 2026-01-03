<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Traits\HasDecimals;
use DeveloperItsMe\FiscalService\Traits\HasSoftwareCode;
use DeveloperItsMe\FiscalService\Traits\HasUUID;

class Invoice extends Model
{
    use HasDecimals;
    use HasUUID;
    use HasSoftwareCode;

    public const TYPE_CASH = 'CASH';
    public const TYPE_NONCASH = 'NONCASH';

    public const TYPE_INVOICE = 'INVOICE';
    public const TYPE_CORRECTIVE = 'CORRECTIVE';
    public const TYPE_SUMMARY = 'SUMMARY';
    public const TYPE_PERIODICAL = 'PERIODICAL';
    public const TYPE_ADVANCE = 'ADVANCE';
    public const TYPE_CREDIT_NOTE = 'CREDIT_NOTE';

    public static $qrBaseUrl;

    /** @var Carbon */
    protected $dateTime;

    /** @var string */
    protected $method = self::TYPE_CASH;

    /** @var string */
    protected $type = self::TYPE_INVOICE;

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

    /** @var \DeveloperItsMe\FiscalService\Models\Buyer */
    protected $buyer;

    /** @var \DeveloperItsMe\FiscalService\Models\PaymentMethods */
    protected $paymentMethods;

    /** @var \DeveloperItsMe\FiscalService\Models\Items */
    protected $items;

    /** @var \DeveloperItsMe\FiscalService\Models\SameTaxes */
    protected $taxes;

    /** @var \DeveloperItsMe\FiscalService\Models\CorrectiveInvoice */
    protected $corrective;

    /** @var \DeveloperItsMe\FiscalService\Models\SupplyPeriod */
    protected $supplyPeriod;

    /** @var array */
    protected $totals = [];

    /** @var string */
    protected $iicSignature;

    /** @var string */
    protected $taxPeriod;

    public function __construct($itemsDecimals = 2)
    {
        $this->paymentMethods = new PaymentMethods();
        $this->items = new Items();
        $this->setDecimals($itemsDecimals);
        $this->dateTime = Carbon::now();
    }

    public function setDateTime($dateTime): self
    {
        $this->dateTime = Carbon::parse($dateTime);

        return $this;
    }

    public function getDateTime(): ?string
    {
        return $this->dateTime ? $this->dateTime->toIso8601String() : null;
    }

    public function setType($type): self
    {
        if (in_array($type, [self::TYPE_CASH, self::TYPE_NONCASH])) {
            $this->method = $type;

            return $this;
        }

        if (in_array($type, $this->types())) {
            $this->type = $type;
        }

        return $this;
    }

    protected function types(): array
    {
        return [
            self::TYPE_INVOICE,
            self::TYPE_CORRECTIVE,
            self::TYPE_SUMMARY,
            self::TYPE_PERIODICAL,
            self::TYPE_ADVANCE,
            self::TYPE_CREDIT_NOTE,
        ];
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
        $this->paymentMethods->add($paymentMethod, $this->method);

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

    public function setCorrectiveInvoice(CorrectiveInvoice $invoice): self
    {
        $this->corrective = $invoice;
        $this->setType(self::TYPE_CORRECTIVE);

        return $this;
    }

    public function setSeller(Seller $seller): self
    {
        $this->seller = $seller;

        return $this;
    }

    public function setBuyer(Buyer $buyer): self
    {
        $this->buyer = $buyer;

        return $this;
    }

    public function setTaxPeriod($period): self
    {
        $this->taxPeriod = $period;

        return $this;
    }

    public function setSupplyPeriod($start, $end = null): self
    {
        unset($this->supplyPeriod);

        $this->supplyPeriod = new SupplyPeriod($start, $end);

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
        $writer->writeAttribute('OperatorCode', $this->operatorCode);
        $writer->writeAttribute('SoftCode', $this->softwareCode);
        $writer->writeAttribute('TCRCode', $this->enu);
        $writer->writeAttribute('TotPrice', $this->formatNumber($this->totals('total'), 2));
        $writer->writeAttribute('TotPriceWoVAT', $this->formatNumber($this->totals('base'), 2));
        if ($this->seller->getIsVat()) {
            $writer->writeAttribute('TotVATAmt', $this->formatNumber($this->totals('vat')));
        } else {
            $writer->writeAttribute('TaxFreeAmt', $this->formatNumber($this->totals('base')));
        }

        $writer->writeAttribute('TypeOfInv', $this->method);
        $writer->writeAttribute('InvType', $this->type);

        // Tax period
        if ($this->taxPeriod) {
            $writer->writeAttribute('TaxPeriod', $this->taxPeriod);
        }

        // Supply period
        if ($this->supplyPeriod) {
            $writer->writeRaw($this->supplyPeriod->toXML());
        }

        if ($this->corrective) {
            $writer->writeRaw($this->corrective->toXML());
        }

        $writer->writeRaw($this->paymentMethods->toXML());

        $writer->writeRaw($this->seller->toXML());

        if ($this->buyer) {
            $writer->writeRaw($this->buyer->toXML());
        }

        $writer->writeRaw(
            $this->items
                ->setDecimals($this->decimals)
                ->setIsVat($this->seller->getIsVat())
                ->toXML()
        );

        if ($this->seller->getIsVat()) {
            $writer->writeRaw($this->taxes->toXML());
        }

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

    public function generateIIC($pkey): void
    {
        $data = hash('sha256', $this->concatenate(
            $this->formatNumber($this->totals('total'))
        ));

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

    public function url(): string
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

    public function ikof(): string
    {
        return $this->issuerCode;
    }

    public function toArray(): array
    {
        return [];
    }
}
