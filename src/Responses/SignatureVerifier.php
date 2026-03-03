<?php

namespace DeveloperItsMe\FiscalService\Responses;

use DeveloperItsMe\FiscalService\Exceptions\SignatureException;
use DOMDocument;
use DOMElement;
use DOMXPath;

class SignatureVerifier
{
    protected const DSIG_NS = 'http://www.w3.org/2000/09/xmldsig#';

    protected ?DOMDocument $doc = null;

    protected ?bool $valid = null;

    protected ?string $error = null;

    public function __construct(DOMDocument|string|null $xml)
    {
        if ($xml instanceof DOMDocument) {
            $this->doc = $xml;
        } elseif (is_string($xml) && $xml !== '') {
            $doc = new DOMDocument();
            $this->doc = $doc->loadXML($xml, LIBXML_NONET) ? $doc : null;
        }
    }

    public function valid(): bool
    {
        if ($this->valid === null) {
            $this->verify();
        }

        return $this->valid;
    }

    public function error(): ?string
    {
        if ($this->valid === null) {
            $this->verify();
        }

        return $this->error;
    }

    protected function verify(): void
    {
        if (!$this->doc) {
            $this->valid = false;
            $this->error = 'Empty response';

            return;
        }

        try {
            $clean = $this->parseClean();
            $responseElement = $this->findResponseElement($clean);
            $signatureElement = $this->findSignatureElement($responseElement);

            $this->verifyDigest($responseElement, $signatureElement);
            $this->verifySignatureValue($signatureElement);

            $this->valid = true;
            $this->error = null;
        } catch (SignatureException $e) {
            $this->valid = false;
            $this->error = $e->getReason();
        }
    }

    /**
     * @throws SignatureException
     */
    protected function parseClean(): DOMDocument
    {
        $clean = new DOMDocument();
        $clean->preserveWhiteSpace = false;

        $xml = $this->doc->saveXML();
        if (!$xml || !$clean->loadXML($xml, LIBXML_NONET)) {
            throw new SignatureException('Failed to parse response XML');
        }

        return $clean;
    }

    /**
     * @throws SignatureException
     */
    protected function findResponseElement(DOMDocument $doc): DOMElement
    {
        $xpath = new DOMXPath($doc);
        $nodes = $xpath->query('//*[@Id="Response"]');

        if ($nodes->length === 0) {
            throw new SignatureException('Element with Id="Response" not found');
        }

        return $nodes->item(0);
    }

    /**
     * @throws SignatureException
     */
    protected function findSignatureElement(DOMElement $responseElement): DOMElement
    {
        $signatures = $responseElement->getElementsByTagNameNS(self::DSIG_NS, 'Signature');

        if ($signatures->length === 0) {
            throw new SignatureException('Signature element not found');
        }

        return $signatures->item(0);
    }

    /**
     * @throws SignatureException
     */
    protected function verifyDigest(DOMElement $responseElement, DOMElement $signatureElement): void
    {
        $expectedDigest = $this->getElementValue($signatureElement, 'DigestValue');

        if ($expectedDigest === null) {
            throw new SignatureException('DigestValue element not found');
        }

        // Clone the response element into a standalone document for canonicalization
        $canonDoc = new DOMDocument('1.0', 'UTF-8');
        $cloned = $canonDoc->importNode($responseElement, true);
        $canonDoc->appendChild($cloned);

        // Remove the Signature element (enveloped-signature transform)
        $sigs = $cloned->getElementsByTagNameNS(self::DSIG_NS, 'Signature');
        if ($sigs->length > 0) {
            $cloned->removeChild($sigs->item(0));
        }

        // Exclusive C14N and SHA-256 digest
        $canonXml = $canonDoc->documentElement->C14N(true);
        $computedDigest = base64_encode(hash('sha256', $canonXml, true));

        if ($computedDigest !== $expectedDigest) {
            throw new SignatureException('Digest value mismatch');
        }
    }

    /**
     * @throws SignatureException
     */
    protected function verifySignatureValue(DOMElement $signatureElement): void
    {
        $signatureValue = $this->getElementValue($signatureElement, 'SignatureValue');

        if ($signatureValue === null) {
            throw new SignatureException('SignatureValue element not found');
        }

        $certBase64 = $this->getElementValue($signatureElement, 'X509Certificate');

        if ($certBase64 === null) {
            throw new SignatureException('X509Certificate element not found');
        }

        // Reconstruct PEM certificate
        $pem = "-----BEGIN CERTIFICATE-----\n"
            . chunk_split(preg_replace('/\s+/', '', $certBase64), 64, "\n")
            . '-----END CERTIFICATE-----';

        $publicKey = openssl_pkey_get_public($pem);

        if ($publicKey === false) {
            throw new SignatureException('Failed to extract public key from X509Certificate');
        }

        // Exclusive C14N on SignedInfo
        $signedInfoNodes = $signatureElement->getElementsByTagNameNS(self::DSIG_NS, 'SignedInfo');

        if ($signedInfoNodes->length === 0) {
            throw new SignatureException('SignedInfo element not found');
        }

        $signedInfoCanon = $signedInfoNodes->item(0)->C14N(true);
        $decodedSignature = base64_decode(preg_replace('/\s+/', '', $signatureValue));

        $result = openssl_verify($signedInfoCanon, $decodedSignature, $publicKey, OPENSSL_ALGO_SHA256);

        if ($result === 0) {
            throw new SignatureException('Signature value is invalid');
        }

        if ($result === -1) {
            throw new SignatureException('Signature verification error: ' . openssl_error_string());
        }
    }

    protected function getElementValue(DOMElement $parent, string $localName): ?string
    {
        $nodes = $parent->getElementsByTagNameNS(self::DSIG_NS, $localName);

        if ($nodes->length === 0) {
            return null;
        }

        return $nodes->item(0)->nodeValue;
    }
}
