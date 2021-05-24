<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Models\Invoice;
use PHPUnit\Framework\TestCase;

class FiscalTest extends TestCase
{
    use HasTestData;

    /** @test */
    public function it_sets_certificate_from_file()
    {
        $fiscal = $this->fiscal();
        $cert = $fiscal->certificate();

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_sets_certificate_from_content()
    {
        $content = file_get_contents($this->certPath);
        $fiscal = $this->fiscal($content);
        $cert = $fiscal->certificate();

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_sets_proper_qr_code_base_url()
    {
        $this->fiscal();
        $this->assertEquals($this->qrTestUrl, Invoice::$qrBaseUrl);

        $this->fiscal(null, false);
        $this->assertEquals($this->qrProductionUrl, Invoice::$qrBaseUrl);
    }
}
