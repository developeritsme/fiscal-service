<?php

namespace DeveloperItsMe\FiscalService\Models;

class IICRefs extends Model
{
    /** @var IICRef[] */
    protected array $refs = [];

    public function add(IICRef $ref): self
    {
        $this->refs[] = $ref;

        return $this;
    }

    public function all(): array
    {
        return $this->refs;
    }

    public function toXML(): string
    {
        $writer = $this->getXmlWriter();
        $writer->startElementNs(null, 'IICRefs', null);
        foreach ($this->refs as $ref) {
            $writer->writeRaw($ref->toXML());
        }
        $writer->endElement();

        return $writer->outputMemory();
    }
}
