<?php

namespace App\Http\Controllers\ins_bonus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\Farglory;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\Fubon;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\TransGlobe;
use App\table_insurance_ori_bonus;
use App\table_supplier_bonus_doc_rules;
use Illuminate\Http\Request;

class bonus extends Controller
{
    public function supplier_import(Request $request)
    {
        $supplier = $request->supplier;
        $period = $request->period;
        $file_path = $request->file->path();
        $doc_name = $request->file->getClientOriginalName();

        $file = file_get_contents($file_path);

        switch ($supplier) {
            case 300000737: //全球人壽
                $array = TransGlobe::bonus_ori($file, $doc_name, $period, $supplier);
                break;
            case 300000735: //遠雄人壽
                $array = Farglory::bonus_ori($file, $doc_name, $period, $supplier);
                break;
            case 300000734: //富邦人壽
                $array = Fubon::bonus_ori($file, $doc_name, $period, $supplier);
                break;
        }

        ini_set("memory_limit", "1000M");
        $chunk = array_chunk($array, 1000);
        foreach ($chunk as $chunk) {
            table_insurance_ori_bonus::insert($chunk);
        }

        $today = date('Y_m_d');
        //uplaod to server
        $upload_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_bonus" . DIRECTORY_SEPARATOR . $today;
        exec("mkdir {$upload_path}");
        $upload_file_path = $upload_path . DIRECTORY_SEPARATOR . $doc_name;
        move_uploaded_file($file_path, $upload_file_path);

        echo json_encode("success!");
    }

    public function rules(Request $request)
    {
        //記得要先輸出成CSV
        $supplier = $request->supplier;
        $file_path = $request->file->path();
        $doc_name = $request->file->getClientOriginalName();

        $file = file_get_contents($file_path);
        $array = array();
        foreach (explode("\n", $file) as $file_key => $file_value) {
            $data = explode(",", $file_value);
            if (count($data) > 38 && empty($data[3]) == false) {
                if (mb_strlen($data[2], "UTF-8") == strlen($data[2])) {

                    //period_rules
                    $rule_types = ['NNN', 'AAA', 'BBB', 'CCC'];
                    foreach ($rule_types as $rule_types) {
                        $start_period = array_search($rule_types, $data);
                        if ($start_period != false) {
                            $rules_start_period = $start_period - 16;
                            $rules_types_insert = $rule_types;
                        }
                    }

                    //product_code
                    $product_code_pre = $data[7];
                    switch ($product_code_pre) {
                        case ($product_code_pre == '除'):
                            $product_code = 'exception';
                            break;
                        case ($product_code_pre == '全'):
                            $product_code = 'all_pro';
                            break;
                        default:
                            $product_code = $data[8];
                            break;
                    }

                    $first_period = ($data[17] == "-") ? '0' : $data[17];
                    $pay_type = ($data[14] == '躉') ? 'D' : 'all';
                    $is_main = ($data[9] == '附約') ? '0' : 'all';
                    if ($data[14] == '躉') {
                        $D = 1;
                        $M = 0;
                        $Q = 0;
                        $S = 0;
                        $Y = 0;
                    } else {
                        $D = 1;
                        $M = 1;
                        $Q = 1;
                        $S = 1;
                        $Y = 1;
                    }
                    $y_period_lower_limit = (empty($data[14])) ? 0 : $data[14];
                    $y_period_upper_limit = (empty($data[15])) ? 99 : $data[15];
                    array_push($array, array(
                        "doc_name" => $doc_name,
                        "doc_date" => $data[0],
                        "doc_number" => $data[1],
                        "doc_number_leishan" => $data[2],
                        "rules_start_date" => $data[3],
                        "rules_due_date" => $data[4],
                        "auto_extension" => $data[5],
                        "supplier_code" => $supplier,
                        "supplier_name" => $data[6],
                        "product_name" => $data[7],
                        "product_code" => $product_code,
                        "pro_keyword" => $data[9],
                        "currency" => $data[10],
                        "main_keyword1" => $data[11],
                        "main_keyword2" => $data[12],
                        "is_same_as_main_period" => $data[13],
                        "y_period_lower_limit" => $y_period_lower_limit,
                        "y_period_upper_limit" => $y_period_upper_limit,
                        "pay_type" => $pay_type,
                        "is_main" => $is_main,
                        "premium_type" => $data[16],
                        "rules_type" => $rules_types_insert,
                        "rules_start_period" => $rules_start_period,
                        "0" => '0',
                        "1" => $first_period,
                        "2" => $data[18],
                        "3" => $data[19],
                        "4" => $data[20],
                        "5" => $data[21],
                        "6" => $data[22],
                        "7" => $data[23],
                        "8" => $data[24],
                        "9" => $data[25],
                        "10" => $data[26],
                        "11" => $data[27],
                        "12" => $data[28],
                        "13" => $data[29],
                        "14" => $data[30],
                        "15" => $data[31],
                        "16" => $data[32],
                        "17" => $data[33],
                        "18" => $data[34],
                        "19" => $data[35],
                        "20" => $data[36],
                        "remark" => $data[38],
                        "created_at" => date('Y-m-d H:i:s'),
                        "created_by" => "jane",
                        "deleted_at" => null,
                        "deleted_by" => null,
                        "D" => $D,
                        "M" => $M,
                        "Q" => $Q,
                        "S" => $S,
                        "Y" => $Y,
                    ));
                }
            }
        }
        //將先前的規則改為delete
        table_supplier_bonus_doc_rules::where('supplier_code', $supplier)
            ->where('deleted_at', null)
            ->update(['deleted_at' => date('Y-m-d H:i:s'), "deleted_by" => "jane"]);

        //新增新的規則
        table_supplier_bonus_doc_rules::insert($array);

        $today = date('Y_m_d');
        //uplaod to server
        $upload_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_rules" . DIRECTORY_SEPARATOR . $today;
        exec("mkdir {$upload_path}");
        $upload_file_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_rules" . DIRECTORY_SEPARATOR . $today . DIRECTORY_SEPARATOR . $doc_name;
        move_uploaded_file($file_path, $upload_file_path);

        echo json_encode("success!");
    }
}
