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

    protected function getRegisterInvoiceRequest($noCash = false, $corrective = null): RegisterInvoice
    {
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
            ->setNumber(time())
            ->setEnu($this->enu)
            ->setBusinessUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setOperatorCode($this->operatorCode)
            ->setSeller($seller);

        if ($corrective) {
            $multi = -1;
            $invoice->setCorrectiveInvoice($corrective);
            $item->setQuantity($multi);
            $item2->setQuantity($multi);
        } else {
            $multi = 1;
        }

        $invoice->addItem($item2)
            ->addItem($item);

        if ($noCash) {
            $invoice->setType(Invoice::TYPE_NONCASH);
            $pm = new PaymentMethod($multi * 3, PaymentMethod::TYPE_ACCOUNT);
        } else {
            $pm = new PaymentMethod($multi * 3);
        }

        $invoice->addPaymentMethod($pm);

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
