<?php

namespace Tests\Validation;

use DeveloperItsMe\FiscalService\Validation\ValidationHelper;
use PHPUnit\Framework\TestCase;

class ValidationHelperTest extends TestCase
{
    /** @test */
    public function required_adds_error_for_null()
    {
        $errors = [];
        ValidationHelper::required($errors, null, 'field', 'Field');

        $this->assertArrayHasKey('field', $errors);
        $this->assertSame('Field is required.', $errors['field'][0]);
    }

    /** @test */
    public function required_adds_error_for_empty_string()
    {
        $errors = [];
        ValidationHelper::required($errors, '', 'field', 'Field');

        $this->assertArrayHasKey('field', $errors);
    }

    /** @test */
    public function required_does_not_add_error_for_valid_value()
    {
        $errors = [];
        ValidationHelper::required($errors, 'value', 'field', 'Field');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function pattern_skips_null_values()
    {
        $errors = [];
        ValidationHelper::pattern($errors, null, ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function pattern_skips_empty_string()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '', ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function pattern_adds_error_for_non_matching_value()
    {
        $errors = [];
        ValidationHelper::pattern($errors, 'abc', ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertArrayHasKey('field', $errors);
        $this->assertSame('Field must match TIN format.', $errors['field'][0]);
    }

    /** @test */
    public function pattern_does_not_add_error_for_matching_value()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '12345678', ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function required_and_pattern_adds_both_errors()
    {
        $errors = [];
        ValidationHelper::requiredAndPattern($errors, null, ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertCount(1, $errors['field']);
        $this->assertSame('Field is required.', $errors['field'][0]);
    }

    /** @test */
    public function required_and_pattern_adds_pattern_error_for_invalid_value()
    {
        $errors = [];
        ValidationHelper::requiredAndPattern($errors, 'abc', ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertCount(1, $errors['field']);
        $this->assertSame('Field must match TIN format.', $errors['field'][0]);
    }

    /** @test */
    public function required_and_pattern_passes_for_valid_value()
    {
        $errors = [];
        ValidationHelper::requiredAndPattern($errors, '12345678', ValidationHelper::TIN, 'field', 'Field', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function positive_adds_error_for_zero()
    {
        $errors = [];
        ValidationHelper::positive($errors, 0, 'field', 'Field');

        $this->assertArrayHasKey('field', $errors);
        $this->assertSame('Field must be greater than 0.', $errors['field'][0]);
    }

    /** @test */
    public function positive_adds_error_for_negative()
    {
        $errors = [];
        ValidationHelper::positive($errors, -1, 'field', 'Field');

        $this->assertArrayHasKey('field', $errors);
    }

    /** @test */
    public function positive_does_not_add_error_for_positive_value()
    {
        $errors = [];
        ValidationHelper::positive($errors, 1, 'field', 'Field');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function positive_does_not_add_error_for_null()
    {
        $errors = [];
        ValidationHelper::positive($errors, null, 'field', 'Field');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function not_empty_adds_error_for_empty_array()
    {
        $errors = [];
        ValidationHelper::notEmpty($errors, [], 'field', 'Field');

        $this->assertArrayHasKey('field', $errors);
        $this->assertSame('Field must not be empty.', $errors['field'][0]);
    }

    /** @test */
    public function not_empty_does_not_add_error_for_non_empty_array()
    {
        $errors = [];
        ValidationHelper::notEmpty($errors, ['item'], 'field', 'Field');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function errors_accumulate_across_multiple_calls()
    {
        $errors = [];
        ValidationHelper::required($errors, null, 'a', 'A');
        ValidationHelper::required($errors, null, 'b', 'B');
        ValidationHelper::pattern($errors, 'invalid', ValidationHelper::TIN, 'c', 'C', 'TIN');

        $this->assertCount(3, $errors);
        $this->assertArrayHasKey('a', $errors);
        $this->assertArrayHasKey('b', $errors);
        $this->assertArrayHasKey('c', $errors);
    }

    /** @test */
    public function registration_code_pattern_matches_valid_codes()
    {
        $errors = [];
        ValidationHelper::pattern($errors, 'ab123cd456', ValidationHelper::REGISTRATION_CODE, 'f', 'F', 'reg');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function registration_code_pattern_rejects_invalid_codes()
    {
        $errors = [];
        ValidationHelper::pattern($errors, 'INVALID', ValidationHelper::REGISTRATION_CODE, 'f', 'F', 'reg');

        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function tin_pattern_matches_8_digit_number()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '02657597', ValidationHelper::TIN, 'f', 'F', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function tin_pattern_matches_13_digit_number()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '1234567890123', ValidationHelper::TIN, 'f', 'F', 'TIN');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function hex_32_pattern_matches_valid_hex()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '4AD5A215BEAF85B0416235736A6DACAB', ValidationHelper::HEX_32, 'f', 'F', 'HEX-32');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function tax_period_pattern_matches_valid_period()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '01/2024', ValidationHelper::TAX_PERIOD, 'f', 'F', 'tax period');

        $this->assertEmpty($errors);
    }

    /** @test */
    public function tax_period_pattern_rejects_invalid_month()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '13/2024', ValidationHelper::TAX_PERIOD, 'f', 'F', 'tax period');

        $this->assertNotEmpty($errors);
    }

    /** @test */
    public function date_pattern_matches_valid_date()
    {
        $errors = [];
        ValidationHelper::pattern($errors, '2024-01-15', ValidationHelper::DATE, 'f', 'F', 'date');

        $this->assertEmpty($errors);
    }
}
