<?php

namespace DeveloperItsMe\FiscalService;

use DeveloperItsMe\FiscalService\Exceptions\CertificateException;

class Certificate
{
    protected string $rawCertificate;

    protected array $certificate;

    protected \OpenSSLAsymmetricKey|false $privateKeyResource = false;

    protected array|false $publicCertificateData;

    protected function __construct()
    {
    }

    /** @throws CertificateException */
    public static function fromFile(string $path, string $passphrase): self
    {
        $cert = @file_get_contents($path);
        if (false === $cert) {
            throw new CertificateException('Cannot read certificate from path: ' . $path);
        }

        $instance = new self();
        $instance->rawCertificate = $cert;
        $instance->parsePkcs12($passphrase);

        return $instance;
    }

    /** @throws CertificateException */
    public static function fromContent(string $content, string $passphrase): self
    {
        $instance = new self();
        $instance->rawCertificate = $content;
        $instance->parsePkcs12($passphrase);

        return $instance;
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
     * Sign data using the certificate's private key.
     *
     * @throws Exceptions\FiscalException
     */
    public function sign(string $data, int $algorithm = OPENSSL_ALGO_SHA256): string
    {
        if (! openssl_sign($data, $signature, $this->privateKeyResource, $algorithm)) {
            throw new Exceptions\FiscalException('Unable to sign data');
        }

        return $signature;
    }

    public function getPublicData(): array|false
    {
        return $this->publicCertificateData;
    }

    /**
     * Get certificate expiration date (UTC).
     *
     * @return \DateTimeImmutable|null
     */
    public function expiresAt(): ?\DateTimeImmutable
    {
        $timestamp = $this->publicCertificateData['validTo_time_t'] ?? null;

        return $timestamp
            ? (new \DateTimeImmutable('@' . $timestamp))->setTimezone(new \DateTimeZone('UTC'))
            : null;
    }

    private function parsePkcs12(string $passphrase): void
    {
        $parsed = [];
        $read = openssl_pkcs12_read($this->rawCertificate, $parsed, $passphrase);
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

        $this->certificate = $parsed;
        $this->privateKeyResource = openssl_pkey_get_private($this->certificate['pkey'], $passphrase);
        $this->publicCertificateData = openssl_x509_parse($this->certificate['cert']);
    }
}
