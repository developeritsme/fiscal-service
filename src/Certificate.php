<?php

namespace DeveloperItsMe\FiscalService;

use DeveloperItsMe\FiscalService\Exceptions\CertificateException;

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
            $errors = [];
            while ($e = openssl_error_string()) {
                $errors[] = $e;
            }
            throw new CertificateException(
                'Failed to read PKCS12 certificate: ' . ($errors[0] ?? 'Unknown error'),
                $errors
            );
        }

        $this->privateKeyResource = openssl_pkey_get_private($this->certificate['pkey'], $passphrase);

        $this->publicCertificateData = openssl_x509_parse($this->certificate['cert']);
    }

    public function public(): string
    {
        return $this->certificate['cert'];
    }

    public function raw(): string
    {
        return $this->rawCertificate;
    }

    /**
     * @return resource|false
     */
    public function getPrivateKey()
    {
        return $this->privateKeyResource;
    }

    /**
     * @return array|false
     */
    public function getPublicData()
    {
        return $this->publicCertificateData;
    }

    protected function readCertificateFromDisk($path): string
    {
        $cert = @file_get_contents($path);
        if (false === $cert) {
            throw new \Exception('Ne mogu procitati certifikat sa lokacije: ' .
                $path, 1);
        }

        return $cert;
    }
}
