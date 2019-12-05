<?php

namespace App\Http\Controllers;

use App\table_insurance_ori_bonus;
use App\table_supplier_bonus_doc_rules;

class bonus extends Controller
{
    public function supplier_import()
    {
        //全球人壽
        $project_root = env('import_file_path');
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
                            "created_at" => date('Y-m-d H:i:s'),
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
        $project_root = env('supplier_bonus_rule_path');
        $files = glob("{$project_root}/*.csv");
        $array = array();
        table_supplier_bonus_doc_rules::all();
        foreach ($files as $files_key => $files_value) {
            $content = file_get_contents($files_value);
            foreach (explode("\n", $content) as $str_key => $str_value) {
                $data = explode(",", $str_value);
                if (mb_strlen($data[2], "UTF-8") == strlen($data[2])) {
                    array_push($array, array(
                        "doc_date" => $data[1],
                        "doc_number" => $data[2],
                        "doc_number_leishan" => $data[3],
                        "rules_start_date" => $data[4],
                        "rules_due_date" => $data[5],
                        "auto_extension" => $data[6],
                        "supplier_code" => $data[7],
                        "supplier_name" => $data[8],
                        "product_name" => $data[9],
                        "product_code" => $data[10],
                        "y_period_lower_limit" => $data[16],
                        "y_period_upper_limit" => $data[17],
                        "premium_type" => $data[18],
                        "cus_period" => $data[19],
                        "cus_status" => $data[20],
                        "1" => $data[21],
                        "2" => $data[22],
                        "3" => $data[23],
                        "4" => $data[24],
                        "5" => $data[25],
                        "6" => $data[26],
                        "7" => $data[27],
                        "8" => $data[28],
                        "9" => $data[29],
                        "10" => $data[30],
                        "11" => $data[31],
                        "12" => $data[32],
                        "13" => $data[33],
                        "14" => $data[34],
                        "15" => $data[35],
                        "16" => $data[36],
                        "17" => $data[37],
                        "18" => $data[38],
                        "19" => $data[39],
                        "20" => $data[40],
                        "remark" => $data[41],
                        "created_at" => date('Y-m-d H:i:s'),
                        "created_by" => "jane",
                    ));
                }
            }
        }
        table_supplier_bonus_doc_rules::insert($array);
        echo json_encode("success!");
    }
}
