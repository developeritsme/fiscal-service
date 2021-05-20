<?php

namespace DeveloperItsMe\FiscalService\Responses;

use DeveloperItsMe\FiscalService\Requests\RegisterCashDeposit as RegisterCashDepositRequest;
use DeveloperItsMe\FiscalService\Requests\RegisterInvoice as RegisterInvoiceRequest;
use DeveloperItsMe\FiscalService\Requests\RegisterTCR as RegisterTCRRequest;
use DeveloperItsMe\FiscalService\Requests\Request;

class Factory
{
    public static function make($response, $code, Request $request = null): Response
    {
        switch (true) {
            case $request instanceof RegisterCashDepositRequest:
                $class = RegisterCashDeposit::class;
                break;

            case $request instanceof RegisterInvoiceRequest:
                $class = RegisterInvoice::class;
                break;

            case $request instanceof RegisterTCRRequest:
                $class = RegisterTCR::class;
                break;

            default:
                $class = 'Test';

        }

        return new $class($response, $code, $request);
    }
}
