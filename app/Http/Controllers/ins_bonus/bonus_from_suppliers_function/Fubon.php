<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;

//富邦人壽
class Fubon extends Controller
{
    public static function bonus_ori($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $file = mb_convert_encoding($file, 'UTF-8', 'big5');
        foreach (explode("\n", $file) as $file_key => $file_value) {

            $sup_code = substr($file_value, 0, 6); //單位代號
            $sup_period = substr($file_value, 6, 6); //保單期間
            $source = substr($file_value, 12, 1); //資料來源；A-壽險;B-個人傷害險;C-旅行險
            $ins_no = substr($file_value, 13, 14); //保單號碼
            $payer_name = trim(substr($file_value, 27, 13)); //要保人姓名
            $payer_ID = substr($file_value, 94, 10); //要保人ID
            $insured_name = substr($file_value, 105, 12); //被保人姓名
            $insured_id = substr($file_value, 124, 10); //被保人ID
            $unknown = substr($file_value, 134, 1); //未知
            $bonus_rate = (int) substr($file_value, 136, 2); //佣金率，除100
            $handle_id = substr($file_value, 138, 10); //業務員ID
            $effe_date = substr($file_value, 148, 8); //生效日
            $unknown_2 = substr($file_value, 156, 1); //未知
            $ins_type = substr($file_value, 157, 4); //險種
            $total_pay_period = substr($file_value, 161, 2); //年期
            $pay_type = substr($file_value, 165, 1); //繳別
            $recent_pay_period = substr($file_value, 166, 2); //繳費年度
            $recent_pay_times = (int) substr($file_value, 168, 2); //繳費次數
            $premium_ori = (int) substr($file_value, 185, 12); //主約基本保費
            $premium_twd = (int) substr($file_value, 185, 12);
            $bonus = (int) substr($file_value, 240, 10); //服務津貼
            $crc = substr($file_value, 274, 3); //幣別
            $crc_rate = substr($file_value, 277, 11); //匯率
            $occupation = substr($file_value, 288, 1); //職業等級
            $unknown_3 = substr($file_value, 289, 9); //未知

            array_push($array, array(
                "doc_name" => $doc_name,
                "period" => $period,
                "supplier_code" => $supplier,
                "handle_id" => $handle_id,
                "handle_name" => " ",
                "insured_id" => $insured_id,
                "insured_name" => " ",
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
                "crc" => $crc,
                "crc_rate" => $crc_rate,
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => "Jane",
                "bonus_rate" => $bonus_rate,
                "recent_pay_times" => $recent_pay_times,
            ));
        }
        return $array;
    }
}
