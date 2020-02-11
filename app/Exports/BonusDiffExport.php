<?php

namespace App\Exports;

use App\Exports\BonusDiffSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BonusDiffExport implements ShouldAutoSize, WithMultipleSheets
{
    // /**
    //  * @return \Illuminate\Support\Collection
    //  */
    use Exportable;

    public function __construct(string $start, string $end, array $supplier)
    {
        $this->start = $start;
        $this->end = $end;
        $this->supplier = $supplier;
    }

    public function sheets(): array
    {
        $sheets = [];

        foreach ($this->supplier as $key => $value) {
            $sheets[] = new BonusDiffSheet($this->start, $this->end, $value);
        }

        return $sheets;
    }
}
