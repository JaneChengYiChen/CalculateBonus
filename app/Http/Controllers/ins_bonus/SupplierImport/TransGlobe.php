<?php

namespace App\Http\Controllers\ins_bonus\SupplierImport;

use Illuminate\Support\Facades\Auth;

//全球人壽
class TransGlobe
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        $creator = Auth::guard('api')->user()->name;

        foreach (explode("\n", $file) as $file_key => $file_value) {
            $explode_character = strchr($file_value, ",");
            $explode_arr = ($explode_character) ? explode(",", $file_value) : explode(" ", $file_value);
            $data_decode = ($explode_character) ? $explode_arr : mb_convert_encoding($file_value, 'UTF-8', 'big5');
            $data_pre = ($explode_character) ? $explode_arr : str_replace("%09", " ", urlencode($data_decode));
            $data = ($explode_character) ? $explode_arr : \explode(" ", urldecode($data_pre));

            $format_detection = count($data);
            switch ($format_detection) {
                case 9:
                    $array = self::formatNineColumn($data, $array, $doc_name, $period, $supplier, $creator);
                    break;
                case ($format_detection > 20):
                    $array = self::formatOverTwentyColumn($data, $array, $doc_name, $period, $supplier, $creator);
                    break;
                default:
                    break;
            }
        }

        return $array;
    }

    private static function formatNineColumn($data, $array, $doc_name, $period, $supplier, $creator)
    {

        if (strlen($data[2]) > 10) {
            $bonus = (int) $data[6] * (int) $data[7];
            $premium_ori = null;
            $num_zero = 30 - strlen($data[5]);
            $zeros = str_repeat('0', $num_zero);
            $combination = $zeros . $data[5];
            $is_ins_bonus = (int) substr($combination, 12, 1);
            if ($is_ins_bonus == 2) {
                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => null,
                    "handle_name" => trim($data[1]),
                    "insured_id" => trim(substr($data[2], 0, 10)),
                    "insured_name" => trim(substr($data[2], 10, 12)),
                    "ins_no" => trim(substr($data[3], 9, 10)),
                    "main_code" => " ",
                    "effe_date" => null,
                    "ins_type" => trim(substr($data[3], 29, 6)),
                    "tatal_pay_period" => (int) trim(substr($data[3], 32, 2)),
                    "pay_type" => null,
                    "recent_pay_period" => null,
                    "pay_date" => null,
                    "premium_ori" => (int) $premium_ori,
                    "premium_twd" => (int) $data[8],
                    "bonus" => (int) $bonus,
                    "crc" => substr($data[5], -2, 2),
                    "crc_rate" => (int) $data[6],
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => $creator,
                    "bonus_rate" => null,
                    "recent_pay_times" => null,
                ));
            }
        }

        return $array;
    }

    private static function formatOverTwentyColumn($data, $array, $doc_name, $period, $supplier, $creator)
    {

        if (count($data) > 20) {
            $is_ins_bonus = $data[19];
            if ($is_ins_bonus == 2) {
                $ins_no = (strlen($data[7]) == 8 && \is_numeric($data[7])) ? '00' . $data[7] : $data[7];

                array_push($array, array(
                    "doc_name" => $doc_name,
                    "period" => $period,
                    "supplier_code" => $supplier,
                    "handle_id" => $data[2],
                    "handle_name" => $data[3],
                    "insured_id" => $data[4],
                    "insured_name" => $data[5],
                    "ins_no" => $ins_no,
                    "main_code" => $data[8],
                    "effe_date" => $data[9],
                    "ins_type" => $data[10],
                    "tatal_pay_period" => $data[11],
                    "pay_type" => $data[12],
                    "recent_pay_period" => $data[13],
                    "pay_date" => $data[14],
                    "premium_ori" => (int) $data[16],
                    "premium_twd" => (int) $data[26],
                    "bonus" => (int) $data[18],
                    "crc" => $data[23],
                    "crc_rate" => $data[24],
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => $creator,
                ));
            }
        }

        return $array;
    }
}
