<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\Seller;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    /** @test */
    public function it_generates_proper_xml_for_single_item()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $pm = new PaymentMethod(20);

        $seller = new Seller('IME PREZIME PRODAVAOCA', 'ID BROJ PRODAVAOCA');
        $seller->setAddress('ADRESA PRODAVAOCA')
            ->setTown('GRAD PRODAVAOCA');

        $item = new Item();
        $item->setCode(501234567890)
            ->setName('NAZIV PROIZVODA')
            ->setUnitPrice(20)
            ->setVatRate(25);

        $invoice->setUuid('8d216f9a-55bb-445a-be32-30137f11b964')
            ->setNumber(1)
            ->setEnu('TCRCode')
            ->setOperatorCode('ab123ab123')
            ->setBusinessUnitCode('ab123ab123')
            ->setSoftwareCode('PRIMJER KODA SOFTVERA')
            ->setIssuerCode('4AD5A215BEAF85B0416235736A6DACAB')
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item);

        $this->assertStringEqualsFile('./tests/xml/SingleItemInvoice.xml', $invoice->toXML());
    }

    /** @test */
    public function it_generates_proper_xml_for_multi_item()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $pm = new PaymentMethod(20);

        $seller = new Seller('IME PREZIME PRODAVAOCA', 'ID BROJ PRODAVAOCA');
        $seller->setAddress('ADRESA PRODAVAOCA')
            ->setTown('GRAD PRODAVAOCA');

        $item1 = (new Item())
            ->setCode(501234567890)
            ->setName('NAZIV PROIZVODA')
            ->setUnitPrice(20)
            ->setVatRate(25);

        $item2 = (new Item())
            ->setName('NAZIV PROIZVODA 2')
            ->setQuantity(2)
            ->setUnitPrice(12)
            ->setVatRate(20);

        $invoice->setUuid('8d216f9a-55bb-445a-be32-30137f11b964')
            ->setNumber(1)
            ->setEnu('TCRCode')
            ->setOperatorCode('ab123ab123')
            ->setBusinessUnitCode('ab123ab123')
            ->setSoftwareCode('PRIMJER KODA SOFTVERA')
            ->setIssuerCode('4AD5A215BEAF85B0416235736A6DACAB')
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item1)
            ->addItem($item2);

        $this->assertStringEqualsFile('./tests/xml/MultiItemInvoice.xml', $invoice->toXML());
    }
}
