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

    protected function code(): ?string
    {
        $element = $this->domResponse
            ? $this->domResponse->getElementsByTagName('TCRCode')->item(0)
            : null;

        return $element ? $element->nodeValue : null;
    }
}
