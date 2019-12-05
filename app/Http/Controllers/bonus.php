<?php

namespace App\Http\Controllers;

use App\table_insurance_ori_bonus;

class bonus extends Controller
{
    public function supplier_import()
    {
        //全球人壽
        $project_root = env('Import_file_path');
        $files = glob("{$project_root}/*.csv");
        $array = array();
        foreach ($files as $files_key => $files_value) {
            $content = file_get_contents($files_value);
            foreach (explode("\n", $content) as $str_key => $str_value) {
                $data = explode(",", $str_value);

                if (count($data) > 20) {
                    $is_ins_bonus = $data[19];
                    if ($is_ins_bonus == 2) {
                        array_push($array, array(
                            "period" => "201808",
                            "supplier_code" => "300000737",
                            "handle_id" => $data[2],
                            "handle_name" => $data[3],
                            "insured_id" => $data[4],
                            "insured_name" => $data[5],
                            "ins_no" => $data[7],
                            "main_code" => $data[8],
                            "effe_date" => $data[9],
                            "ins_type" => $data[10],
                            "tatal_pay_period" => $data[11],
                            "pay_type" => $data[12],
                            "recent_pay_period" => $data[13],
                            "pay_date" => $data[14],
                            "premium_ori" => (int) $data[16],
                            "premium_twd" => (int) $data[23],
                            "bonus" => (int) $data[18],
                            "crc" => $data[23],
                            "crc_rate" => $data[24],
                            "created_date" => date('Y-m-d H:i:s'),
                            "created_by" => "Jane",
                        ));
                    }
                }
            }
        }
        table_insurance_ori_bonus::insert($array);
        echo json_encode("success!");
    }

    public function rules()
    {
        echo 'hihi';
    }
}
