<?php

namespace DeveloperItsMe\FiscalService;

use DeveloperItsMe\FiscalService\Requests\RegisterInvoice;
use DeveloperItsMe\FiscalService\Requests\Request;
use DeveloperItsMe\FiscalService\Responses\Factory;
use DeveloperItsMe\FiscalService\Responses\Response;
use DOMDocument;
use DOMElement;

class Fiscal
{
    private $url = 'https://efi.tax.gov.me/fs-v1';

    /** @var \DeveloperItsMe\FiscalService\Certificate */
    protected $certificate;

    /** @var Request */
    protected $request;

    public function __construct($certificatePath, $certificatePassphrase, $test = false)
    {
        if ($test) {
            $this->url = 'https://efitest.tax.gov.me/fs-v1';
        }
        $this->certificate = new Certificate($certificatePath, $certificatePassphrase);
    }

    public function certificate(): Certificate
    {
        return $this->certificate;
    }

    public function request(Request $request): self
    {
        $this->request = $request;

        return $this;
    }

    public function send(): Response
    {
        if ($this->request) {
            if (method_exists($model = $this->request->model(), 'generateIIC')) {
                $model->generateIIC($this->certificate()->getPrivateKey());
            }
            $payload = $this->request->envelope(
                $this->sign($this->request->toXML())
            );

            return $this->soap($payload);
        }
    }

    protected function sign($xml)
    {
        $XMLRequestDOMDoc = new DOMDocument();
        $XMLRequestDOMDoc->loadXML($xml);

        $digestValue = base64_encode(hash('sha256', $XMLRequestDOMDoc->C14N(), true));

        $rootElem = $XMLRequestDOMDoc->documentElement;
        $rootElem->removeAttributeNS('ns2', '');

        $SignatureNode = $rootElem->appendChild(
            new DOMElement('Signature')
        );
        $SignatureNode->setAttribute('xmlns', 'http://www.w3.org/2000/09/xmldsig#');

        $SignedInfoNode = $SignatureNode->appendChild(new DOMElement('SignedInfo', null, ''));

        $CanonicalizationMethodNode = $SignedInfoNode->appendChild(new DOMElement('CanonicalizationMethod'));
        $CanonicalizationMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $SignatureMethodNode = $SignedInfoNode->appendChild(new DOMElement('SignatureMethod'));
        $SignatureMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmldsig-more#rsa-sha256');

        $ReferenceNode = $SignedInfoNode->appendChild(new DOMElement('Reference'));
        $ReferenceNode->setAttribute('URI', sprintf('#%s', $XMLRequestDOMDoc->documentElement->getAttribute('Id')));

        $TransformsNode = $ReferenceNode->appendChild(new DOMElement('Transforms'));

        $Transform1Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform1Node->setAttribute('Algorithm', 'http://www.w3.org/2000/09/xmldsig#enveloped-signature');

        $Transform2Node = $TransformsNode->appendChild(new DOMElement('Transform'));
        $Transform2Node->setAttribute('Algorithm', 'http://www.w3.org/2001/10/xml-exc-c14n#');

        $DigestMethodNode = $ReferenceNode->appendChild(new DOMElement('DigestMethod'));
        $DigestMethodNode->setAttribute('Algorithm', 'http://www.w3.org/2001/04/xmlenc#sha256');

        $ReferenceNode->appendChild(new DOMElement('DigestValue', $digestValue));

        $SignedInfoNode = $XMLRequestDOMDoc->getElementsByTagName('SignedInfo')->item(0);

        $publicCertificatePureString = str_replace('-----BEGIN CERTIFICATE-----', '', $this->certificate()->public());
        $publicCertificatePureString = str_replace('-----END CERTIFICATE-----', '', $publicCertificatePureString);

        $signedInfoSignature = null;

        if (!openssl_sign($SignedInfoNode->C14N(true), $signedInfoSignature, $this->certificate()->getPrivateKey(), OPENSSL_ALGO_SHA256)) {
            throw new Exception('Unable to sign the request');
        }

        $SignatureNode = $XMLRequestDOMDoc->getElementsByTagName('Signature')->item(0);
        $SignatureValueNode = new DOMElement('SignatureValue', base64_encode($signedInfoSignature));
        $SignatureNode->appendChild($SignatureValueNode);

        $KeyInfoNode = $SignatureNode->appendChild(new DOMElement('KeyInfo'));

        $X509DataNode = $KeyInfoNode->appendChild(new DOMElement('X509Data'));
        $X509CertificateNode = new DOMElement('X509Certificate', $publicCertificatePureString);
        $X509DataNode->appendChild($X509CertificateNode);

        return $XMLRequestDOMDoc->saveXML();
    }

    protected function soap($payload): Response
    {
        //todo: this is "hack" - fix it...
        $payload = str_replace('default:', '', $payload);

        $ch = curl_init();

        $headers = [
            'Content-type: text/xml;charset="utf-8"',
            'Accept: text/xml',
            'Cache-Control: no-cache',
            'Pragma: no-cache',
            'Content-length: ' . strlen($payload),
        ];

        $options = [
            CURLOPT_URL            => $this->url,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSLVERSION     => 6,
            CURLOPT_HTTPHEADER     => $headers,
        ];

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return Factory::make($response, $code, $this->request->setPayload($payload));
    }
}
