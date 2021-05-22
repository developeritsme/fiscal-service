<?php

namespace DeveloperItsMe\FiscalService\Responses;

class RegisterTCR extends Response
{
    public function toArray(): array
    {
        return [
            'code' => $this->code(),
        ];
    }

    protected function code()
    {
        return $this->domResponse->getElementsByTagName('TCRCode')->item(0)->nodeValue;
    }
}
