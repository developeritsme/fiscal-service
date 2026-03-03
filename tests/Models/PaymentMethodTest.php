<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Models\Invoice;
use DeveloperItsMe\FiscalService\Models\PaymentMethod;
use PHPUnit\Framework\TestCase;

class PaymentMethodTest extends TestCase
{
    /** @test */
    public function constructor_sets_amount_and_type()
    {
        $pm = new PaymentMethod(99.50, PaymentMethod::TYPE_CARD);

        $arr = $pm->toArray();

        $this->assertEquals(99.50, $arr['amount']);
        $this->assertSame('CARD', $arr['type']);
    }

    /** @test */
    public function constructor_defaults_to_banknote()
    {
        $pm = new PaymentMethod(10);

        $this->assertSame('BANKNOTE', $pm->getType());
    }

    /** @test */
    public function setAdvIIC_is_fluent()
    {
        $pm = new PaymentMethod(10, PaymentMethod::TYPE_ADVANCE);

        $this->assertSame($pm, $pm->setAdvIIC('aabb0011ccdd2233eeff44556677aabb'));
    }

    /** @test */
    public function setCompCard_is_fluent()
    {
        $pm = new PaymentMethod(10, PaymentMethod::TYPE_COMPANY);

        $this->assertSame($pm, $pm->setCompCard('COMP-123'));
    }

    /** @test */
    public function setBankAcc_is_fluent()
    {
        $pm = new PaymentMethod(10);

        $this->assertSame($pm, $pm->setBankAcc('550-123-44'));
    }

    // -- Validation: AdvIIC --

    /** @test */
    public function validate_requires_advIIC_for_advance_type()
    {
        $pm = new PaymentMethod(100, PaymentMethod::TYPE_ADVANCE);

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('advIIC', $e->getErrors());
        }
    }

    /** @test */
    public function validate_requires_advIIC_for_voucher_type()
    {
        $pm = new PaymentMethod(100, PaymentMethod::TYPE_VOUCHER);

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('advIIC', $e->getErrors());
        }
    }

    /** @test */
    public function validate_passes_advance_with_valid_advIIC()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_ADVANCE))
            ->setAdvIIC('aabb0011ccdd2233eeff44556677aabb');

        $pm->validate();

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function validate_fails_advance_with_invalid_advIIC_format()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_ADVANCE))
            ->setAdvIIC('not-hex-32');

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('advIIC', $e->getErrors());
        }
    }

    /** @test */
    public function validate_checks_advIIC_format_on_non_advance_type()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_BANKNOTE))
            ->setAdvIIC('not-hex-32');

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('advIIC', $e->getErrors());
        }
    }

    /** @test */
    public function validate_allows_null_advIIC_on_non_advance_type()
    {
        $pm = new PaymentMethod(100, PaymentMethod::TYPE_BANKNOTE);

        $pm->validate();

        $this->addToAssertionCount(1);
    }

    // -- Validation: CompCard --

    /** @test */
    public function validate_requires_compCard_for_company_type()
    {
        $pm = new PaymentMethod(100, PaymentMethod::TYPE_COMPANY);

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compCard', $e->getErrors());
        }
    }

    /** @test */
    public function validate_passes_company_with_compCard()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_COMPANY))
            ->setCompCard('COMP-123');

        $pm->validate();

        $this->addToAssertionCount(1);
    }

    /** @test */
    public function validate_fails_compCard_exceeding_max_length()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_COMPANY))
            ->setCompCard(str_repeat('A', 51));

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('compCard', $e->getErrors());
        }
    }

    // -- Validation: BankAcc --

    /** @test */
    public function validate_fails_bankAcc_exceeding_max_length()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_BANKNOTE))
            ->setBankAcc(str_repeat('A', 51));

        try {
            $pm->validate();
            $this->fail('Expected ValidationException');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('bankAcc', $e->getErrors());
        }
    }

    /** @test */
    public function validate_allows_valid_bankAcc()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_BANKNOTE))
            ->setBankAcc('550-12332-44');

        $pm->validate();

        $this->addToAssertionCount(1);
    }

    // -- isAllowedForInvoiceType --

    /** @test */
    public function cash_types_are_allowed_for_cash_invoices()
    {
        $cashTypes = [
            PaymentMethod::TYPE_BANKNOTE,
            PaymentMethod::TYPE_CARD,
            PaymentMethod::TYPE_ORDER,
            PaymentMethod::TYPE_OTHER_CASH,
        ];

        foreach ($cashTypes as $type) {
            $pm = new PaymentMethod(10, $type);
            $this->assertTrue($pm->isAllowedForInvoiceType(Invoice::TYPE_CASH), "{$type} should be allowed for CASH");
        }
    }

    /** @test */
    public function noncash_types_are_allowed_for_noncash_invoices()
    {
        $noncashTypes = [
            PaymentMethod::TYPE_BUSINESS_CARD,
            PaymentMethod::TYPE_VOUCHER,
            PaymentMethod::TYPE_COMPANY,
            PaymentMethod::TYPE_ORDER,
            PaymentMethod::TYPE_ADVANCE,
            PaymentMethod::TYPE_ACCOUNT,
            PaymentMethod::TYPE_FACTORING,
            PaymentMethod::TYPE_OTHER,
        ];

        foreach ($noncashTypes as $type) {
            $pm = new PaymentMethod(10, $type);
            $this->assertTrue($pm->isAllowedForInvoiceType(Invoice::TYPE_NONCASH), "{$type} should be allowed for NONCASH");
        }
    }

    /** @test */
    public function noncash_type_is_not_allowed_for_cash_invoice()
    {
        $pm = new PaymentMethod(10, PaymentMethod::TYPE_ADVANCE);

        $this->assertFalse($pm->isAllowedForInvoiceType(Invoice::TYPE_CASH));
    }

    /** @test */
    public function cash_type_is_not_allowed_for_noncash_invoice()
    {
        $pm = new PaymentMethod(10, PaymentMethod::TYPE_BANKNOTE);

        $this->assertFalse($pm->isAllowedForInvoiceType(Invoice::TYPE_NONCASH));
    }

    // -- XML output --

    /** @test */
    public function xml_output_basic()
    {
        $pm = new PaymentMethod(25.50, PaymentMethod::TYPE_CARD);

        $xml = $pm->toXML();

        $this->assertStringContainsString('Amt="25.50"', $xml);
        $this->assertStringContainsString('Type="CARD"', $xml);
        $this->assertStringNotContainsString('AdvIIC', $xml);
        $this->assertStringNotContainsString('CompCard', $xml);
        $this->assertStringNotContainsString('BankAcc', $xml);
    }

    /** @test */
    public function xml_output_includes_advIIC()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_ADVANCE))
            ->setAdvIIC('aabb0011ccdd2233eeff44556677aabb');

        $xml = $pm->toXML();

        $this->assertStringContainsString('AdvIIC="aabb0011ccdd2233eeff44556677aabb"', $xml);
    }

    /** @test */
    public function xml_output_includes_compCard()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_COMPANY))
            ->setCompCard('COMP-123');

        $xml = $pm->toXML();

        $this->assertStringContainsString('CompCard="COMP-123"', $xml);
    }

    /** @test */
    public function xml_output_includes_bankAcc()
    {
        $pm = (new PaymentMethod(100, PaymentMethod::TYPE_ACCOUNT))
            ->setBankAcc('550-12332-44');

        $xml = $pm->toXML();

        $this->assertStringContainsString('BankAcc="550-12332-44"', $xml);
    }

    // -- toArray --

    /** @test */
    public function toArray_returns_complete_structure()
    {
        $pm = (new PaymentMethod(50.00, PaymentMethod::TYPE_ADVANCE))
            ->setAdvIIC('aabb0011ccdd2233eeff44556677aabb')
            ->setCompCard('COMP-123')
            ->setBankAcc('550-12332-44');

        $arr = $pm->toArray();

        $this->assertSame('ADVANCE', $arr['type']);
        $this->assertEquals(50.00, $arr['amount']);
        $this->assertSame('aabb0011ccdd2233eeff44556677aabb', $arr['adv_iic']);
        $this->assertSame('COMP-123', $arr['comp_card']);
        $this->assertSame('550-12332-44', $arr['bank_acc']);
    }

    /** @test */
    public function toArray_has_null_for_optional_fields_when_absent()
    {
        $pm = new PaymentMethod(10);

        $arr = $pm->toArray();

        $this->assertNull($arr['adv_iic']);
        $this->assertNull($arr['comp_card']);
        $this->assertNull($arr['bank_acc']);
    }
}
