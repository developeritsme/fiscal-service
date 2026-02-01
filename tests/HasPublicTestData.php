<?php

namespace Tests;

trait HasPublicTestData
{
    protected $certPath = './CoreitPecatSoft.pfx';
    protected $certPassphrase = '123456';
    protected $enu = 'si747we972';
    protected $seller = 'Public Company';
    protected $tin = '12345678';
    protected $unitCode = 'xx123xx123';
    protected $softwareCode = 'ss123ss123';
    protected $maintainerCode = 'mm123mm123';
    protected $operatorCode = 'oo123oo123';
    protected $isVat = true;
    protected $vatRate = 7;

    protected function loadEnvCredentials(string $prefix = 'FISCAL_VAT'): void
    {
        $prefixed = [
            'CERT_PATH'       => 'certPath',
            'CERT_PASSPHRASE' => 'certPassphrase',
            'ENU'             => 'enu',
            'SELLER'          => 'seller',
            'TIN'             => 'tin',
            'UNIT_CODE'       => 'unitCode',
            'OPERATOR_CODE'   => 'operatorCode',
        ];

        foreach ($prefixed as $suffix => $property) {
            $value = getenv("{$prefix}_{$suffix}");
            if ($value !== false) {
                $this->$property = $value;
            }
        }

        $shared = [
            'FISCAL_SOFTWARE_CODE'   => 'softwareCode',
            'FISCAL_MAINTAINER_CODE' => 'maintainerCode',
        ];

        foreach ($shared as $envVar => $property) {
            $value = getenv($envVar);
            if ($value !== false) {
                $this->$property = $value;
            }
        }
    }

    protected function requireEnvCredentials(string $prefix = 'FISCAL_VAT'): void
    {
        if (getenv("{$prefix}_CERT_PATH") === false) {
            $this->markTestSkipped(
                "Integration test requires {$prefix}_* env vars. "
                . 'Copy .env.testing.example to .env.testing and fill in real credentials.'
            );
        }
    }
}
