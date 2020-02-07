<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

//遠雄人壽
class Farglory extends Controller
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;
        $file = mb_convert_encoding($file, 'UTF-8', 'big5');
        foreach (explode("\n", $file) as $file_key => $file_value) {
            $handle_id = substr($file_value, 11, 10);
            $handle_name = null;
            $insured_id = null;
            $insured_name = substr($file_value, 55, 9);
            $ins_no = substr($file_value, 35, 10);
            $effe_date = substr($file_value, 88, 9);
            $ins_type = substr($file_value, 82, 6);
            $total_pay_period = substr($file_value, 69, 2);
            $pay_type = substr($file_value, 71, 1);
            $recent_pay_period = substr($file_value, 72, 2);
            $premium_ori = (int) substr($file_value, 97, 9);
            $premium_twd = (int) substr($file_value, 97, 9);
            $bonus = (int) substr($file_value, 122, 9);
            $bonus_rate = (int) substr($file_value, 131, 7);
            $recent_pay_times = (int) substr($file_value, 74, 2);
            array_push($array, array(
                "doc_name" => $doc_name,
                "period" => $period,
                "supplier_code" => $supplier,
                "handle_id" => $handle_id,
                "handle_name" => $handle_name,
                "insured_id" => $insured_id,
                "insured_name" => $insured_name,
                "ins_no" => $ins_no,
                "main_code" => " ",
                "effe_date" => $effe_date,
                "ins_type" => $ins_type,
                "tatal_pay_period" => $total_pay_period,
                "pay_type" => $pay_type,
                "recent_pay_period" => $recent_pay_period,
                "pay_date" => " ",
                "premium_ori" => $premium_ori,
                "premium_twd" => $premium_twd,
                "bonus" => $bonus,
                "crc" => " ",
                "crc_rate" => " ",
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => $creator,
                "bonus_rate" => substr($file_value, 131, 7),
                "recent_pay_times" => substr($file_value, 74, 2),
            ));
        }

        return $array;
    }
}
