<?php

namespace Tests\Validation;

use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Models\BusinessUnit;
use PHPUnit\Framework\TestCase;

class BusinessUnitValidationTest extends TestCase
{
    /** @test */
    public function it_throws_validation_exception_when_all_required_fields_missing()
    {
        $unit = new BusinessUnit();

        try {
            $unit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            $this->assertArrayHasKey('unitCode', $errors);
            $this->assertArrayHasKey('idNumber', $errors);
            $this->assertArrayHasKey('internalId', $errors);
        }
    }

    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('12345678')
            ->setInternalId('1');

        $unit->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_registration_code_pattern_for_unit_code()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('INVALID')
            ->setIdNumber('12345678')
            ->setInternalId('1');

        try {
            $unit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('unitCode', $errors);
            $this->assertStringContainsString('registration code', $errors['unitCode'][0]);
        }
    }

    /** @test */
    public function it_validates_tin_pattern_for_id_number()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('invalid')
            ->setInternalId('1');

        try {
            $unit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('idNumber', $errors);
            $this->assertStringContainsString('TIN', $errors['idNumber'][0]);
        }
    }

    /** @test */
    public function it_validates_optional_maintainer_code_pattern()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('12345678')
            ->setInternalId('1')
            ->setMaintainerCode('INVALID');

        try {
            $unit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('maintainerCode', $errors);
        }
    }

    /** @test */
    public function it_validates_optional_software_code_pattern()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('12345678')
            ->setInternalId('1')
            ->setSoftwareCode('INVALID');

        try {
            $unit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('softwareCode', $errors);
        }
    }

    /** @test */
    public function it_allows_null_optional_codes()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('12345678')
            ->setInternalId('1');

        $unit->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_allows_valid_optional_codes()
    {
        $unit = (new BusinessUnit())
            ->setUnitCode('ab123cd456')
            ->setIdNumber('12345678')
            ->setInternalId('1')
            ->setMaintainerCode('ab123cd456')
            ->setSoftwareCode('ab123cd456');

        $unit->validate();

        $this->assertTrue(true);
    }
}
