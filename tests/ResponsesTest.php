<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Responses\Factory;
use PHPUnit\Framework\TestCase;

class ResponsesTest extends TestCase
{
    use HasTestData;

    /** @test */
    public function register_invoice_response_returns_proper_data()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $fiscal = $this->mockFiscal();

        $fiscal->method('request')
            ->with($request = $this->getRegisterInvoiceRequest());

        $fiscal->method('send')
            ->willReturn(Factory::make($responseContent, 200, $request));

        $response = $fiscal->send();

        $data = $response->data();

        $this->assertTrue($response->valid());
        $this->assertArrayHasKey('url', $data);
        $this->assertArrayHasKey('ikof', $data);
        $this->assertArrayHasKey('jikr', $data);
        $this->assertArrayHasKey('number', $data);
    }

    /** @test */
    public function register_tcr_response_returns_proper_data()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterTCRResponse.xml');
        $fiscal = $this->mockFiscal();

        $fiscal->method('request')
            ->with($request = $this->getRegisterTCRRequest());

        $fiscal->method('send')
            ->willReturn(Factory::make($responseContent, 200, $request));

        $response = $fiscal->send();

        $data = $response->data();

        $this->assertArrayHasKey('code', $data);
    }

    /** @test */
    public function register_cash_deposit_returns_proper_data()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterCashDepositResponse.xml');
        $fiscal = $this->mockFiscal();

        $fiscal->method('request')
            ->with($request = $this->getRegisterCashDepositRequest());

        $fiscal->method('send')
            ->willReturn(Factory::make($responseContent, 200, $request));

        $response = $fiscal->send();

        $data = $response->data();

        $this->assertArrayHasKey('id', $data);
    }

    /** @test */
    public function it_does_not_resolve_external_entities_in_response()
    {
        $xxeXml = '<?xml version="1.0"?>'
            . '<!DOCTYPE foo [<!ENTITY xxe SYSTEM "file:///etc/hostname">]>'
            . '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<RegisterTCRResponse xmlns="https://efi.tax.gov.me/fs/schema" Id="Response" Version="1">'
            . '<Header UUID="a8323e4a-4ceb-4ef0-8a38-6b315229a1f7" SendDateTime="2021-05-22T14:41:45+02:00"/>'
            . '<TCRCode xmlns="https://efi.tax.gov.me/fs/schema">&xxe;</TCRCode>'
            . '</RegisterTCRResponse>'
            . '</env:Body></env:Envelope>';

        $request = $this->getRegisterTCRRequest();
        $response = Factory::make($xxeXml, 200, $request);
        $data = $response->data();

        $this->assertArrayHasKey('code', $data);
        if (file_exists('/etc/hostname')) {
            $this->assertNotEquals(trim(file_get_contents('/etc/hostname')), $data['code']);
        }
    }

    /** @test */
    public function register_invoice_response_returns_null_fic_when_element_missing()
    {
        $responseContent = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<RegisterInvoiceResponse xmlns="https://efi.tax.gov.me/fs/schema" Id="Response" Version="1">'
            . '<Header UUID="d95ffaec-17b4-4745-b98e-cc7fb3b99385" SendDateTime="2021-05-22T13:32:51+02:00"/>'
            . '</RegisterInvoiceResponse>'
            . '</env:Body></env:Envelope>';

        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($responseContent, 200, $request);
        $data = $response->data();

        $this->assertArrayHasKey('jikr', $data);
        $this->assertNull($data['jikr']);
    }

    /** @test */
    public function register_tcr_response_returns_null_code_when_element_missing()
    {
        $responseContent = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<RegisterTCRResponse xmlns="https://efi.tax.gov.me/fs/schema" Id="Response" Version="1">'
            . '<Header UUID="a8323e4a-4ceb-4ef0-8a38-6b315229a1f7" SendDateTime="2021-05-22T14:41:45+02:00"/>'
            . '</RegisterTCRResponse>'
            . '</env:Body></env:Envelope>';

        $request = $this->getRegisterTCRRequest();
        $response = Factory::make($responseContent, 200, $request);
        $data = $response->data();

        $this->assertArrayHasKey('code', $data);
        $this->assertNull($data['code']);
    }

    /** @test */
    public function register_cash_deposit_returns_null_id_when_element_missing()
    {
        $responseContent = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<RegisterCashDepositResponse xmlns="https://efi.tax.gov.me/fs/schema" Id="Response" Version="1">'
            . '<Header UUID="e6d0a0ab-745e-4ffd-8998-ea87b876ea65" SendDateTime="2021-05-22T15:00:21+02:00"/>'
            . '</RegisterCashDepositResponse>'
            . '</env:Body></env:Envelope>';

        $request = $this->getRegisterCashDepositRequest();
        $response = Factory::make($responseContent, 200, $request);
        $data = $response->data();

        $this->assertArrayHasKey('id', $data);
        $this->assertNull($data['id']);
    }

    /** @test */
    public function verifier_returns_valid_on_signed_response()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($responseContent, 200, $request);

        $this->assertTrue($response->verifier()->valid());
        $this->assertNull($response->verifier()->error());
    }

    /** @test */
    public function verifier_returns_invalid_on_empty_response()
    {
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make('', 0, $request, 'Connection timeout');

        $this->assertFalse($response->verifier()->valid());
        $this->assertSame('Empty response', $response->verifier()->error());
    }

    /** @test */
    public function verifier_returns_invalid_on_tampered_content()
    {
        $xml = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $xml = str_replace(
            '029de09a-3784-4630-b8e4-257e55afbd0b',
            '00000000-0000-0000-0000-000000000000',
            $xml,
        );

        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($xml, 200, $request);

        $this->assertFalse($response->verifier()->valid());
        $this->assertSame('Digest value mismatch', $response->verifier()->error());
    }

    /** @test */
    public function verifier_returns_same_instance()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($responseContent, 200, $request);

        $this->assertSame($response->verifier(), $response->verifier());
    }
}
