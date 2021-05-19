<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Fiscal;
use DeveloperItsMe\FiscalService\Models\Business;
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
    protected $seller = 'Test only company';
    protected $tin = '12345678';
    protected $unitCode = 'xx123xx123';
    protected $softwareCode = 'ss123ss123';
    protected $maintainerCode = 'mm123mm123';
    protected $operatorCode = 'oo123oo123';

    protected function fiscal()
    {
        if (Carbon::hasTestNow()) {
            Carbon::setTestNow();
        }

        return new Fiscal($this->certPath, $this->certPassphrase, true);
    }

    /** @test */
    public function it_can_construct_proper()
    {
        $fiscal = $this->fiscal();
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
            ->setAmount(10)
            ->setEnu($this->enu);

        $request = new RegisterCashDeposit($cashDeposit);

        $response = $this->fiscal()
            ->request($request)
            ->send();

        $this->assertTrue($response->valid());
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

    /** @test */
    public function it_can_send_invoice_request()
    {
        $pm = new PaymentMethod(121);

        $seller = new Seller($this->seller, $this->tin);

        $item = new Item();
        $item->setCode(501234567890)
            ->setName('Taxi voznja')
            ->setUnitPrice(121)
            ->setVatRate(21);

        $invoice = (new Invoice())
            ->setNumber(9952)
            ->setEnu($this->enu)
            ->setBusinessUnitCode($this->unitCode)
            ->setSoftwareCode($this->softwareCode)
            ->setOperatorCode($this->operatorCode)
            ->setIssuerCode('4AD5A215BEAF85B0416235736A6DACAB')
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item);

        $request = new RegisterInvoice($invoice);

        $response = $this->fiscal()
            ->request($request)
            ->send();

        var_dump($response);
        $this->assertTrue($response->valid());
    }
}
