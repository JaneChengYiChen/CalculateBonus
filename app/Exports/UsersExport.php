<?php

namespace App\Exports;

use App\import_bonus_suppliers;
use Maatwebsite\Excel\Concerns\FromCollection;

class UsersExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return import_bonus_suppliers::all();
    }
}
