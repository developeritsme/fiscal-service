<?php

namespace DeveloperItsMe\FiscalService\Requests;

use DeveloperItsMe\FiscalService\Models\Model;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;
use DOMDocument;

abstract class Request
{
    use HasXmlWriter;

    /** @var string */
    protected $requestName;

    /** @var \DeveloperItsMe\FiscalService\Requests\Request */
    protected $model;

    /** @var string */
    protected $payload;

    /** @var int CURLOPT_CONNECTTIMEOUT */
    protected $curl_connect_timeout = 10;

    /** @var int CURLOPT_TIMEOUT */
    protected $curl_timeout = 30;

    public function __construct(Model $model = null)
    {
        $this->model = $model;
    }

    public function model(): Model
    {
        return $this->model;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();

        $writer->startElementNs(null, $this->requestName, 'https://efi.tax.gov.me/fs/schema');

        $writer->writeAttribute('Id', 'Request');
        $writer->writeAttribute('Version', '1');

        if ($this->model) {
            $writer->writeRaw(PHP_EOL . $this->model->toXML());
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    public function setPayload($payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    public function envelope($xml = null)
    {
        $xmlRequestDom = new DOMDocument();
        $xmlRequestDom->loadXML($xml ?? $this->toXML());

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

    public function timeout($seconds = null): int
    {
        if (intval($seconds) > 0) {
            $this->curl_timeout = $seconds;
        }

        return $this->curl_timeout;
    }

    public function connect_timeout($seconds = null): int
    {
        if (intval($seconds) > 0) {
            $this->curl_connect_timeout = $seconds;
        }

        return $this->curl_connect_timeout;
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
}
