<?php

namespace DeveloperItsMe\FiscalService\Requests;

class RegisterInvoice extends Request
{
    /** @var string */
    protected $requestName = 'RegisterInvoiceRequest';


    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        $writer->startElementNs(null, $this->requestName, 'https://efi.tax.gov.me/fs/schema');

        $writer->writeAttribute('xmlns:ns2', 'http://www.w3.org/2000/09/xmldsig#');
        $writer->writeAttribute('Id', 'Request');
        $writer->writeAttribute('Version', '1');

        if ($this->model) {
            $writer->writeRaw($this->model->toXML());
        }

        $writer->endElement();

        return $writer->outputMemory();
    }
}
