<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Models\Item;
use PHPUnit\Framework\TestCase;

class ItemTest extends TestCase
{
    /** @test */
    public function no_rebate_produces_same_prices_as_before()
    {
        $item = (new Item('Product', 21))
            ->setUnitPrice(121);

        $this->assertEqualsWithDelta(100.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(121.0, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(121.0, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(100.0, $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function explicit_zero_rebate_is_identical_to_no_rebate()
    {
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setRebate(0);

        $this->assertEqualsWithDelta(100.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(121.0, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(121.0, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(100.0, $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function rebate_reduces_base_price_rr_true()
    {
        // 100€ base, 21% VAT → unit price = 121€
        // 10% rebate with RR=true: effective base = 100 * 0.9 = 90
        // UPA = 90 * 1.21 = 108.90
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setRebate(10, true);

        $this->assertEqualsWithDelta(90.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(90.0, $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function rebate_reduces_base_price_with_quantity()
    {
        // 100€ base, 21% VAT, qty 3, 10% rebate RR=true
        // UPB = 90, UPA = 108.90, PB = 270, PA = 326.70, VA = 56.70
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setQuantity(3)
            ->setRebate(10, true);

        $this->assertEqualsWithDelta(90.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(326.70, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(270.0, $item->totalBasePrice(), 0.0001);
        $this->assertEqualsWithDelta(56.70, $item->totalPrice() - $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function rebate_does_not_reduce_base_price_rr_false()
    {
        // 100€ base, 21% VAT → unit price = 121€
        // 10% rebate with RR=false: base stays 100, UPA = 121 * 0.9 = 108.90
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setRebate(10, false);

        $this->assertEqualsWithDelta(100.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(100.0, $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function rebate_rr_false_with_quantity()
    {
        // 100€ base, 21% VAT, qty 2, 10% rebate RR=false
        // UPB = 100, UPA = 108.90, PB = 200, PA = 217.80
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setQuantity(2)
            ->setRebate(10, false);

        $this->assertEqualsWithDelta(100.0, $item->baseUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(108.90, $item->effectiveUnitPrice(), 0.0001);
        $this->assertEqualsWithDelta(217.80, $item->totalPrice(), 0.0001);
        $this->assertEqualsWithDelta(200.0, $item->totalBasePrice(), 0.0001);
    }

    /** @test */
    public function set_rebate_is_fluent()
    {
        $item = new Item('Product', 21);
        $result = $item->setRebate(10);

        $this->assertSame($item, $result);
    }

    /** @test */
    public function xml_output_includes_dynamic_rebate_values()
    {
        $item = (new Item('Product', 21))
            ->setIsVat(true)
            ->setUnitPrice(121)
            ->setRebate(10, true);

        $xml = $item->toXML();

        $this->assertStringContainsString('R="10.00"', $xml);
        $this->assertStringContainsString('RR="true"', $xml);
    }

    /** @test */
    public function xml_output_includes_rr_false_when_set()
    {
        $item = (new Item('Product', 21))
            ->setIsVat(true)
            ->setUnitPrice(121)
            ->setRebate(10, false);

        $xml = $item->toXML();

        $this->assertStringContainsString('R="10.00"', $xml);
        $this->assertStringContainsString('RR="false"', $xml);
    }

    /** @test */
    public function xml_output_default_rebate_values()
    {
        $item = (new Item('Product', 21))
            ->setIsVat(true)
            ->setUnitPrice(121);

        $xml = $item->toXML();

        $this->assertStringContainsString('R="0.00"', $xml);
        $this->assertStringContainsString('RR="true"', $xml);
    }

    /** @test */
    public function setExemptFromVAT_sets_reason()
    {
        $item = (new Item('Product', 0))->setUnitPrice(100);
        $item->setExemptFromVAT(Item::EXEMPT_CL26);

        $this->assertSame('VAT_CL26', $item->getExemptFromVAT());
    }

    /** @test */
    public function setExemptFromVAT_is_fluent()
    {
        $item = new Item('Product', 0);
        $result = $item->setExemptFromVAT(Item::EXEMPT_CL26);

        $this->assertSame($item, $result);
    }

    /** @test */
    public function setExemptFromVAT_ignores_invalid_value()
    {
        $item = (new Item('Product', 0))->setUnitPrice(100);
        $item->setExemptFromVAT('INVALID_VALUE');

        $this->assertNull($item->getExemptFromVAT());
    }

    /** @test */
    public function setExemptFromVAT_accepts_null_to_clear()
    {
        $item = (new Item('Product', 0))->setUnitPrice(100);
        $item->setExemptFromVAT(Item::EXEMPT_CL26);
        $item->setExemptFromVAT(null);

        $this->assertNull($item->getExemptFromVAT());
    }

    /** @test */
    public function xml_output_includes_exempt_attribute_for_vat_item()
    {
        $item = (new Item('Product', 0))
            ->setIsVat(true)
            ->setUnitPrice(100)
            ->setExemptFromVAT(Item::EXEMPT_CL26);

        $xml = $item->toXML();

        $this->assertStringContainsString('EX="VAT_CL26"', $xml);
    }

    /** @test */
    public function xml_output_omits_exempt_when_not_set()
    {
        $item = (new Item('Product', 21))
            ->setIsVat(true)
            ->setUnitPrice(121);

        $xml = $item->toXML();

        $this->assertStringNotContainsString('EX=', $xml);
    }

    /** @test */
    public function validate_fails_when_exempt_and_vat_rate_positive()
    {
        $item = (new Item('Product', 21))
            ->setUnitPrice(121)
            ->setExemptFromVAT(Item::EXEMPT_CL26);

        $this->expectException(ValidationException::class);
        $item->validate();
    }

    /** @test */
    public function validate_passes_when_exempt_and_vat_rate_zero()
    {
        $item = (new Item('Product', 0))
            ->setUnitPrice(100)
            ->setExemptFromVAT(Item::EXEMPT_CL26);

        $item->validate();
        $this->addToAssertionCount(1);
    }

    /** @test */
    public function toArray_includes_exempt_from_vat()
    {
        $item = (new Item('Product', 0))
            ->setUnitPrice(100)
            ->setExemptFromVAT(Item::EXEMPT_CL26);

        $array = $item->toArray();

        $this->assertSame('VAT_CL26', $array['exempt_from_vat']);
    }

    /** @test */
    public function toArray_includes_null_exempt_from_vat_when_not_set()
    {
        $item = (new Item('Product', 21))
            ->setUnitPrice(121);

        $array = $item->toArray();

        $this->assertArrayHasKey('exempt_from_vat', $array);
        $this->assertNull($array['exempt_from_vat']);
    }
}
