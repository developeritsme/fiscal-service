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
}
