<?php

namespace DeveloperItsMe\FiscalService\Responses;

use DeveloperItsMe\FiscalService\Requests\RegisterCashDeposit as RegisterCashDepositRequest;
use DeveloperItsMe\FiscalService\Requests\RegisterInvoice as RegisterInvoiceRequest;
use DeveloperItsMe\FiscalService\Requests\RegisterTCR as RegisterTCRRequest;
use DeveloperItsMe\FiscalService\Requests\Request;

class Factory
{
    protected const REQUEST_RESPONSE_MAP = [
        RegisterCashDepositRequest::class => RegisterCashDeposit::class,
        RegisterInvoiceRequest::class => RegisterInvoice::class,
        RegisterTCRRequest::class => RegisterTCR::class,
    ];

    public static function make($response, $code, Request $request = null, string $connectionError = null): Response
    {
        $requestClass = get_class($request);

        if (!isset(self::REQUEST_RESPONSE_MAP[$requestClass])) {
            throw new \Exception('Unknown response type: ' . $requestClass);
        }

        $class = self::REQUEST_RESPONSE_MAP[$requestClass];

        return new $class($response, $code, $request, $connectionError);
    }
}
