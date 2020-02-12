<?php
namespace App\Http\Controllers;

use App\Exports\BonusDiffExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

class MailController extends Controller
{
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
        ), $fileName, 'tmp');

        $pathToFile = "/tmp/" . DIRECTORY_SEPARATOR . $fileName;
        $zip_path = DIRECTORY_SEPARATOR . "tmp" . DIRECTORY_SEPARATOR . $fileName . "zip";
        $password = env("BonusDiffPassword");
        system("zip -P {$password} {$zip_path} {$pathToFile}");
        echo var_Dump($pathToFile);
        exit;

        Mail::raw('bonus_diff，密碼為統編', function ($message) use ($zip_path) {
            $to = 'jane.zheng@leishan.com.tw';

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
