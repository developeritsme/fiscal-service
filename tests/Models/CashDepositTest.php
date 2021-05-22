<?php

namespace Tests\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use PHPUnit\Framework\TestCase;

class CashDepositTest extends TestCase
{
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

        $this->assertStringEqualsFile('./tests/xml/CashDeposit.xml', $cashDeposit->toXML());
    }
}
