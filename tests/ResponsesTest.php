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

    /** @test */
    public function failed_returns_true_for_non_200_code()
    {
        $faultXml = $this->soapFaultXml('env:Server', 'Internal error');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($faultXml, 500, $request);

        $this->assertTrue($response->failed());
        $this->assertFalse($response->valid());
        $this->assertFalse($response->ok());
        $this->assertFalse($response->success());
    }

    /** @test */
    public function error_returns_faultstring_from_soap_fault()
    {
        $faultXml = $this->soapFaultXml('env:Server', 'Invoice validation failed');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($faultXml, 500, $request);

        $this->assertSame('Invoice validation failed', $response->error());
    }

    /** @test */
    public function errors_returns_structured_fault_data_with_details()
    {
        $faultXml = '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<env:Fault>'
            . '<faultcode>env:Client</faultcode>'
            . '<faultstring>Validation error</faultstring>'
            . '<detail><ErrorCode>100</ErrorCode><Message>Invalid TIN</Message></detail>'
            . '</env:Fault>'
            . '</env:Body></env:Envelope>';

        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($faultXml, 500, $request);

        $errors = $response->errors();

        $this->assertSame('env:Client', $errors['code']);
        $this->assertSame('Validation error', $errors['message']);
        $this->assertArrayHasKey('details', $errors);
        $this->assertNotEmpty($errors['details']);
    }

    /** @test */
    public function errors_without_detail_element_has_no_details_key()
    {
        $faultXml = $this->soapFaultXml('env:Server', 'Server error');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($faultXml, 500, $request);

        $errors = $response->errors();

        $this->assertSame('env:Server', $errors['code']);
        $this->assertSame('Server error', $errors['message']);
        $this->assertArrayNotHasKey('details', $errors);
    }

    /** @test */
    public function data_returns_errors_when_failed()
    {
        $faultXml = $this->soapFaultXml('env:Server', 'Some fault');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($faultXml, 500, $request);

        $data = $response->data();

        $this->assertArrayHasKey('code', $data);
        $this->assertArrayHasKey('message', $data);
        $this->assertSame('Some fault', $data['message']);
    }

    /** @test */
    public function error_returns_connection_error_when_no_xml()
    {
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make(false, 0, $request, 'Connection timeout');

        $this->assertSame('Connection timeout', $response->error());
    }

    /** @test */
    public function error_returns_empty_response_when_no_xml_and_no_connection_error()
    {
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make(false, 0, $request);

        $this->assertSame('Empty response', $response->error());
    }

    /** @test */
    public function body_returns_raw_response()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $request = $this->getRegisterInvoiceRequest();
        $response = Factory::make($responseContent, 200, $request);

        $this->assertSame($responseContent, $response->body());
    }

    /** @test */
    public function request_returns_request_payload()
    {
        $responseContent = file_get_contents('./tests/xml/RegisterInvoiceResponse.xml');
        $request = $this->getRegisterInvoiceRequest();
        $request->setPayload('<soap>signed request</soap>');
        $response = Factory::make($responseContent, 200, $request);

        $this->assertSame('<soap>signed request</soap>', $response->request());
    }

    private function soapFaultXml(string $faultCode, string $faultString): string
    {
        return '<env:Envelope xmlns:env="http://schemas.xmlsoap.org/soap/envelope/">'
            . '<env:Header/><env:Body>'
            . '<env:Fault>'
            . '<faultcode>' . $faultCode . '</faultcode>'
            . '<faultstring>' . $faultString . '</faultstring>'
            . '</env:Fault>'
            . '</env:Body></env:Envelope>';
    }
}
