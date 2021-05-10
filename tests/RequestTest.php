<?php

namespace Tests;

use DeveloperItsMe\FiscalService\Requests\Request;
use PHPUnit\Framework\TestCase;

class RequestTest extends TestCase
{
    /** @test */
    public function it_envelopes_request()
    {
        $envelopeStart = '<?xml version="1.0" encoding="UTF-8"?>
<SOAP-ENV:Envelope xmlns:SOAP-ENV="http://schemas.xmlsoap.org/soap/envelope/">
    <SOAP-ENV:Header/>
    <SOAP-ENV:Body>';

        $envelopeEnd = '</SOAP-ENV:Body>
</SOAP-ENV:Envelope>
';

        $request = new TestEnvelope();
        $enveloped = $request->envelope();
        $full = $envelopeStart . $xml = $request->toXml() . $envelopeEnd;

        $this->assertStringStartsWith($envelopeStart, $enveloped);
        $this->assertStringContainsString($xml, $enveloped);
        $this->assertStringEndsWith($envelopeEnd, $enveloped);
        $this->assertXmlStringEqualsXmlString($full, $enveloped);
    }
}

class TestEnvelope extends Request
{

    public function toXML(): string
    {
        return '<test/>';
    }
}
