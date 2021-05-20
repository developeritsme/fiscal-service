<?php

namespace DeveloperItsMe\FiscalService\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Traits\HasSoftwareCode;
use DeveloperItsMe\FiscalService\Traits\HasUUID;

class Invoice extends Model
{
    use HasUUID;
    use HasSoftwareCode;

    public const TYPE_CASH = 'CASH';
    public const TYPE_NONCASH = 'NONCASH';

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

    public function __construct()
    {
        $this->paymentMethods = new PaymentMethods();
        $this->items = new Items();
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

    protected function number(): string
    {
        /*
          Broj računa sastavljen od kôda poslovnog prostora,
          rednog broja računa, godine izdavanja računa i kôda ENU na kojem je izdat račun.
         Redni broj računa je neprekidni string koji se dodjeljuje svakom novom računu kako
         bi se računi mogli prebrojati. String resetuje se na početku svake godine.
         */
        return implode('/', [$this->businessUnitCode, $this->number, $this->dateTime->year, $this->enu]);
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        if (!$this->dateTime) {
            $this->dateTime = Carbon::now();
        }
        $taxes = SameTaxes::make($this->getItems());
        $totals = $taxes->getTotals();

        //Header
        $writer->startElementNs(null, 'Header', null);
        $writer->writeAttribute('SendDateTime', Carbon::now()->toIso8601String());
        $writer->writeAttribute('UUID', $this->uuid ?? $this->generateUUID());
        $writer->endElement();

        $writer->startElementNs(null, 'Invoice', null);
        $writer->writeAttribute('BusinUnitCode', $this->businessUnitCode);
        $writer->writeAttribute('IssueDateTime', $this->dateTime->toIso8601String());

        //todo: IKOF Potpis
        $writer->writeAttribute('IICSignature', $this->securityCode($total = $this->formatNumber($totals['total'])));
        $writer->writeAttribute('IIC', $this->issuerCode);
        $writer->writeAttribute('InvNum', $this->number());
        $writer->writeAttribute('InvOrdNum', $this->number);
        //todo:
        $writer->writeAttribute('IsIssuerInVAT', 'true');
        $writer->writeAttribute('IsReverseCharge', $this->boolToString(false));
        $writer->writeAttribute('IsSimplifiedInv', $this->boolToString($this->isSimplified));
        $writer->writeAttribute('OperatorCode', $this->operatorCode);
        $writer->writeAttribute('SoftCode', $this->softwareCode);
        $writer->writeAttribute('TCRCode', $this->enu);
        $writer->writeAttribute('TotPrice', $total);
        $writer->writeAttribute('TotPriceWoVAT', $this->formatNumber($totals['base']));
        $writer->writeAttribute('TotVATAmt', $this->formatNumber($totals['vat']));

        $writer->writeAttribute('TypeOfInv', $this->type);

        $writer->writeRaw($this->paymentMethods->toXML());

        $writer->writeRaw($this->seller->toXML());

        $writer->writeRaw($this->items->toXML());

        $writer->writeRaw($taxes->toXML());

        $writer->endElement();

        return $writer->outputMemory();
    }

    public function concatenate($total)
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

    public function securityCode($total): string
    {
//        $pkey = file_get_contents('./private.key');
//        $key = openssl_pkey_get_private($pkey);
//        var_dump($key);
//        $signatureCode = null;

//        $data = $this->concatenate($total);

//        openssl_sign($data, $signatureCode, $pkey, OPENSSL_ALGO_SHA256);

//        $signatureCode = 'A72977773A579523665C3D4F8DEFF3F301CA726A7960EFF5A6863E4CB6009A752C52652C615049A0B2B650380A12D4CC44E7FEB0371FEC42501D95A2F8ACE24A9483EC8AF93219DCC7F58C1E62497B412922B5CAE83A0F914427A769EE550C6510C43DE1FFBF13C911DBADCE66DAC6065B98352276F0B19260457887C20EB351932377B749B4CC0338100D9CB6A202A1EE9BC77B1E584FD9692C26102F603C7ED920E3ABF22DAF4C1D170E954B1D320709E26A429C3B8D45208B7C5CBF5BA1C51713E888ACA00BC60C00BA18E7B1434A196F9F09CBD28B68F4FD1F56EA197B59AF77D6B8459C1CBCAA367089BCC8CEFAE3926DA8183DD822D371230411F4CFFD';

//        return md5($signatureCode);

        return '83D728C8E10BA04C430BE64CE98612B0256C0FE618C167F28BF62A0C0CB38C51824F152AB00510AE076508E53ACE4F877D25D51C7830F043E09BB1500D3A0AEA233ECC6175A45FE58CBF53E517FD9EA1D06CBABC055EEE6B430A16560C96D3A27720A6E5C9BA5C8D18A7AE5C2A7F1D8E46B293F56D32847FCEE199D2AFDC6E5BC1164BA974A6E29D6F40FBD8C51D40A99BC97DD6DB2AE9EC0582F2E74E9C7841AC5A854DE92B1D778A809CACCBBEF4DC325C852487BCF035AA2D54594DC6BDD859E250782CCCDD7CC89EE80A2FE1030AAAD615DA5D728322F8590D9F56E6DDE5975A738F304F56BB832996763624B72C77E97881D9C647B50709F20AFBFA0602';
    }
}
