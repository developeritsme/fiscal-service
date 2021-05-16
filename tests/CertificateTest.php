<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Certificate;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    /** @test */
    public function it_reads_certificate()
    {
        $cert = new Certificate('./CoreitPotpisSoft.pfx', '123456');

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }
}
