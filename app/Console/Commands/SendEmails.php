<?php

namespace App\Console\Commands;

use App\Exports\BonusDiffExport;
use File;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Mail;

class SendEmails extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'BonusDiff:SendMail';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '收入驗證寄送 Email';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $this::bonusDiffEmailSend();
        // 檔案紀錄在 storage/BonusDiff.log
        $log_file_path = storage_path('BonuDiff.log');

        // 記錄當時的時間
        $log_info = [
            'date' => date('Y-m-d H:i:s'),
            'msg' => 'Email has been sent!',
        ];

        // 記錄 JSON 字串
        $log_info_json = json_encode($log_info) . "\r\n";

        // 記錄 Log
        File::append($log_file_path, $log_info_json);
    }

    private function bonusDiffEmailSend()
    {

        $startPeriod = '201901';
        $endPeriod = '201912';
        $supplier = [
            "300000737",
            "300000735",
            "300000734",
            "300006376",
            "300000749",
            "300000717",
            "300000722",
        ];

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

        $zip = new \ZipArchive();
        $zip->open($zip_path, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile($pathToFile);
        $zip->close();

        $content =
            "Dear all,
        附檔為服務津貼，密碼為統編
        時間區間：{$startPeriod} ~ {$endPeriod}
        ";

        Mail::raw($content, function ($message) use ($zip_path) {

            $message->to(env("BonusDiffEmail"))
                ->cc(env("BonusDiffEmailCC"))
                ->subject('服務津貼')
                ->attach($zip_path);
        });

        if (file_exists($zip_path)) {
            unlink($zip_path);
        }
        if (file_exists($pathToFile)) {
            unlink($pathToFile);
        }
    }
}
