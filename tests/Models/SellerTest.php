<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Exceptions\InvalidArgumentException;
use DeveloperItsMe\FiscalService\Models\Seller;
use PHPUnit\Framework\TestCase;

class SellerTest extends TestCase
{
    /** @test */
    public function setCountry_throws_on_non_mne()
    {
        $this->expectException(InvalidArgumentException::class);

        $seller = new Seller('Test', '12345678');
        $seller->setCountry('ALB');
    }

    /** @test */
    public function setCountry_accepts_mne()
    {
        $seller = new Seller('Test', '12345678');
        $seller->setCountry('MNE');

        $this->assertSame('MNE', $seller->toArray()['country']);
    }

    /** @test */
    public function setIdNumber_throws_on_invalid_tin()
    {
        $this->expectException(InvalidArgumentException::class);

        new Seller('Test', 'INVALID');
    }

    /** @test */
    public function setIdNumber_accepts_8_digit_tin()
    {
        $seller = new Seller('Test', '12345678');

        $this->assertSame('12345678', $seller->getIdNumber());
    }

    /** @test */
    public function setIdNumber_accepts_13_digit_tin()
    {
        $seller = new Seller('Test', '1234567890123');

        $this->assertSame('1234567890123', $seller->getIdNumber());
    }

    /** @test */
    public function constructor_throws_on_invalid_tin()
    {
        $this->expectException(InvalidArgumentException::class);

        new Seller('Test', '123');
    }
}
