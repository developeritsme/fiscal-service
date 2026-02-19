<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Certificate;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    /** @test */
    public function it_reads_certificate()
    {
        $cert = Certificate::fromFile('./CoreitPecatSoft.pfx', '123456');

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_reads_certificate_from_content()
    {
        $content = file_get_contents('./CoreitPecatSoft.pfx');
        $cert = Certificate::fromContent($content, '123456');

        $this->assertNotFalse($cert->getPrivateKey());
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_returns_expiration_date()
    {
        $cert = Certificate::fromFile('./CoreitPecatSoft.pfx', '123456');

        $expiresAt = $cert->expiresAt();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expiresAt);
        $this->assertEquals('2023-10-05', $expiresAt->format('Y-m-d'));
    }
}
