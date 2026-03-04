<?php

namespace Tests\Models;

use DeveloperItsMe\FiscalService\Models\Countries;
use PHPUnit\Framework\TestCase;

class CountriesTest extends TestCase
{
    /** @test */
    public function all_returns_full_array()
    {
        $all = Countries::all();

        $this->assertNotEmpty($all);
        $this->assertArrayHasKey('MNE', $all);
        $this->assertSame('Montenegro', $all['MNE']);
    }

    /** @test */
    public function codes_returns_array_of_codes()
    {
        $codes = Countries::codes();

        $this->assertContains('MNE', $codes);
        $this->assertContains('USA', $codes);
        $this->assertContainsOnly('string', $codes);
    }

    /** @test */
    public function names_returns_array_of_names()
    {
        $names = Countries::names();

        $this->assertContains('Montenegro', $names);
        $this->assertCount(count(Countries::all()), $names);
    }

    /** @test */
    public function me_constant_equals_mne()
    {
        $this->assertSame('MNE', Countries::ME);
    }
}
