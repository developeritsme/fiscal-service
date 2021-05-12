<?php

namespace DeveloperItsMe\FiscalService\Traits;

use XMLWriter;

trait HasXmlWriter
{
    protected function getXmlWriter($indent = true, $indentString = '    '): XMLWriter
    {
        $writer = new XMLWriter();
        $writer->openMemory();

        $writer->setIndent($indent);
        $writer->setIndentString($indentString);

        return $writer;
    }
}
