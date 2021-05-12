<?php

namespace DeveloperItsMe\FiscalService\Models;

class Seller extends Business
{
    protected function getXmlNodeName(): string
    {
        return 'Seller';
    }
}
