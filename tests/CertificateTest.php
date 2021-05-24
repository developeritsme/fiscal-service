<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Certificate;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    use HasTestData;

    /** @test */
    public function it_reads_certificate()
    {
        $cert = new Certificate($this->certPath, $this->certPassphrase);

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }
}
