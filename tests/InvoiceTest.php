<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\SameTaxes;
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
        $pm2 = new PaymentMethod(24, PaymentMethod::TYPE_CARD);

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
            ->addPaymentMethod($pm2)
            ->setSeller($seller)
            ->addItem($item1)
            ->addItem($item2);

        $this->assertStringEqualsFile('./tests/xml/MultiItemInvoice.xml', $invoice->toXML());
    }

    /** @test */
    public function it_concatenate_data_for_identification_code()
    {
        Carbon::setTestNow('2019-06-12T17:05:43+02:00');

        $invoice = $this->getInvoice();

        $taxes = SameTaxes::make($invoice->getItems());
        $totals = $taxes->getTotals();
        $expected = '12345678|2019-06-12T17:05:43+02:00|9952|bb123bb123|cc123cc123|ss123ss123|99.01';

        $this->assertSame($expected, $invoice->concatenate($totals['total']));
    }


    /** @skip */
    public function it_can_create_identification_code()
    {
        Carbon::setTestNow('2019-06-12T17:05:43+02:00');

        $invoice = $this->getInvoice();

        $taxes = SameTaxes::make($invoice->getItems());
        $totals = $taxes->getTotals();
        $expected = 'A72977773A579523665C3D4F8DEFF3F301CA726A7960EFF5A6863E4CB6009A752C52652C615049A0B2B650380A12D4CC44E7FEB0371FEC42501D95A2F8ACE24A9483EC8AF93219DCC7F58C1E62497B412922B5CAE83A0F914427A769EE550C6510C43DE1FFBF13C911DBADCE66DAC6065B98352276F0B19260457887C20EB351932377B749B4CC0338100D9CB6A202A1EE9BC77B1E584FD9692C26102F603C7ED920E3ABF22DAF4C1D170E954B1D320709E26A429C3B8D45208B7C5CBF5BA1C51713E888ACA00BC60C00BA18E7B1434A196F9F09CBD28B68F4FD1F56EA197B59AF77D6B8459C1CBCAA367089BCC8CEFAE3926DA8183DD822D371230411F4CFFD';
        $expected = 'E4033D471FEEA47A3C664B15C669C709';

        $this->assertSame($expected, $invoice->securityCode($totals['total']));
    }

    protected function getInvoice()
    {
        $invoice = new Invoice();

        $pm = new PaymentMethod(99.01);

        $seller = new Seller('IME PREZIME PRODAVAOCA', '12345678');

        $item = new Item();
        $item->setCode(501234567890)
            ->setName('NAZIV PROIZVODA')
            ->setUnitPrice(99.01)
            ->setVatRate(25);

        return $invoice
            ->setNumber(9952)
            ->setEnu('cc123cc123')
            ->setBusinessUnitCode('bb123bb123')
            ->setSoftwareCode('ss123ss123')
            ->setIssuerCode('4AD5A215BEAF85B0416235736A6DACAB')
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item);
    }
}
