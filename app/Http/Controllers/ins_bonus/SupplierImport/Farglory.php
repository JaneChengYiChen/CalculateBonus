<?php

namespace App\Http\Controllers\ins_bonus\SupplierImport;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

//遠雄人壽
class Farglory extends Controller
{
    public function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;
        $file = mb_convert_encoding($file, 'UTF-8', 'big5');
        // $encoding = mb_detect_encoding($file, array('GB2312', 'GBK', 'UTF-16', 'UCS-2', 'UTF-8', 'BIG5', 'ASCII'));

        foreach (explode("\n", $file) as $file_key => $file_value) {
            $strlen = strlen($file_value);
            switch ($strlen) {
                case 193:
                    $this->typeOne($file_value);
                    break;
                case 190:
                    $this->typeThree($file_value);
                    break;
                case 192:
                    $this->typeFour($file_value);
                    break;
                default:
                    $this->typeTwo($file_value);
                    break;
            }
            array_push($array, array(
                "doc_name" => $doc_name,
                "period" => $period,
                "supplier_code" => $supplier,
                "handle_id" => $this->handle_id,
                "handle_name" => $this->handle_name,
                "insured_id" => $this->insured_id,
                "insured_name" => $this->insured_name,
                "ins_no" => $this->ins_no,
                "main_code" => " ",
                "effe_date" => $this->effe_date,
                "ins_type" => $this->ins_type,
                "tatal_pay_period" => $this->total_pay_period,
                "pay_type" => $this->pay_type,
                "recent_pay_period" => $this->recent_pay_period,
                "pay_date" => " ",
                "premium_ori" => $this->premium_ori,
                "premium_twd" => $this->premium_twd,
                "bonus" => $this->bonus,
                "crc" => " ",
                "crc_rate" => " ",
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => $creator,
                "bonus_rate" => $this->bonus_rate,
                "recent_pay_times" => $this->recent_pay_times,
            ));
        }
        return $array;
    }

    private function typeOne($file_value)
    {
        $this->handle_id = substr($file_value, 11, 10);
        $this->handle_name = substr($file_value, 21, 9);
        $this->insured_id = null;
        $this->insured_name = null;
        $this->ins_no = substr($file_value, 35, 10);
        $this->effe_date = substr($file_value, 88, 9);
        $this->ins_type = substr($file_value, 82, 6);
        $this->total_pay_period = substr($file_value, 69, 2);
        $this->pay_type = substr($file_value, 71, 1);
        $this->recent_pay_period = substr($file_value, 72, 2);
        $this->premium_ori = (int) substr($file_value, 97, 9);
        $this->premium_twd = (int) substr($file_value, 97, 9);
        $this->bonus = (int) substr($file_value, 122, 9);
        $this->bonus_rate = (int) substr($file_value, 131, 7);
        $this->recent_pay_times = substr($file_value, 74, 2);
    }

    private function typeTwo($file_value)
    {
        $this->handle_id = substr($file_value, 11, 10);
        $this->handle_name = substr($file_value, 21, 9);
        $this->insured_id = null;
        $this->insured_name = null;
        $this->ins_no = mb_substr($file_value, 28, 10, "utf-8");
        $this->effe_date = mb_substr($file_value, 76, 9, "utf-8");
        $this->ins_type = mb_substr($file_value, 62, 6, "utf-8");
        $this->total_pay_period = mb_substr($file_value, 55, 2, "utf-8");
        $this->pay_type = mb_substr($file_value, 57, 1, "utf-8");
        $this->recent_pay_period = mb_substr($file_value, 58, 2, "utf-8");
        $this->premium_ori = (int) mb_substr($file_value, 85, 9, "utf-8");
        $this->premium_twd = (int) mb_substr($file_value, 85, 9, "utf-8");
        $this->bonus = (int) mb_substr($file_value, 110, 9, "utf-8");
        $this->bonus_rate = (int) mb_substr($file_value, 119, 7, "utf-8");
        $this->recent_pay_times = mb_substr($file_value, 60, 2, "utf-8");
    }

    private function typeThree($file_value)
    {
        $this->handle_id = substr($file_value, 11, 10);
        $this->handle_name = mb_substr($file_value, 21, 4, "utf-8");
        $this->insured_id = null;
        $this->insured_name = null;
        $this->ins_no = trim(substr($file_value, 33, 11));
        $this->ins_no = preg_replace("|[\x21-\x2ba-z]|is", "", $this->ins_no);
        $this->effe_date = mb_substr($file_value, 76, 9, "utf-8");
        $this->ins_type = mb_substr($file_value, -118, 6, "utf-8");
        $this->total_pay_period = mb_substr($file_value, -125, 2, "utf-8");
        $this->pay_type = mb_substr($file_value, -123, 1, "utf-8");
        $this->recent_pay_period = mb_substr($file_value, -122, 2, "utf-8");
        $this->premium_ori = (int) mb_substr($file_value, 85, 9, "utf-8");
        $this->premium_twd = (int) mb_substr($file_value, 85, 9, "utf-8");
        $this->bonus = (int) mb_substr($file_value, 110, 9, "utf-8");
        $this->bonus_rate = (int) mb_substr($file_value, 119, 7, "utf-8");
        $this->recent_pay_times = mb_substr($file_value, -120, 2, "utf-8");
    }

    private function typeFour($file_value)
    {
        $this->handle_id = substr($file_value, 11, 10);
        $this->handle_name = mb_substr($file_value, 21, 4, "utf-8");
        $this->insured_id = null;
        $this->insured_name = null;
        $this->ins_no = trim(mb_substr($file_value, 27, 11, "utf-8"));
        $this->ins_no = preg_replace("|[\x21-\x2ba-z]|is", "", $this->ins_no);
        $this->effe_date = trim(mb_substr($file_value, 75, 9, "utf-8"));
        $this->ins_type = substr($file_value, 75, 6);
        $this->total_pay_period = mb_substr($file_value, -124, 2, "utf-8");
        $this->pay_type = mb_substr($file_value, -122, 1, "utf-8");
        $this->recent_pay_period = mb_substr($file_value, -121, 2, "utf-8");
        $this->premium_ori = (int) substr($file_value, 97, 9);
        $this->premium_twd = (int) substr($file_value, 97, 9);
        $this->bonus = (int) substr($file_value, 122, 9);
        $this->bonus_rate = (int) substr($file_value, 131, 7);
        $this->recent_pay_times = mb_substr($file_value, -119, 2, "utf-8");
    }
}
