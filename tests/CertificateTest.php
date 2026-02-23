<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Certificate;
use DeveloperItsMe\FiscalService\Exceptions\FiscalException;
use PHPUnit\Framework\TestCase;

class CertificateTest extends TestCase
{
    /** @test */
    public function it_reads_certificate()
    {
        $cert = Certificate::fromFile('./CoreitPecatSoft.pfx', '123456');

        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $this->getPrivateKeyViaReflection($cert));
        $this->assertNotFalse($cert->getPublicData());
    }

    /** @test */
    public function it_reads_certificate_from_content()
    {
        $content = file_get_contents('./CoreitPecatSoft.pfx');
        $cert = Certificate::fromContent($content, '123456');

        $this->assertInstanceOf(\OpenSSLAsymmetricKey::class, $this->getPrivateKeyViaReflection($cert));
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

    /** @test */
    public function it_signs_data()
    {
        $cert = Certificate::fromFile('./CoreitPecatSoft.pfx', '123456');

        $signature = $cert->sign('test data');

        $this->assertNotEmpty($signature);
    }

    /** @test */
    public function it_throws_exception_when_signing_fails()
    {
        $this->expectException(FiscalException::class);
        $this->expectExceptionMessage('Unable to sign data');

        $cert = Certificate::fromFile('./CoreitPecatSoft.pfx', '123456');

        $ref = new \ReflectionProperty($cert, 'privateKeyResource');
        $ref->setAccessible(true);
        $ref->setValue($cert, false);

        set_error_handler(function () {
            return true;
        });
        try {
            $cert->sign('test data');
        } finally {
            restore_error_handler();
        }
    }

    private function getPrivateKeyViaReflection(Certificate $cert): mixed
    {
        $ref = new \ReflectionProperty($cert, 'privateKeyResource');
        $ref->setAccessible(true);

        return $ref->getValue($cert);
    }
}
