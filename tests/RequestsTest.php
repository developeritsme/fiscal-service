<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\BusinessUnit;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\Seller;
use DeveloperItsMe\FiscalService\Requests\RegisterCashDeposit;
use DeveloperItsMe\FiscalService\Requests\RegisterInvoice;
use DeveloperItsMe\FiscalService\Requests\RegisterTCR;
use DeveloperItsMe\FiscalService\Requests\Request;
use PHPUnit\Framework\TestCase;

class RequestsTest extends TestCase
{
    /** @test */
    public function it_envelopes_request()
    {
        $envelopeStart = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
    <SOAP-ENV:Header/>
    <SOAP-ENV:Body>';

        $envelopeEnd = '</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
';

        $request = new TestEnvelope();
        $enveloped = $request->envelope();
        $full = $envelopeStart . $xml = $request->toXml() . $envelopeEnd;

        $this->assertStringStartsWith($envelopeStart, $enveloped);
        $this->assertStringContainsString($xml, $enveloped);
        $this->assertStringEndsWith($envelopeEnd, $enveloped);
        $this->assertXmlStringEqualsXmlString($full, $enveloped);
    }

    /** @test */
    public function register_invoice_generates_proper_xml()
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

        $request = new RegisterInvoice($invoice);
        $this->assertStringEqualsFile('./tests/xml/RegisterInvoice.xml', $request->toXML());
    }

    /** @test */
    public function register_TCR_generates_proper_xml()
    {
        Carbon::setTestNow('2019-12-02T10:57:01+01:00');

        $businessUnit = new BusinessUnit();
        $businessUnit->setUuid('78b37523-3677-416a-8bc0-e0dd77296fc7')
            ->setIdNumber('02657597')
            ->setUnitCode('ab123ab123')
            ->setInternalId(1)
            ->setSoftwareCode('ab123ab123')
            ->setMaintainerCode('123')
            ->setValidFrom('2019-12-05');

        $request = new RegisterTCR($businessUnit);

        $this->assertStringEqualsFile('./tests/xml/RegisterTCR.xml', $request->toXML());
    }

    /** @test */
    public function it_generates_proper_xml()
    {
        Carbon::setTestNow('2019-12-05T14:35:00+01:00');

        $cashDeposit = new CashDeposit();

        $cashDeposit->setUuid('3389b9c4-bb24-4673-b952-456e451cd3c3')
            ->setDate('2019-12-05T14:35:00+01:00')
            ->setIdNumber('PRIMJER PIB-A')
            ->setAmount(2000.00)
            ->setEnu('KOD BLAGAJNE');

        $request = new RegisterCashDeposit($cashDeposit);

        $this->assertStringEqualsFile('./tests/xml/RegisterCashDeposit.xml', $request->toXML());
    }
}

class TestEnvelope extends Request
{

    public function toXML(): string
    {
        return '<test/>';
    }
}
