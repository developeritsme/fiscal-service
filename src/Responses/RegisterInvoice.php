<?php

namespace DeveloperItsMe\FiscalService\Responses;

class RegisterInvoice extends Response
{
    public function toArray(): array
    {
        /** @var \DeveloperItsMe\FiscalService\Models\Invoice $model */
        $model = $this->request->model();

        return [
            'url'    => $model->url(),
            'ikof'   => $model->ikof(),
            'jikr'   => $this->fic(),
            'number' => $model->number(),
        ];
    }

    protected function fic(): string
    {
        return $this->domResponse->getElementsByTagName('FIC')->item(0)->nodeValue;
    }
}
