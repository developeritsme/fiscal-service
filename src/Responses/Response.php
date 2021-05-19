<?php

namespace DeveloperItsMe\FiscalService\Responses;

class Response
{
    /** @var string */
    protected $response;

    /** @var int */
    protected $code;

    public function __construct($response, $code)
    {
        $this->response = $response;
        $this->code = $code;
    }

    public function valid(): bool
    {
        return $this->code === 200;
    }

    public function body()
    {
        return $this->response;
    }

}
