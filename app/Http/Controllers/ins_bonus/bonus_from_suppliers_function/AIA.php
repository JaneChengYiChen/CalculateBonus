<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

//友邦人壽
class AIA extends Controller
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;
        foreach ($file[0] as $file_key => $file_value) {
            $data_detection = count($file_value);

            switch ($data_detection) {
                case ($data_detection < 30):
                    $array = self::formatLessThanTwenty($file_value, $array, $doc_name, $period, $supplier, $creator);
                    break;
                default:
                    $array = self::formatDefault($file_value, $array, $doc_name, $period, $supplier, $creator);
                    break;
            }
        }
        return $array;
    }

    private static function formatLessThanTwenty($data, $array, $doc_name, $period, $supplier, $creator)
    {
        if (\is_numeric($data[8]) && !empty($data[5])) {
            array_push($array, array(
                "doc_name" => $doc_name,
                "period" => $period,
                "supplier_code" => $supplier,
                "handle_id" => trim($data[14]),
                "handle_name" => trim($data[13]),
                "insured_id" => trim($data[12]),
                "insured_name" => trim($data[11]),
                "ins_no" => trim($data[5]),
                "main_code" => '',
                "effe_date" => trim($data[6]),
                "ins_type" => trim($data[4]),
                "tatal_pay_period" => '',
                "pay_type" => trim($data[10]),
                "recent_pay_period" => '',
                "pay_date" => " ",
                "premium_ori" => (int) trim($data[9]),
                "premium_twd" => (int) trim($data[9]),
                "bonus" => (int) trim($data[15]),
                "crc" => ' ',
                "crc_rate" => ' ',
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => $creator,
                "bonus_rate" => ' ',
                "recent_pay_times" => (int) $data[17],
            ));
        }

        return $array;
    }
    private static function formatDefault($data, $array, $doc_name, $period, $supplier, $creator)
    {

        if (count($data) > 30 && \is_numeric($data[17])) {
            $premium_ori = ($data[32] == 0) ? $data[16] : $data[32];
            array_push($array, array(
                "doc_name" => $doc_name,
                "period" => $period,
                "supplier_code" => $supplier,
                "handle_id" => trim($data[2]),
                "handle_name" => trim($data[3]),
                "insured_id" => trim($data[6]),
                "insured_name" => trim($data[7]),
                "ins_no" => trim($data[5]),
                "main_code" => trim($data[27]),
                "effe_date" => trim($data[15]),
                "ins_type" => trim($data[14]),
                "tatal_pay_period" => (int) trim($data[8]),
                "pay_type" => trim($data[9]),
                "recent_pay_period" => (int) substr($data[10], 0, 2),
                "pay_date" => " ",
                "premium_ori" => (int) $premium_ori,
                "premium_twd" => (int) trim($data[16]),
                "bonus" => (int) trim($data[19]),
                "crc" => trim($data[31]),
                "crc_rate" => (int) trim($data[33]),
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => $creator,
                "bonus_rate" => (int) trim($data[20]) / 100,
                "recent_pay_times" => (int) substr($data[10], 2, 2),
            ));
        }

        return $array;
    }
}
