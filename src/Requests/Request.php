<?php

namespace DeveloperItsMe\FiscalService\Requests;

use DeveloperItsMe\FiscalService\Exceptions\FiscalException;
use DeveloperItsMe\FiscalService\Models\Model;
use DeveloperItsMe\FiscalService\Traits\HasXmlWriter;
use DOMDocument;

abstract class Request
{
    use HasXmlWriter;

    protected const SCHEMA_NAMESPACE = 'https://efi.tax.gov.me/fs/schema';
    protected const SOAP_NAMESPACE = 'http://schemas.xmlsoap.org/soap/envelope/';
    protected const DEFAULT_CONNECT_TIMEOUT = 10;
    protected const DEFAULT_TIMEOUT = 30;

    protected string $requestName;

    protected ?Model $model;

    protected ?string $payload = null;

    protected int $curl_connect_timeout = self::DEFAULT_CONNECT_TIMEOUT;

    protected int $curl_timeout = self::DEFAULT_TIMEOUT;

    protected bool $curl_verify_peer = true;

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

        $writer->startElementNs(null, $this->requestName, self::SCHEMA_NAMESPACE);

        $writer->writeAttribute('Id', 'Request');
        $writer->writeAttribute('Version', '1');

        if ($this->model) {
            $writer->writeRaw(PHP_EOL . $this->model->toXML());
        }

        $writer->endElement();

        return $writer->outputMemory();
    }

    public function setPayload(string $payload): self
    {
        $this->payload = $payload;

        return $this;
    }

    public function payload(): string
    {
        return $this->payload;
    }

    /** @throws FiscalException */
    public function envelope(?string $xml = null): string
    {
        $xmlRequestDom = new DOMDocument();
        if (!$xmlRequestDom->loadXML($xml ?? $this->toXML(), LIBXML_NONET)) {
            throw new FiscalException('Failed to parse request XML');
        }

        $envelope = new DOMDocument();
        if (!$envelope->loadXML($this->getEnvelopeXml(), LIBXML_NONET)) {
            throw new FiscalException('Failed to parse SOAP envelope XML');
        }
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

    public function timeout(?int $seconds = null): int
    {
        if ($seconds !== null && $seconds > 0) {
            $this->curl_timeout = $seconds;
        }

        return $this->curl_timeout;
    }

    public function connect_timeout(?int $seconds = null): int
    {
        if ($seconds !== null && $seconds > 0) {
            $this->curl_connect_timeout = $seconds;
        }

        return $this->curl_connect_timeout;
    }

    public function verifySslPeer(?bool $verify = null): bool
    {
        if (!is_null($verify)) {
            $this->curl_verify_peer = $verify;
        }

        return $this->curl_verify_peer;
    }

    protected function getEnvelopeXml(): string
    {
        $writer = $this->getXmlWriter();
        $ns = 'SOAP-ENV';

        $writer->startElementNs($ns, 'Envelope', self::SOAP_NAMESPACE);

        $writer->startElementNs($ns, 'Header', null);
        $writer->endElement();

        $writer->startElementNs($ns, 'Body', null);
        $writer->endElement();

        $writer->endElement();

        return $writer->outputMemory();
    }
}
