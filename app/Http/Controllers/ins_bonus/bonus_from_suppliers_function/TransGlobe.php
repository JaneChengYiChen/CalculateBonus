<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;

//全球人壽
class TransGlobe extends Controller
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();
        foreach (explode("\n", $file) as $file_key => $file_value) {
            $explode_character = strchr($file_value, ",");
            $explode_arr = ($explode_character) ? explode(",", $file_value) : explode(" ", $file_value);
            $data_decode = ($explode_character) ? $explode_arr : mb_convert_encoding($file_value, 'UTF-8', 'big5');
            $data_pre = ($explode_character) ? $explode_arr : str_replace("%09", " ", urlencode($data_decode));
            $data = ($explode_character) ? $explode_arr : \explode(" ", urldecode($data_pre));

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
                        "created_by" => "Jane",
                    ));
                }
            }
        }

        return $array;
    }

    public static function bonusOriTxt($file, $doc_name, $period, $supplier)
    {
        $array = array();
        foreach (explode("\n", $file) as $file_key => $file_value) {
            $explode_character = strchr($file_value, ",");
            $explode_arr = ($explode_character) ? explode(",", $file_value) : explode(" ", $file_value);
            $data_decode = ($explode_character) ? $explode_arr : mb_convert_encoding($file_value, 'UTF-8', 'big5');
            $data_pre = ($explode_character) ? $explode_arr : str_replace("%09", " ", urlencode($data_decode));
            $data = ($explode_character) ? $explode_arr : \explode(" ", urldecode($data_pre));

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
                        "created_by" => "Jane",
                    ));
                }
            }
        }

        return $array;
    }
}
