<?php

namespace Tests;

use Carbon\Carbon;
use DeveloperItsMe\FiscalService\Models\CashDeposit;
use DeveloperItsMe\FiscalService\Models\CorrectiveInvoice;
use PHPUnit\Framework\TestCase;

class FiscalServiceNoVatTest extends TestCase
{
    //    use HasNoVatTestData;

    /** @test */
    public function example()
    {
        $this->assertTrue(true);
    }

    /** @skip */
    public function it_can_send_no_vat_tcr_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterTCRRequest())
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }

    /** @skip */
    public function it_can_send_no_vat_initial_cash_deposit_request()
    {
        $date = Carbon::now('Europe/Podgorica')->format('Y-m-d');

        $enuFile = './tests/it_can_send_no_vat_initial_cash_deposit_request' . $this->enu . '.skip';
        if ($date == @file_get_contents($enuFile)) {
            $this->markTestSkipped('Already sent initial cash deposit request');
        }

        $responseInitial = $this->fiscal()
            ->request($this->getRegisterCashDepositRequest())
            ->send();

        file_put_contents($enuFile, $date);

        $this->assertTrue($responseInitial->valid(), $responseInitial->error());
    }

    /** @skip */
    public function it_can_send_no_vat_withdraw_cash_deposit_request()
    {
        $responseWithdraw = $this->fiscal()
            ->request($this->getRegisterCashDepositRequest(CashDeposit::OPERATION_WITHDRAW))
            ->send();

        $this->assertTrue($responseWithdraw->valid(), $responseWithdraw->error());
    }

    /** @skip */
    public function it_can_send_no_vat_cash_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest())
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }

    /** @skip */
    public function it_can_send_no_vat_no_cash_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest(true))
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }

    /** @skip */
    public function it_can_send_no_vat_corrective_invoice_full_amount()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest(true))
            ->send();

        $this->assertTrue($response->valid(), $response->error());
        $data = $response->data();

        $corrective = new CorrectiveInvoice($data['ikof'], $data['date']);
        $cResponse = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest(true, $corrective))
            ->send();

        $this->assertTrue($cResponse->valid(), $cResponse->error());
    }

    /** @skip */
    public function it_can_send_no_vat_items_with_4_decimals_invoice_request()
    {
        $response = $this->fiscal()
            ->request($this->getRegisterInvoiceRequest(true, null, 4))
            ->send();

        $this->assertTrue($response->valid(), $response->error());
    }
}
