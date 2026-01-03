<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Certificate;
use DeveloperItsMe\FiscalService\Exceptions\CertificateException;
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
    public function it_sets_qr_base_url_on_invoice_when_processing()
    {
        $fiscal = $this->fiscal();
        $request = $this->getRegisterInvoiceRequest();

        $fiscal->request($request)->payload();

        $this->assertStringContainsString('efitest.tax.gov.me', $request->model()->url());
    }

    /** @test */
    public function it_throws_certificate_exception_for_invalid_certificate_data()
    {
        $this->expectException(CertificateException::class);
        $this->expectExceptionMessage('Failed to read PKCS12 certificate');

        new Certificate('invalid-certificate-data', 'password');
    }

    /** @test */
    public function it_throws_certificate_exception_for_wrong_passphrase()
    {
        $this->expectException(CertificateException::class);

        new Certificate($this->certPath, 'wrong-passphrase');
    }

    /** @test */
    public function it_provides_openssl_errors_in_certificate_exception()
    {
        try {
            new Certificate('invalid-certificate-data', 'password');
            $this->fail('Expected CertificateException was not thrown');
        } catch (CertificateException $e) {
            $this->assertIsArray($e->getOpensslErrors());
            $this->assertNotEmpty($e->getOpensslErrors());
        }
    }
}
