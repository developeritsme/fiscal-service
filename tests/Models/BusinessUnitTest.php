<?php

namespace Tests\Models;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\BusinessUnit;
use PHPUnit\Framework\TestCase;

class BusinessUnitTest extends TestCase
{
    /** @test */
    public function it_generates_proper_xml()
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

        $this->assertStringEqualsFile('./tests/xml/BusinessUnit.xml', $businessUnit->toXML());
    }
}
