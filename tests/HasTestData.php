<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Fiscal;
use DeveloperItsMe\FiscalService\Models\BusinessUnit;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\Seller;
use DeveloperItsMe\FiscalService\Requests\RegisterCashDeposit;
use DeveloperItsMe\FiscalService\Requests\RegisterInvoice;
use DeveloperItsMe\FiscalService\Requests\RegisterTCR;
use PHPUnit\Framework\MockObject\MockObject;

trait HasTestData
{
    use HasPublicTestData;

    public static $createdEnu;

    protected $qrProductionUrl = 'https://mapr.tax.gov.me/ic/#/verify';
    protected $qrTestUrl = 'https://efitest.tax.gov.me/ic/#/verify';

    protected function fiscal($certContent = null, $test = true): Fiscal
    {
        if (Carbon::hasTestNow()) {
            Carbon::setTestNow();
        }

        return new Fiscal($certContent ?? $this->certPath, $this->certPassphrase, $test);
    }

    protected function mockFiscal(): MockObject
    {
        return $this->getMockBuilder(Fiscal::class)
            ->onlyMethods(['request', 'send'])
            ->setConstructorArgs([$this->certPath, $this->certPassphrase])
            ->getMock();
    }

    protected function getInvoice($noCash = false, $decimals = 2): Invoice
    {
        $seller = new Seller($this->seller, $this->tin);
        $seller->setAddress('Radosava Burića bb')
            ->setTown('Podgorica');

        $number = intval(time() * (rand(1, 100) / 10000) * 100);

        $invoice = (new Invoice($decimals))
            ->setNumber($number)
            ->setEnu($this->enu)
            ->setBusinessUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setOperatorCode($this->operatorCode)
            ->setSeller($seller);

        return $noCash ? $invoice->setType(Invoice::TYPE_NONCASH) : $invoice;
    }

    protected function getItem($name, $vatRate, $price): Item
    {
        $item = new Item();

        return $item->setName($name)
            ->setVatRate($vatRate)
            ->setUnitPrice($price);
    }

    protected function getPaymentMethod($amount, $type = PaymentMethod::TYPE_BANKNOTE): PaymentMethod
    {
        return new PaymentMethod($amount, $type);
    }

    protected function getRegisterInvoiceRequest($noCash = false, $corrective = null, $decimals = 2): RegisterInvoice
    {
        $item = $this->getItem('Taxi voznja', 7, 2.20);

        $item2 = $this->getItem('Čekanje', 7, 0.80);

        $invoice = $this->getInvoice($noCash, $decimals);

        $multi = 1;
        if ($corrective) {
            $multi = -1;
            $invoice->setCorrectiveInvoice($corrective);
            $item->setQuantity($multi);
            $item2->setQuantity($multi);
        }

        $invoice->addItem($item2)
            ->addItem($item);

        $invoice->addPaymentMethod($this->getPaymentMethod(
            $multi * 3,
            $noCash ? PaymentMethod::TYPE_ACCOUNT : PaymentMethod::TYPE_BANKNOTE
        ));

        return $this->registerInvoiceRequest($invoice);
    }

    protected function registerInvoiceRequest($invoice): RegisterInvoice
    {
        return new RegisterInvoice($invoice);
    }

    protected function getRegisterTCRRequest(): RegisterTCR
    {
        $businessUnit = (new BusinessUnit())
            ->setIdNumber($this->tin)
            ->setUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setMaintainerCode($this->maintainerCode)
            ->setInternalId(uniqid());

        return new RegisterTCR($businessUnit);
    }

    protected function getRegisterCashDepositRequest($operation = null): RegisterCashDeposit
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber($this->tin)
            ->setAmount(1)
            ->setEnu(self::$createdEnu ?? $this->enu)
            ->setOperation($operation ?? CashDeposit::OPERATION_INITIAL);

        return new RegisterCashDeposit($cashDeposit);
    }
}
