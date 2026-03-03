<?php

namespace Tests\Traits;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Models\Invoice;
use PHPUnit\Framework\TestCase;

class HasDecimalsTest extends TestCase
{
    /** @test */
    public function setDecimals_throws_on_invalid_value()
    {
        foreach ([0, 1, 5] as $value) {
            try {
                new Invoice($value);
                $this->fail("Expected InvalidArgumentException for decimals={$value}");
            } catch (InvalidArgumentException $e) {
                $this->addToAssertionCount(1);
            }
        }
    }

    /** @test */
    public function setDecimals_accepts_2()
    {
        $invoice = new Invoice(2);

        $reflection = new \ReflectionProperty(Invoice::class, 'decimals');
        $reflection->setAccessible(true);

        $this->assertSame(2, $reflection->getValue($invoice));
    }

    /** @test */
    public function setDecimals_accepts_3()
    {
        $invoice = new Invoice(3);

        $reflection = new \ReflectionProperty(Invoice::class, 'decimals');
        $reflection->setAccessible(true);

        $this->assertSame(3, $reflection->getValue($invoice));
    }

    /** @test */
    public function setDecimals_accepts_4()
    {
        $invoice = new Invoice(4);

        $reflection = new \ReflectionProperty(Invoice::class, 'decimals');
        $reflection->setAccessible(true);

        $this->assertSame(4, $reflection->getValue($invoice));
    }
}
