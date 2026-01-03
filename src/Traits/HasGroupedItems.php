<?php

namespace DeveloperItsMe\FiscalService\Traits;

trait HasGroupedItems
{
    /** @var array */
    protected $grouped = [];

    abstract protected function getGroupableItems(): array;

    protected function setGroupedItems(): void
    {
        foreach ($this->getGroupableItems() as $item) {
            $this->grouped[$item->getVatRate()]['prices'][] = $basePrice = $item->totalBasePrice();
            $this->grouped[$item->getVatRate()]['vats'][] = $item->totalPrice() - $basePrice;
        }

        ksort($this->grouped, SORT_NUMERIC);
    }

    public function getTotals(): array
    {
        $totals = [
            'total' => 0,
            'base'  => 0,
            'vat'   => 0,
        ];

        foreach ($this->grouped as $item) {
            $totals['base'] += $base = array_sum($item['prices']);
            $totals['vat'] += $vat = array_sum($item['vats']);
            $totals['total'] += $base + $vat;
        }

        return $totals;
    }
}
