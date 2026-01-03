<?php

namespace Tests;

use DOMDocument;
use PHPUnit\Framework\TestCase;

class XsdSchemaTest extends TestCase
{
    use HasTestData;

    /** @test */
    public function register_tcr_request_xml_is_valid_service_schema()
    {
        $xml = new DOMDocument();
        $xml->loadXML(
            $this->fiscal()->request($this->getRegisterTCRRequest())->payload()
        );

        $this->assertTrue(
            $xml->schemaValidate('./tests/schema/eficg-fiscalization-service.xsd')
        );
    }

    /** @test */
    public function register_cash_deposit_request_xml_is_valid_service_schema()
    {
        $xml = new DOMDocument();
        $xml->loadXML(
            $this->fiscal()->request($this->getRegisterCashDepositRequest())->payload()
        );

        $this->assertTrue(
            $xml->schemaValidate('./tests/schema/eficg-fiscalization-service.xsd')
        );
    }

    /** @test */
    public function register_invoice_request_xml_is_valid_service_schema()
    {
        $xml = new DOMDocument();
        $xml->loadXML(
            $this->fiscal()->request($this->getRegisterInvoiceRequest())->payload()
        );

        $this->assertTrue(
            $xml->schemaValidate('./tests/schema/eficg-fiscalization-service.xsd')
        );
    }
}
