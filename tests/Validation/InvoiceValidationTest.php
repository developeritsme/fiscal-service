<?php

namespace Tests\Validation;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Models\Buyer;
use DeveloperItsMe\FiscalService\Models\CorrectiveInvoice;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use DeveloperItsMe\FiscalService\Models\Seller;
use PHPUnit\Framework\TestCase;

class InvoiceValidationTest extends TestCase
{
    /** @test */
    public function it_throws_validation_exception_when_all_required_fields_missing()
    {
        $invoice = new Invoice();

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            $this->assertArrayHasKey('enu', $errors);
            $this->assertArrayHasKey('operatorCode', $errors);
            $this->assertArrayHasKey('businessUnitCode', $errors);
            $this->assertArrayHasKey('softwareCode', $errors);
            $this->assertArrayHasKey('number', $errors);
            $this->assertArrayHasKey('seller', $errors);
            $this->assertArrayHasKey('items', $errors);
            $this->assertArrayHasKey('paymentMethods', $errors);
        }
    }

    /** @test */
    public function it_passes_validation_with_valid_invoice()
    {
        $invoice = $this->buildValidInvoice();

        $invoice->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_registration_code_patterns()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setEnu('INVALID');

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('enu', $errors);
            $this->assertStringContainsString('registration code', $errors['enu'][0]);
        }
    }

    /** @test */
    public function it_validates_tax_period_pattern()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setTaxPeriod('13/2024');

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('taxPeriod', $errors);
        }
    }

    /** @test */
    public function it_allows_valid_tax_period()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setTaxPeriod('01/2024');

        $invoice->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_allows_null_tax_period()
    {
        $invoice = $this->buildValidInvoice();

        $invoice->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_prefixes_seller_errors()
    {
        $invoice = new Invoice();
        $invoice->setNumber(1)
            ->setEnu('ab123cd456')
            ->setOperatorCode('ab123cd456')
            ->setBusinessUnitCode('ab123cd456')
            ->setSoftwareCode('ab123cd456')
            ->setSeller(new Seller('', ''))
            ->addItem(new Item('Product', 21))
            ->addPaymentMethod(new PaymentMethod(10));

        // Set unit price on the item
        $invoice->getItems()[0]->setUnitPrice(10);

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('seller.name', $errors);
            $this->assertArrayHasKey('seller.idNumber', $errors);
        }
    }

    /** @test */
    public function it_prefixes_buyer_errors()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setBuyer(new Buyer('Buyer', 'invalid-tin'));

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('buyer.idNumber', $errors);
        }
    }

    /** @test */
    public function it_prefixes_item_errors()
    {
        $invoice = new Invoice();
        $invoice->setNumber(1)
            ->setEnu('ab123cd456')
            ->setOperatorCode('ab123cd456')
            ->setBusinessUnitCode('ab123cd456')
            ->setSoftwareCode('ab123cd456')
            ->setSeller(new Seller('Seller', '12345678'))
            ->addItem(new Item())
            ->addPaymentMethod(new PaymentMethod(10));

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('items[0].name', $errors);
            $this->assertArrayHasKey('items[0].unitPrice', $errors);
            $this->assertArrayHasKey('items[0].vatRate', $errors);
        }
    }

    /** @test */
    public function it_prefixes_corrective_errors()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setCorrectiveInvoice(new CorrectiveInvoice('not-hex', '2024-01-01'));

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('corrective.ikof', $errors);
        }
    }

    /** @test */
    public function it_prefixes_supply_period_errors()
    {
        $invoice = $this->buildValidInvoice();
        $invoice->setSupplyPeriod('not-a-date');

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('supplyPeriod.start', $errors);
        }
    }

    /** @test */
    public function it_does_not_validate_issuer_code_or_iic_signature()
    {
        $invoice = $this->buildValidInvoice();

        // These are generated by generateIIC() AFTER validation
        $invoice->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_skips_buyer_validation_when_buyer_not_set()
    {
        $invoice = $this->buildValidInvoice();

        $invoice->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function validation_exception_is_catchable_as_fiscal_exception()
    {
        $this->expectException(\DeveloperItsMe\FiscalService\Exceptions\FiscalException::class);

        $invoice = new Invoice();
        $invoice->validate();
    }

    /** @test */
    public function validation_exception_provides_flat_messages()
    {
        try {
            $invoice = new Invoice();
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $messages = $e->getMessages();
            $this->assertNotEmpty($messages);
            $this->assertContainsOnly('string', $messages);
        }
    }

    /** @test */
    public function it_collects_all_errors_at_once()
    {
        $invoice = new Invoice();

        try {
            $invoice->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            // Should have multiple error keys, not just the first one
            $this->assertGreaterThan(1, count($e->getErrors()));
        }
    }

    private function buildValidInvoice(): Invoice
    {
        $seller = new Seller('Test Seller', '12345678');
        $item = new Item('Product', 21);
        $item->setUnitPrice(10);

        $invoice = new Invoice();
        $invoice->setNumber(1)
            ->setEnu('ab123cd456')
            ->setOperatorCode('ab123cd456')
            ->setBusinessUnitCode('ab123cd456')
            ->setSoftwareCode('ab123cd456')
            ->setSeller($seller)
            ->addItem($item)
            ->addPaymentMethod(new PaymentMethod(10));

        return $invoice;
    }
}
