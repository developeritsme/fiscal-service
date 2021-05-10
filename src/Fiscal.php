<?php


namespace DeveloperItsMe\FiscalService;


class Fiscal
{
    private $url = 'https://efi.tax.gov.me/fs-v1';

    public function __construct($test = false)
    {
        if ($test) {
            $this->url = 'https://efitest.tax.gov.me/fs-v1';
        }
    }
}
