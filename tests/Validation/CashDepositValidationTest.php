<?php

namespace Tests\Validation;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Exceptions\ValidationException;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use PHPUnit\Framework\TestCase;

class CashDepositValidationTest extends TestCase
{
    /** @test */
    public function it_throws_validation_exception_when_all_required_fields_missing()
    {
        $cashDeposit = new CashDeposit();

        try {
            $cashDeposit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            $this->assertArrayHasKey('date', $errors);
            $this->assertArrayHasKey('idNumber', $errors);
            $this->assertArrayHasKey('amount', $errors);
            $this->assertArrayHasKey('enu', $errors);
        }
    }

    /** @test */
    public function it_passes_validation_with_valid_data()
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber('12345678')
            ->setAmount(100)
            ->setEnu('ab123cd456');

        $cashDeposit->validate();

        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_tin_pattern_for_id_number()
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber('invalid-tin')
            ->setAmount(100)
            ->setEnu('ab123cd456');

        try {
            $cashDeposit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('idNumber', $errors);
            $this->assertStringContainsString('TIN', $errors['idNumber'][0]);
        }
    }

    /** @test */
    public function it_validates_registration_code_pattern_for_enu()
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber('12345678')
            ->setAmount(100)
            ->setEnu('INVALID');

        try {
            $cashDeposit->validate();
            $this->fail('Expected ValidationException was not thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();
            $this->assertArrayHasKey('enu', $errors);
            $this->assertStringContainsString('registration code', $errors['enu'][0]);
        }
    }

    /** @test */
    public function it_accepts_13_digit_tin()
    {
        $cashDeposit = (new CashDeposit())
            ->setDate(Carbon::now())
            ->setIdNumber('1234567890123')
            ->setAmount(100)
            ->setEnu('ab123cd456');

        $cashDeposit->validate();

        $this->assertTrue(true);
    }
}
