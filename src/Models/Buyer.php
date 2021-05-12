<?php

namespace DeveloperItsMe\FiscalService\Models;

class Buyer extends Business
{
    protected function getXmlNodeName(): string
    {
        return 'Buyer';
    }
}
