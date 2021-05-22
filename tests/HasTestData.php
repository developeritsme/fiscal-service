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
    public static $createdEnu;

    protected $qrProductionUrl = 'https://mapr.tax.gov.me/ic/#/verify';
    protected $qrTestUrl = 'https://efitest.tax.gov.me/ic/#/verify';
    protected $certPath = './CoreitPotpisSoft.pfx';
    protected $certPassphrase = '123456';
    protected $enu = 'si747we972';
    protected $seller = 'City Taxi';
    protected $tin = '12345678';
    protected $unitCode = 'xx123xx123';
    protected $softwareCode = 'ss123ss123';
    protected $maintainerCode = 'mm123mm123';
    protected $operatorCode = 'oo123oo123';

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

    protected function getRegisterInvoiceRequest(): RegisterInvoice
    {
        $pm = new PaymentMethod(3);

        $seller = new Seller($this->seller, $this->tin);
        $seller->setAddress('Radosava Burića bb')
            ->setTown('Podgorica');

        $item = new Item();
        $item->setName('Taxi voznja')
            ->setUnitPrice(2.20)
            ->setVatRate(7);

        $item2 = new Item();
        $item2->setName('Čekanje')
            ->setUnitPrice(0.80)
            ->setVatRate(7);

        $invoice = (new Invoice())
            ->setNumber(9952)
            ->setEnu($this->enu)
            ->setBusinessUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setOperatorCode($this->operatorCode)
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item2)
            ->addItem($item);

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
