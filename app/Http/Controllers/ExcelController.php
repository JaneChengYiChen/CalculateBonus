<?php

namespace App\Http\Controllers;

use App\Exports\BonusDiffExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ExcelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function export(Request $request)
    {
        $requestBody = $request->json()->all();

        $startPeriod = $requestBody[0]["startPeriod"];
        $endPeriod = $requestBody[0]["endPeriod"];
        $supplier = $requestBody[0]["supplier"];

        ini_set("memory_limit", "1000M");
        return Excel::download(new BonusDiffExport(
            $startPeriod,
            $endPeriod,
            $supplier
        ), 'bonus_diff.xlsx');
    }
}
