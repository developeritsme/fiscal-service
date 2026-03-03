<?php

namespace DeveloperItsMe\FiscalService\Traits;

use XMLWriter;

trait HasXmlWriter
{
    protected function getXmlWriter(bool $indent = true, string $indentString = '    '): XMLWriter
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent($indent);
        $writer->setIndentString($indentString);

        return $writer;
    }
}
