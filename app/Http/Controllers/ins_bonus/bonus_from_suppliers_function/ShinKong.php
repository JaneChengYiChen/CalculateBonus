<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;

//新光人壽
class ShinKong extends Controller
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();

        $file = mb_convert_encoding($file, "UTF-8", "BIG5");

        foreach (explode("\n", $file) as $file_key => $file_value) {
            $data = explode(",", $file_value);

            if (count($data) > 20) {
                $handle_id = $data[1];
                $handle_name = $data[2];
                $ins_no = $data[3];
                $ins_type = $data[4];
                $effe_date = $data[6];
                $total_pay_period = $data[7];
                $premium_ori = (int) $data[21];
                $premium_twd = (int) $data[11];
                $pay_type = substr($data[10], -1, 1);
                $recent_pay_period = substr($data[10], 0, 2);
                $bonus = (int) $data[12];
                $crc = $data[22];
                $crc_rate = (int) $data[23];
                $bonus_rate = round($bonus / $premium_twd, 2);
                $recent_pay_times = " ";
                $insured_id = $data[15];
                $insured_name = $data[16];
                $main_code = ($data[26] == '主約') ? 1 : 0;

                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => $handle_id,
                    "handle_name" => $handle_name,
                    "insured_id" => $insured_id,
                    "insured_name" => $insured_name,
                    "ins_no" => $ins_no,
                    "main_code" => $main_code,
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
        }

        return $array;
    }
}
