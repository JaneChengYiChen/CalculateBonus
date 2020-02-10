<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\ToModel;

class UsersImport implements ToModel
{
    use Importable;

    public function model(array $row)
    {
        return new user([
            'UserName' => $row['UserName'],
            'Password' => $row['Password'],
            'date' => \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($row['保險公司發文日期']),
        ]);
    }
}
