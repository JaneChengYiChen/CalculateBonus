<?php

namespace App\Http\Controllers\ins_bonus\SupplierImport;

use Illuminate\Support\Facades\Auth;

//元大人壽
class Yuanta
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;

        foreach ($file[0] as $file_key => $file_value) {
            $data = $file_value;
            if (count($data) > 30 && \is_numeric($data[4])) {
                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => trim($data[31]),
                    "handle_name" => trim($data[33]),
                    "insured_id" => trim($data[13]),
                    "insured_name" => trim($data[12]),
                    "ins_no" => trim($data[5]),
                    "main_code" => " ",
                    "effe_date" => trim($data[7]),
                    "ins_type" => trim($data[20]),
                    "tatal_pay_period" => (int) trim($data[19]),
                    "pay_type" => trim($data[17]),
                    "recent_pay_period" => (int) trim($data[15]),
                    "pay_date" => trim($data[9]),
                    "premium_ori" => (int) trim($data[22]),
                    "premium_twd" => (int) trim($data[22]),
                    "bonus" => (int) trim($data[25]),
                    "crc" => trim($data[21]),
                    "crc_rate" => (int) trim($data[29]),
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => $creator,
                    "bonus_rate" => (int) trim($data[26]) / 100,
                    "recent_pay_times" => (int) trim($data[16]),
                ));
            }
        }
        return $array;
    }
}
