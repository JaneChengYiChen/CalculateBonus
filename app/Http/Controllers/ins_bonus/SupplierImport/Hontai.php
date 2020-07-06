<?php

namespace App\Http\Controllers\ins_bonus\SupplierImport;

use Illuminate\Support\Facades\Auth;

//宏泰人壽
class Hontai
{
    public function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;
        $file = mb_convert_encoding($file, 'UTF-8', 'big5');

        foreach (explode("\n", $file) as $file_key => $file_value) {
            $count = strlen($file_value);

            if ($count >= 200) {
                $data = explode(",", $file_value);
                $is_mandrain = preg_match("/^([\x7f-\xff]+)$/", $data[0]);
                if ($is_mandrain == 1) {
                    continue;
                }
                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => null,
                    "handle_name" => $data[12],
                    "insured_id" => null,
                    "insured_name" => $data[4],
                    "ins_no" => $data[3],
                    "main_code" => " ",
                    "effe_date" => $data[7],
                    "ins_type" => $data[5],
                    "tatal_pay_period" => null,
                    "pay_type" => substr($data[8], -1),
                    "recent_pay_period" => null,
                    "pay_date" => " ",
                    "premium_ori" => $data[15],
                    "premium_twd" => $data[9],
                    "bonus" => $data[11],
                    "crc" => $data[14],
                    "crc_rate" => " ",
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => $creator,
                    "bonus_rate" => null,
                    "recent_pay_times" => null,
                ));
            }
        }
        return $array;
    }
}
