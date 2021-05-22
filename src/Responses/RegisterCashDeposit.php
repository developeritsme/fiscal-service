<?php

namespace DeveloperItsMe\FiscalService\Responses;

class RegisterCashDeposit extends Response
{
    public function toArray(): array
    {
        return [
            'id' => $this->fcdc(),
        ];
    }

    protected function fcdc()
    {
        return $this->domResponse->getElementsByTagName('FCDC')->item(0)->nodeValue;
    }
}
