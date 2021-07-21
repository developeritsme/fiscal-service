<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use PHPUnit\Framework\TestCase;

class FiscalServiceTest extends TestCase
{
    use HasTestData;

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
    public function it_can_send_cash_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest())
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }

    /** @test */
    public function it_can_send_no_cash_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest(true))
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }
}
