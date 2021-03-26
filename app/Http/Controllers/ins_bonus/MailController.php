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

        $fileName = "服務津貼{$startPeriod}_{$endPeriod}.xlsx";
        $fileNameZip = "服務津貼{$startPeriod}_{$endPeriod}.zip";
        $path = Excel::store(new BonusDiffExport(
            $startPeriod,
            $endPeriod,
            $supplier
        ), $fileName, 'tmp');

        $pathToFile = "/tmp/" . $fileName;
        $zip_path = "/tmp/" . $fileNameZip;
        $password = env("BonusDiffPassword");
        system("zip -P {$password} {$zip_path} {$pathToFile}");

        $content =
            "Dear all,
        附檔為服務津貼，密碼為統編
        時間區間：{$startPeriod} ~ {$endPeriod}
        ";

        Mail::raw($content, function ($message) use ($zip_path) {

            $message->to(env("BonusDiffEmail"))
                ->subject('服務津貼')
                ->attach($zip_path);
        });

        if (file_exists($zip_path)) {
            unlink($zip_path);
        }
        if (file_exists($pathToFile)) {
            unlink($pathToFile);
        }

        return response()->json(['success!']);
    }
}
