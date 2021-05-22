<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use DeveloperItsMe\FiscalService\Models\Invoice;
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
    public function it_sets_proper_qr_code_base_url()
    {
        $this->fiscal();
        $this->assertEquals($this->qrTestUrl, Invoice::$qrBaseUrl);

        $this->fiscal(null, false);
        $this->assertEquals($this->qrProductionUrl, Invoice::$qrBaseUrl);
    }

    /** @skip */
    public function it_can_send_tcr_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterTCRRequest())
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }

    /** @skip */
    public function it_can_send_initial_cash_deposit_request()
    {
        $date = Carbon::now('Europe/Podgorica')->format('Y-m-d');

        if ($date == @file_get_contents('./tests/it_can_send_initial_cash_deposit_request.skip')) {
            $this->markTestSkipped('Already sent initial cash deposit request');
        }

        $responseInitial = $this->fiscal()
            ->request($this->getRegisterCashDepositRequest())
            ->send();

        file_put_contents('./tests/it_can_send_initial_cash_deposit_request.skip', $date);

        $this->assertTrue($responseInitial->valid(), $responseInitial->error());
    }

    /** @skip */
    public function it_can_send_withdraw_cash_deposit_request()
    {
        $responseWithdraw = $this->fiscal()
            ->request($this->getRegisterCashDepositRequest(CashDeposit::OPERATION_WITHDRAW))
            ->send();

        $this->assertTrue($responseWithdraw->valid(), $responseWithdraw->error());
    }

    /** @skip */
    public function it_can_send_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest())
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }
}
