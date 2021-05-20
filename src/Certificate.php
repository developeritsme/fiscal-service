<?php

namespace DeveloperItsMe\FiscalService;

class Certificate
{
    protected $rawCertificate;

    protected $certificate;

    /** @var false|resource */
    protected $privateKeyResource = false;

    protected $publicCertificateData;

    public function __construct($path, $passphrase)
    {
        if (@file_exists($path)) {
            $this->rawCertificate = $this->readCertificateFromDisk($path);
        } else {
            $this->rawCertificate = $path;
        }

        $read = openssl_pkcs12_read($this->rawCertificate, $this->certificate, $passphrase);
        if ($read === false) {
            for ($e = openssl_error_string(), $i = 0; $e; $e = openssl_error_string(), $i++)
                printf("SSL l%d: %s" . PHP_EOL, $i, $e);
            exit(1);
        }

        $this->privateKeyResource = openssl_pkey_get_private($this->certificate['pkey'], $passphrase);

        $this->publicCertificateData = openssl_x509_parse($this->certificate['cert']);
    }

    public function public()
    {
        return $this->certificate['cert'];
    }

    public function raw()
    {
        return $this->rawCertificate;
    }

    public function getPrivateKey()
    {
        return $this->privateKeyResource;
    }

    public function getPublicData()
    {
        return $this->publicCertificateData;
    }

    protected function readCertificateFromDisk($path): string
    {
        $cert = @file_get_contents($path);
        if (false === $cert) {
            throw new \Exception("Ne mogu procitati certifikat sa lokacije: " .
                $path, 1);
        }

        return $cert;
    }
}
