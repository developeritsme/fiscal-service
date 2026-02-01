<?php

namespace Tests\Responses;

use DeveloperItsMe\FiscalService\Exceptions\SignatureException;
use DeveloperItsMe\FiscalService\Responses\SignatureVerifier;
use DOMDocument;
use PHPUnit\Framework\TestCase;

class SignatureVerifierTest extends TestCase
{
    /** @test */
    public function it_verifies_register_invoice_response_signature()
    {
        $verifier = new SignatureVerifier($this->loadFixture('./tests/xml/RegisterInvoiceResponse.xml'));

        $this->assertTrue($verifier->valid());
        $this->assertNull($verifier->error());
    }

    /** @test */
    public function it_verifies_register_tcr_response_signature()
    {
        $verifier = new SignatureVerifier($this->loadFixture('./tests/xml/RegisterTCRResponse.xml'));

        $this->assertTrue($verifier->valid());
    }

    /** @test */
    public function it_verifies_register_cash_deposit_response_signature()
    {
        $verifier = new SignatureVerifier($this->loadFixture('./tests/xml/RegisterCashDepositResponse.xml'));

        $this->assertTrue($verifier->valid());
    }

    /** @test */
    public function it_fails_on_tampered_content()
    {
        $xml = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $xml = str_replace(
            '029de09a-3784-4630-b8e4-257e55afbd0b',
            '00000000-0000-0000-0000-000000000000',
            $xml,
        );

        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NONET);

        $verifier = new SignatureVerifier($doc);

        $this->assertFalse($verifier->valid());
        $this->assertSame('Digest value mismatch', $verifier->error());
    }

    /** @test */
    public function it_fails_when_signature_element_is_missing()
    {
        $xml = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<RegisterInvoiceResponse xmlns="https://efi.tax.gov.me/fs/schema" Id="Response" Version="1">'
            . '<Header UUID="d95ffaec-17b4-4745-b98e-cc7fb3b99385" SendDateTime="2021-05-22T13:32:51+02:00"/>'
            . '<FIC>029de09a-3784-4630-b8e4-257e55afbd0b</FIC>'
            . '</RegisterInvoiceResponse>'
            . '</env:Body></env:Envelope>';

        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NONET);

        $verifier = new SignatureVerifier($doc);

        $this->assertFalse($verifier->valid());
        $this->assertSame('Signature element not found', $verifier->error());
    }

    /** @test */
    public function it_fails_when_response_element_is_missing()
    {
        $xml = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<SomeElement xmlns="https://efi.tax.gov.me/fs/schema">content</SomeElement>'
            . '</env:Body></env:Envelope>';

        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NONET);

        $verifier = new SignatureVerifier($doc);

        $this->assertFalse($verifier->valid());
        $this->assertSame('Element with Id="Response" not found', $verifier->error());
    }

    /** @test */
    public function it_fails_on_tampered_signature_value()
    {
        $xml = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $xml = str_replace('Dx3fZSDCB6tx', 'AAAAAAAAAAAA', $xml);

        $doc = new DOMDocument();
        $doc->loadXML($xml, LIBXML_NONET);

        $verifier = new SignatureVerifier($doc);

        $this->assertFalse($verifier->valid());
        $this->assertSame('Signature value is invalid', $verifier->error());
    }

    /** @test */
    public function it_accepts_xml_string()
    {
        $xml = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $verifier = new SignatureVerifier($xml);

        $this->assertTrue($verifier->valid());
    }

    /** @test */
    public function it_fails_on_null()
    {
        $verifier = new SignatureVerifier(null);

        $this->assertFalse($verifier->valid());
        $this->assertSame('Empty response', $verifier->error());
    }

    /** @test */
    public function it_fails_on_empty_string()
    {
        $verifier = new SignatureVerifier('');

        $this->assertFalse($verifier->valid());
        $this->assertSame('Empty response', $verifier->error());
    }

    /** @test */
    public function result_is_cached()
    {
        $verifier = new SignatureVerifier($this->loadFixture('./tests/xml/RegisterInvoiceResponse.xml'));

        $this->assertTrue($verifier->valid());
        $this->assertTrue($verifier->valid());
        $this->assertNull($verifier->error());
    }

    /** @test */
    public function signature_exception_carries_reason()
    {
        $exception = new SignatureException('test reason');

        $this->assertSame('test reason', $exception->getReason());
        $this->assertStringContainsString('test reason', $exception->getMessage());
    }

    private function loadFixture(string $path): DOMDocument
    {
        $doc = new DOMDocument();
        $doc->load($path, LIBXML_NONET);

        return $doc;
    }
}
