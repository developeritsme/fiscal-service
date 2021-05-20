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
use PHPUnit\Framework\TestCase;

class FiscalTest extends TestCase
{
    protected $certPath = './CoreitPotpisSoft.pfx';
    protected $certPassphrase = '123456';
    protected $enu = 'si747we972';
    protected $seller = 'City Taxi';
    protected $tin = '12345678';
    protected $unitCode = 'xx123xx123';
    protected $softwareCode = 'ss123ss123';
    protected $maintainerCode = 'mm123mm123';
    protected $operatorCode = 'oo123oo123';

    protected function fiscal($certContent = null): Fiscal
    {
        if (Carbon::hasTestNow()) {
            Carbon::setTestNow();
        }

        return new Fiscal($certContent ?? $this->certPath, $this->certPassphrase, true);
    }

    /** @test */
    public function it_sets_certificate_from_file()
    {
        $fiscal = $this->fiscal();
        $cert = $fiscal->certificate();

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_sets_certificate_from_content()
    {
        $content = file_get_contents($this->certPath);
        $fiscal = $this->fiscal($content);
        $cert = $fiscal->certificate();

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @skip */
    public function it_can_send_cash_deposit_request()
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber($this->tin)
            ->setAmount(1)
            ->setEnu($this->enu);

        $requestInitial = new RegisterCashDeposit($cashDeposit);

        $responseInitial = $this->fiscal()
            ->request($requestInitial)
            ->send();

        $this->assertTrue($responseInitial->valid());

        $cashDeposit->setOperation(CashDeposit::OPERATION_WITHDRAW);
        $requestWithdraw = new RegisterCashDeposit($cashDeposit);

        $responseWithdraw = $this->fiscal()
            ->request($requestWithdraw)
            ->send();

        $this->assertTrue($responseWithdraw->valid());
    }

    /** @skip */
    public function it_can_send_tcr_request()
    {
        $businessUnit = (new BusinessUnit())
            ->setIdNumber($this->tin)
            ->setUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setMaintainerCode($this->maintainerCode)
            ->setInternalId(uniqid());

        $request = new RegisterTCR($businessUnit);

        $response = $this->fiscal()
            ->request($request)
            ->send();

        $this->assertTrue($response->valid());
    }

    /** @skip */
    public function it_can_send_invoice_request()
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

        $request = new RegisterInvoice($invoice);

        $response = $this->fiscal()
            ->request($request)
            ->send();

        $this->assertTrue($response->valid());
    }
}
