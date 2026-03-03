<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Models\Buyer;
use PHPUnit\Framework\TestCase;

class BuyerTest extends TestCase
{
    /** @test */
    public function setCountry_throws_on_invalid_code()
    {
        $this->expectException(InvalidArgumentException::class);

        $buyer = new Buyer('Test', '12345678');
        $buyer->setCountry('INVALID');
    }

    /** @test */
    public function setCountry_accepts_valid_code()
    {
        $buyer = new Buyer('Test', '12345678');
        $buyer->setCountry('ALB');

        $data = $buyer->toArray();
        $this->assertSame('ALB', $data['country']);
    }
}
