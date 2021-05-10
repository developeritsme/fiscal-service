<?php


namespace DeveloperItsMe\FiscalService\Requests;


use DOMDocument;
use XMLWriter;

abstract class Request
{
    /** @var string */
    protected $requestName = 'RegisterInvoiceRequest';

    /** @var \DeveloperItsMe\FiscalService\Requests\Request */
    protected $request;

    public function __construct(Request $request = null)
    {
        $this->request = $request;
    }

    public function generateUUID(): string
    {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public function envelope()
    {
        $xmlRequestDom = new DOMDocument();
        $xmlRequestDom->loadXML($this->toXML());

        $envelope = new DOMDocument();
        $envelope->loadXML($this->getEnvelopeXml());
        $envelope->xmlVersion = '1.0';
        $envelope->encoding = 'UTF-8';

        $xmlRequestType = $xmlRequestDom->documentElement->localName;

        $XMLRequestTypeNode = $xmlRequestDom->getElementsByTagName($xmlRequestType)->item(0);
        $XMLRequestTypeNode = $envelope->importNode($XMLRequestTypeNode, true);

        $envelope->getElementsByTagName('Body')
            ->item(0)
            ->appendChild($XMLRequestTypeNode);

        return $envelope->saveXML();
    }

    protected function getXmlWriter($indent = true, $indentString = '    '): XMLWriter
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent($indent);
        $writer->setIndentString($indentString);

        return $writer;
    }

    protected function getEnvelopeXml(): string
    {
        $writer = $this->getXmlWriter();
        $ns = 'SOAP-ENV';

        $writer->startElementNs($ns, 'Envelope', 'http://schemas.xmlsoap.org/soap/envelope/');

        $writer->startElementNs($ns, 'Header', null);
        $writer->endElement();

        $writer->startElementNs($ns, 'Body', null);
        $writer->endElement();

        $writer->endElement();

        return $writer->outputMemory();
    }

    abstract public function toXML(): string;

}
