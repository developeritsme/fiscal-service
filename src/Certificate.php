<?php

namespace DeveloperItsMe\FiscalService;

class Certificate
{
    protected $rawCertificate;

    protected $certificate;

    protected $passphrase;

    /** @var false|resource */
    protected $privateKeyResource = false;

    protected $publicCertificateData;

    public function __construct($path, $passphrase)
    {
        if (file_exists($path)) {
            $this->rawCertificate = $this->readCertificateFromDisk($path);
        } else {
            $this->rawCertificate = $path;
        }
        $this->passphrase = $passphrase;

        $read = openssl_pkcs12_read($this->rawCertificate, $this->certificate, $this->getPassphrase());
        if ($read === false) {
            for ($e = openssl_error_string(), $i = 0; $e; $e = openssl_error_string(), $i++)
                printf("SSL l%d: %s" . PHP_EOL, $i, $e);
            exit(1);
        }
        $this->privateKeyResource = openssl_pkey_get_private($this->certificate['pkey'], $this->getPassphrase());
        $this->publicCertificateData = openssl_x509_parse($this->certificate['cert']);
    }

    public function key($key)
    {
        return $this->certificate[$key];
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

    public function getPassphrase(): string
    {
        return $this->passphrase;
    }
}
