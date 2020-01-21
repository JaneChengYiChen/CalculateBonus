<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;

//台灣人壽
class TaiwanLife extends Controller
{
    public static function bonus_ori($file, $doc_name, $period, $supplier)
    {
        $array = array();
        foreach ($file[0] as $file_key => $file_value) {

            $data = $file_value;
            if (count($data) > 20 && \is_numeric($data[5])) {
                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => trim($data[25]),
                    "handle_name" => trim($data[24]),
                    "insured_id" => trim($data[8]),
                    "insured_name" => trim($data[9]),
                    "ins_no" => trim($data[2]),
                    "main_code" => trim($data[3]),
                    "effe_date" => trim($data[6]),
                    "ins_type" => trim($data[4]),
                    "tatal_pay_period" => (int) trim($data[5]),
                    "pay_type" => trim($data[15]),
                    "recent_pay_period" => (int) substr($data[16], 0, 2),
                    "pay_date" => trim($data[23]),
                    "premium_ori" => (int) trim($data[17]),
                    "premium_twd" => (int) trim($data[18]),
                    "bonus" => (int) trim($data[21]),
                    "crc" => trim($data[12]),
                    "crc_rate" => (int) trim($data[22]),
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => "Jane",
                    "bonus_rate" => (int) trim($data[19]) / 100,
                    "recent_pay_times" => (int) substr($data[16], 2, 2),
                ));
            }
        }
        return $array;
    }
}
