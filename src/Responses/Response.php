<?php

namespace DeveloperItsMe\FiscalService\Responses;

use DeveloperItsMe\FiscalService\Requests\Request;
use DOMDocument;

abstract class Response
{
    /** @var string */
    protected $response;

    /** @var int */
    protected $code;

    /** @var Request */
    protected $request;

    /** @var DOMDocument */
    protected $domResponse;

    /** @var string */
    protected $connectionError;

    public function __construct($response, $code, Request $request = null, string $connectionError = null)
    {
        $this->response = $response;
        $this->code = $code;
        $this->request = $request;
        $this->connectionError = $connectionError;

        $this->setDomResponse();
    }

    public function valid(): bool
    {
        return $this->code === 200;
    }

    public function failed(): bool
    {
        return !$this->valid();
    }

    public function success(): bool
    {
        return $this->valid();
    }

    public function ok(): bool
    {
        return $this->valid();
    }

    public function body(): string
    {
        return $this->response;
    }

    public function request(): string
    {
        return $this->request->payload();
    }

    public function data(): array
    {
        return $this->valid() ? $this->toArray() : $this->errors();
    }

    public function error(): string
    {
        if ($this->domResponse) {
            $faultString = $this->domResponse->getElementsByTagName('faultstring')->item(0);

            return $faultString ? $faultString->nodeValue : 'Success';
        }

        return $this->connectionError ?: 'Empty response';
    }

    public function errors(): array
    {
        if ($this->domResponse) {
            $faultCode = $this->domResponse->getElementsByTagName('faultcode')->item(0);
            $faultString = $this->domResponse->getElementsByTagName('faultstring')->item(0);
            $details = $this->domResponse->getElementsByTagName('detail')->item(0);

            $errors = [
                'code'    => $faultCode ? $faultCode->nodeValue : 0,
                'message' => $faultString ? $faultString->nodeValue : 'Success',
            ];

            if ($details) {
                $errors['details'] = [];
                /** @var \DOMNode $detail */
                foreach ($details->childNodes as $detail) {
                    $errors['details'][] = [$detail->nodeName => $detail->nodeValue];
                }
            }

            return $errors;
        }

        return ['code' => 0, 'message' => $this->error()];
    }

    protected function setDomResponse(): void
    {
        if ($this->response) {
            $dom = new DOMDocument();

            $this->domResponse = $dom->loadXML($this->response, LIBXML_NONET) ? $dom : null;
        }
    }

    abstract public function toArray(): array;
}
