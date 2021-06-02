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

    protected function fcdc(): ?string
    {
        return $this->domResponse
            ? $this->domResponse->getElementsByTagName('FCDC')->item(0)->nodeValue
            : null;
    }
}
