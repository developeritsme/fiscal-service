<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Models\Item;
use DeveloperItsMe\FiscalService\Models\SameTaxes;
use PHPUnit\Framework\TestCase;

class SameTaxesTest extends TestCase
{
    /** @test */
    public function single_item_produces_one_group_with_correct_totals()
    {
        // 100 base, 21% VAT → unit price 121
        $item = (new Item('Product A', 21))->setUnitPrice(121);

        $taxes = SameTaxes::make([$item]);
        $totals = $taxes->getTotals();

        $this->assertEqualsWithDelta(100.0, $totals['base'], 0.01);
        $this->assertEqualsWithDelta(21.0, $totals['vat'], 0.01);
        $this->assertEqualsWithDelta(121.0, $totals['total'], 0.01);
    }

    /** @test */
    public function single_item_xml_has_one_same_tax_element()
    {
        $item = (new Item('Product A', 21))->setUnitPrice(121);

        $taxes = SameTaxes::make([$item]);
        $xml = $taxes->toXML();

        $this->assertStringContainsString('NumOfItems="1"', $xml);
        $this->assertStringContainsString('PriceBefVAT="100.00"', $xml);
        $this->assertStringContainsString('VATAmt="21.00"', $xml);
        $this->assertStringContainsString('VATRate="21.00"', $xml);
    }

    /** @test */
    public function multiple_items_with_same_vat_rate_are_aggregated()
    {
        // Two items at 21% VAT
        $item1 = (new Item('Product A', 21))->setUnitPrice(121);
        $item2 = (new Item('Product B', 21))->setUnitPrice(60.50); // base = 50

        $taxes = SameTaxes::make([$item1, $item2]);
        $totals = $taxes->getTotals();

        // base = 100 + 50 = 150, vat = 21 + 10.50 = 31.50, total = 181.50
        $this->assertEqualsWithDelta(150.0, $totals['base'], 0.01);
        $this->assertEqualsWithDelta(31.50, $totals['vat'], 0.01);
        $this->assertEqualsWithDelta(181.50, $totals['total'], 0.01);

        $xml = $taxes->toXML();
        $this->assertStringContainsString('NumOfItems="2"', $xml);
        $this->assertStringContainsString('PriceBefVAT="150.00"', $xml);
        $this->assertStringContainsString('VATAmt="31.50"', $xml);
        $this->assertStringContainsString('VATRate="21.00"', $xml);
    }

    /** @test */
    public function multiple_items_with_different_vat_rates_produce_separate_groups()
    {
        // 20% VAT: base = 100, vat = 20
        $item1 = (new Item('Product A', 20))->setUnitPrice(120);
        // 21% VAT: base = 100, vat = 21
        $item2 = (new Item('Product B', 21))->setUnitPrice(121);

        $taxes = SameTaxes::make([$item1, $item2]);
        $totals = $taxes->getTotals();

        $this->assertEqualsWithDelta(200.0, $totals['base'], 0.01);
        $this->assertEqualsWithDelta(41.0, $totals['vat'], 0.01);
        $this->assertEqualsWithDelta(241.0, $totals['total'], 0.01);

        $xml = $taxes->toXML();

        // Two SameTax elements, sorted by rate (20 before 21)
        $this->assertSame(2, substr_count($xml, '<SameTax '));

        // 20% group
        $this->assertStringContainsString('VATRate="20.00"', $xml);
        // 21% group
        $this->assertStringContainsString('VATRate="21.00"', $xml);

        // Verify sort order: 20% appears before 21%
        $pos20 = strpos($xml, 'VATRate="20.00"');
        $pos21 = strpos($xml, 'VATRate="21.00"');
        $this->assertLessThan($pos21, $pos20);
    }

    /** @test */
    public function xml_output_matches_expected_structure()
    {
        $item = (new Item('Product A', 25))->setUnitPrice(100);

        // base = 100 / 1.25 = 80, vat = 20
        $taxes = SameTaxes::make([$item]);
        $xml = $taxes->toXML();

        $this->assertStringContainsString('<SameTaxes>', $xml);
        $this->assertStringContainsString('</SameTaxes>', $xml);
        $this->assertStringContainsString('NumOfItems="1"', $xml);
        $this->assertStringContainsString('PriceBefVAT="80.00"', $xml);
        $this->assertStringContainsString('VATAmt="20.00"', $xml);
        $this->assertStringContainsString('VATRate="25.00"', $xml);
        $this->assertSame(1, substr_count($xml, '<SameTax '));
    }

    /** @test */
    public function items_with_quantity_are_handled_correctly()
    {
        // qty 3, unitPrice 121, 21% VAT
        // totalBase = 3 * 100 = 300, totalPrice = 3 * 121 = 363, vat = 63
        $item = (new Item('Product A', 21))
            ->setUnitPrice(121)
            ->setQuantity(3);

        $taxes = SameTaxes::make([$item]);
        $totals = $taxes->getTotals();

        $this->assertEqualsWithDelta(300.0, $totals['base'], 0.01);
        $this->assertEqualsWithDelta(63.0, $totals['vat'], 0.01);
        $this->assertEqualsWithDelta(363.0, $totals['total'], 0.01);
    }

    /** @test */
    public function totals_are_cached_on_repeated_calls()
    {
        $item = (new Item('Product A', 21))->setUnitPrice(121);

        $taxes = SameTaxes::make([$item]);

        $first = $taxes->getTotals();
        $second = $taxes->getTotals();

        $this->assertSame($first, $second);
    }
}
