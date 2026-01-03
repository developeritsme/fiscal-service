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

    /** @test */
    public function it_returns_expiration_date()
    {
        $cert = new Certificate($this->certPath, $this->certPassphrase);

        $expiresAt = $cert->expiresAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expiresAt);
        $this->assertEquals('2023-10-05', $expiresAt->format('Y-m-d'));
    }
}
