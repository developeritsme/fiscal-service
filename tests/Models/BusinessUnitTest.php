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

    /** @test */
    public function toArray_returns_complete_structure()
    {
        Carbon::setTestNow('2019-12-02T10:57:01+01:00');

        $businessUnit = new BusinessUnit();
        $businessUnit->setUuid('78b37523-3677-416a-8bc0-e0dd77296fc7')
            ->setIdNumber('02657597')
            ->setUnitCode('ab123ab123')
            ->setInternalId(1)
            ->setSoftwareCode('ab123ab123')
            ->setMaintainerCode('ab123ab123')
            ->setValidFrom('2019-12-05')
            ->setValidTo('2020-12-05');

        $arr = $businessUnit->toArray();

        $this->assertSame('78b37523-3677-416a-8bc0-e0dd77296fc7', $arr['uuid']);
        $this->assertSame('ab123ab123', $arr['unit_code']);
        $this->assertSame('02657597', $arr['id_number']);
        $this->assertEquals(1, $arr['internal_id']);
        $this->assertSame('ab123ab123', $arr['maintainer_code']);
        $this->assertSame('ab123ab123', $arr['software_code']);
        $this->assertSame('2019-12-05', $arr['valid_from']);
        $this->assertSame('2020-12-05', $arr['valid_to']);
        $this->assertSame(BusinessUnit::TYPE_REGULAR, $arr['type']);
    }

    /** @test */
    public function toArray_has_null_for_optional_fields_when_absent()
    {
        $businessUnit = new BusinessUnit();
        $businessUnit->setIdNumber('02657597')
            ->setUnitCode('ab123ab123')
            ->setInternalId(1);

        $arr = $businessUnit->toArray();

        $this->assertNull($arr['maintainer_code']);
        $this->assertNull($arr['software_code']);
        $this->assertNull($arr['valid_to']);
    }
}
