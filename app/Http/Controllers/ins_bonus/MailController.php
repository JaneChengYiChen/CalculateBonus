<?php

namespace App\Http\Controllers\ins_bonus;

use App\Exports\BonusDiffExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

class MailController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function send(Request $request)
    {
        $requestBody = $request->json()->all();

        $startPeriod = $requestBody[0]["startPeriod"];
        $endPeriod = $requestBody[0]["endPeriod"];
        $supplier = $requestBody[0]["supplier"];

        ini_set("memory_limit", "1000M");

        $fileName = 'bonus_diff.xlsx';
        $path = Excel::store(new BonusDiffExport(
            $startPeriod,
            $endPeriod,
            $supplier
        ), $fileName, 'local');

        $pathToFile = storage_path('app') . DIRECTORY_SEPARATOR . $fileName;

        Mail::raw('bonus_diff，密碼為統編', function ($message) use ($pathToFile) {
            $to = 'jane.zheng@leishan.com.tw';

            $message->to(env("BonusDiffEmail"))
                ->subject('服務津貼')
                ->attach($pathToFile);
        });

        return response()->json(['success!']);
    }
}
