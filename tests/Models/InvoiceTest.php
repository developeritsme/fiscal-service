<?php

namespace Tests\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Exceptions\FiscalException;
use DeveloperItsMe\FiscalService\Models\Buyer;
use DeveloperItsMe\FiscalService\Models\CorrectiveInvoice;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\SameTaxes;
use DeveloperItsMe\FiscalService\Models\Seller;
use PHPUnit\Framework\TestCase;

class InvoiceTest extends TestCase
{
    protected $iicSignature = '83D728C8E10BA04C430BE64CE98612B0256C0FE618C167F28BF62A0C0CB38C51824F152AB00510AE076508E53ACE4F877D25D51C7830F043E09BB1500D3A0AEA233ECC6175A45FE58CBF53E517FD9EA1D06CBABC055EEE6B430A16560C96D3A27720A6E5C9BA5C8D18A7AE5C2A7F1D8E46B293F56D32847FCEE199D2AFDC6E5BC1164BA974A6E29D6F40FBD8C51D40A99BC97DD6DB2AE9EC0582F2E74E9C7841AC5A854DE92B1D778A809CACCBBEF4DC325C852487BCF035AA2D54594DC6BDD859E250782CCCDD7CC89EE80A2FE1030AAAD615DA5D728322F8590D9F56E6DDE5975A738F304F56BB832996763624B72C77E97881D9C647B50709F20AFBFA0602';

    protected $issuerCode = '4AD5A215BEAF85B0416235736A6DACAB';

    /** @test */
    public function it_generates_proper_xml_for_single_item()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $pm = new PaymentMethod(20);

        $seller = new Seller('IME PREZIME PRODAVAOCA', 'ID BROJ PRODAVAOCA');
        $seller->setAddress('ADRESA PRODAVAOCA')
            ->setTown('GRAD PRODAVAOCA');

        $item = new Item('NAZIV PROIZVODA', 25);
        $item->setCode(501234567890)
            ->setUnitPrice(20);

        $invoice->setUuid('8d216f9a-55bb-445a-be32-30137f11b964')
            ->setNumber(1)
            ->setEnu('TCRCode')
            ->setOperatorCode('ab123ab123')
            ->setBusinessUnitCode('ab123ab123')
            ->setSoftwareCode('PRIMJER KODA SOFTVERA')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
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

        $item1 = (new Item('NAZIV PROIZVODA', 25))
            ->setCode(501234567890)
            ->setUnitPrice(20);

        $item2 = (new Item('NAZIV PROIZVODA 2', 20))
            ->setQuantity(2)
            ->setUnitPrice(12);

        $invoice->setUuid('8d216f9a-55bb-445a-be32-30137f11b964')
            ->setNumber(1)
            ->setEnu('TCRCode')
            ->setOperatorCode('ab123ab123')
            ->setBusinessUnitCode('ab123ab123')
            ->setSoftwareCode('PRIMJER KODA SOFTVERA')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
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

    /** @test */
    public function it_throws_exception_when_iic_signing_fails()
    {
        $this->expectException(FiscalException::class);
        $this->expectExceptionMessage('Unable to sign invoice data for IIC generation');

        set_error_handler(function () {
            return true;
        });
        try {
            $invoice = $this->getInvoice();
            $invoice->generateIIC('invalid-key');
        } finally {
            restore_error_handler();
        }
    }

    /** @test */
    public function it_rejects_zero_as_invoice_number()
    {
        $invoice = new Invoice();
        $invoice->setNumber(0);

        $reflection = new \ReflectionProperty(Invoice::class, 'number');
        $reflection->setAccessible(true);

        $this->assertNull($reflection->getValue($invoice));
    }

    /** @test */
    public function setMethod_sets_cash()
    {
        $invoice = new Invoice();
        $invoice->setMethod(Invoice::TYPE_CASH);

        $reflection = new \ReflectionProperty(Invoice::class, 'method');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_CASH, $reflection->getValue($invoice));
    }

    /** @test */
    public function setMethod_sets_noncash()
    {
        $invoice = new Invoice();
        $invoice->setMethod(Invoice::TYPE_NONCASH);

        $reflection = new \ReflectionProperty(Invoice::class, 'method');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_NONCASH, $reflection->getValue($invoice));
    }

    /** @test */
    public function setMethod_ignores_invalid_value()
    {
        $invoice = new Invoice();
        $invoice->setMethod('INVALID');

        $reflection = new \ReflectionProperty(Invoice::class, 'method');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_CASH, $reflection->getValue($invoice));
    }

    /** @test */
    public function setInvoiceType_sets_type()
    {
        $invoice = new Invoice();
        $invoice->setInvoiceType(Invoice::TYPE_ADVANCE);

        $reflection = new \ReflectionProperty(Invoice::class, 'type');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_ADVANCE, $reflection->getValue($invoice));
    }

    /** @test */
    public function setInvoiceType_ignores_invalid_value()
    {
        $invoice = new Invoice();
        $invoice->setInvoiceType('INVALID');

        $reflection = new \ReflectionProperty(Invoice::class, 'type');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_INVOICE, $reflection->getValue($invoice));
    }

    /** @test */
    public function setCorrectiveInvoice_sets_type_to_corrective()
    {
        $invoice = new Invoice();
        $invoice->setCorrectiveInvoice(new CorrectiveInvoice('ikof123', '2025-01-01'));

        $reflection = new \ReflectionProperty(Invoice::class, 'type');
        $reflection->setAccessible(true);

        $this->assertSame(Invoice::TYPE_CORRECTIVE, $reflection->getValue($invoice));
    }

    /** @test */
    public function deprecated_setType_routes_noncash_to_method()
    {
        $invoice = new Invoice();
        $invoice->setType(Invoice::TYPE_NONCASH);

        $methodRef = new \ReflectionProperty(Invoice::class, 'method');
        $methodRef->setAccessible(true);

        $this->assertSame(Invoice::TYPE_NONCASH, $methodRef->getValue($invoice));
    }

    /** @test */
    public function deprecated_setType_routes_corrective_to_type()
    {
        $invoice = new Invoice();
        $invoice->setType(Invoice::TYPE_CORRECTIVE);

        $typeRef = new \ReflectionProperty(Invoice::class, 'type');
        $typeRef->setAccessible(true);

        $this->assertSame(Invoice::TYPE_CORRECTIVE, $typeRef->getValue($invoice));
    }

    /** @test */
    public function setMethod_and_setInvoiceType_are_fluent()
    {
        $invoice = new Invoice();

        $this->assertSame($invoice, $invoice->setMethod(Invoice::TYPE_CASH));
        $this->assertSame($invoice, $invoice->setInvoiceType(Invoice::TYPE_INVOICE));
    }

    /** @test */
    public function toArray_returns_complete_structure()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $seller = new Seller('Test Seller', '12345678');
        $seller->setAddress('Test Address')->setTown('Podgorica');

        $buyer = new Buyer('Test Buyer', '87654321');
        $buyer->setAddress('Buyer Address')->setTown('Niksic');

        $item = new Item('Product A', 21);
        $item->setCode('P001')->setUnitPrice(100)->setQuantity(2);

        $pm = new PaymentMethod(200, PaymentMethod::TYPE_BANKNOTE);

        $invoice->setUuid('test-uuid-1234')
            ->setNumber(42)
            ->setEnu('en123en123')
            ->setOperatorCode('op123op123')
            ->setBusinessUnitCode('bu123bu123')
            ->setSoftwareCode('sw123sw123')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
            ->setTaxPeriod('01/2019')
            ->setSeller($seller)
            ->setBuyer($buyer)
            ->addItem($item)
            ->addPaymentMethod($pm)
            ->setCorrectiveInvoice(new CorrectiveInvoice('aabb0011ccdd2233eeff44556677aabb', '2019-11-01T10:00:00+01:00'))
            ->setSupplyPeriod('2019-12-01', '2019-12-31');

        $arr = $invoice->toArray();

        $this->assertSame('test-uuid-1234', $arr['uuid']);
        $this->assertSame('bu123bu123/42/2019/en123en123', $arr['number']);
        $this->assertSame(42, $arr['ordinal_number']);
        $this->assertSame('2019-12-05T14:30:13+01:00', $arr['date_time']);
        $this->assertSame('CASH', $arr['method']);
        $this->assertSame('CORRECTIVE', $arr['type']);
        $this->assertSame('bu123bu123', $arr['business_unit_code']);
        $this->assertSame('op123op123', $arr['operator_code']);
        $this->assertSame('en123en123', $arr['tcr_code']);
        $this->assertSame('sw123sw123', $arr['software_code']);
        $this->assertSame($this->issuerCode, $arr['issuer_code']);
        $this->assertSame($this->iicSignature, $arr['iic_signature']);
        $this->assertSame('01/2019', $arr['tax_period']);

        $this->assertArrayHasKey('total', $arr['totals']);
        $this->assertArrayHasKey('base', $arr['totals']);
        $this->assertArrayHasKey('vat', $arr['totals']);

        $this->assertSame('Test Seller', $arr['seller']['name']);
        $this->assertSame('12345678', $arr['seller']['id_number']);
        $this->assertSame('TIN', $arr['seller']['id_type']);
        $this->assertSame('Test Address', $arr['seller']['address']);
        $this->assertSame('Podgorica', $arr['seller']['town']);
        $this->assertSame('MNE', $arr['seller']['country']);
        $this->assertTrue($arr['seller']['is_vat']);

        $this->assertSame('Test Buyer', $arr['buyer']['name']);
        $this->assertSame('87654321', $arr['buyer']['id_number']);

        $this->assertCount(1, $arr['items']);
        $this->assertSame('Product A', $arr['items'][0]['name']);
        $this->assertSame('P001', $arr['items'][0]['code']);
        $this->assertEquals(2, $arr['items'][0]['quantity']);
        $this->assertEquals(100, $arr['items'][0]['unit_price']);
        $this->assertEquals(21, $arr['items'][0]['vat_rate']);

        $this->assertCount(1, $arr['payment_methods']);
        $this->assertSame('BANKNOTE', $arr['payment_methods'][0]['type']);
        $this->assertEquals(200, $arr['payment_methods'][0]['amount']);

        $this->assertSame('aabb0011ccdd2233eeff44556677aabb', $arr['corrective_invoice']['ikof']);
        $this->assertSame('CORRECTIVE', $arr['corrective_invoice']['type']);

        $this->assertSame('2019-12-01', $arr['supply_period']['start']);
        $this->assertSame('2019-12-31', $arr['supply_period']['end']);
    }

    /** @test */
    public function toArray_has_null_for_optional_fields_when_absent()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $seller = new Seller('Seller', '12345678');
        $item = new Item('Item', 21);
        $item->setUnitPrice(50);
        $pm = new PaymentMethod(50);

        $invoice->setUuid('uuid-test')
            ->setNumber(1)
            ->setEnu('en123en123')
            ->setOperatorCode('op123op123')
            ->setBusinessUnitCode('bu123bu123')
            ->setSoftwareCode('sw123sw123')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
            ->setSeller($seller)
            ->addItem($item)
            ->addPaymentMethod($pm);

        $arr = $invoice->toArray();

        $this->assertNull($arr['buyer']);
        $this->assertNull($arr['corrective_invoice']);
        $this->assertNull($arr['supply_period']);
        $this->assertNull($arr['tax_period']);
    }

    /** @test */
    public function toArray_includes_multiple_items_and_payment_methods()
    {
        Carbon::setTestNow('2019-12-05T14:30:13+01:00');

        $invoice = new Invoice();

        $seller = new Seller('Seller', '12345678');

        $item1 = (new Item('Item A', 21))->setUnitPrice(10);
        $item2 = (new Item('Item B', 7))->setUnitPrice(20);
        $item3 = (new Item('Item C', 0))->setUnitPrice(30);

        $pm1 = new PaymentMethod(30, PaymentMethod::TYPE_BANKNOTE);
        $pm2 = new PaymentMethod(30, PaymentMethod::TYPE_CARD);

        $invoice->setUuid('uuid-multi')
            ->setNumber(5)
            ->setEnu('en123en123')
            ->setOperatorCode('op123op123')
            ->setBusinessUnitCode('bu123bu123')
            ->setSoftwareCode('sw123sw123')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
            ->setSeller($seller)
            ->addItem($item1)
            ->addItem($item2)
            ->addItem($item3)
            ->addPaymentMethod($pm1)
            ->addPaymentMethod($pm2);

        $arr = $invoice->toArray();

        $this->assertCount(3, $arr['items']);
        $this->assertSame('Item A', $arr['items'][0]['name']);
        $this->assertSame('Item B', $arr['items'][1]['name']);
        $this->assertSame('Item C', $arr['items'][2]['name']);

        $this->assertCount(2, $arr['payment_methods']);
        $this->assertSame('BANKNOTE', $arr['payment_methods'][0]['type']);
        $this->assertSame('CARD', $arr['payment_methods'][1]['type']);
    }

    protected function getInvoice()
    {
        $invoice = new Invoice();

        $pm = new PaymentMethod(99.01);

        $seller = new Seller('IME PREZIME PRODAVAOCA', '12345678');

        $item = new Item('NAZIV PROIZVODA', 25);
        $item->setCode(501234567890)
            ->setUnitPrice(99.01);

        return $invoice
            ->setNumber(9952)
            ->setEnu('cc123cc123')
            ->setBusinessUnitCode('bb123bb123')
            ->setSoftwareCode('ss123ss123')
            ->setIssuerCode($this->issuerCode)
            ->setIicSignature($this->iicSignature)
            ->addPaymentMethod($pm)
            ->setSeller($seller)
            ->addItem($item);
    }
}
