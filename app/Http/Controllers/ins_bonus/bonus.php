<?php

namespace App\Http\Controllers\ins_bonus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\AIA;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\Farglory;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\Fubon;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\ShinKong;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\TaiwanLife;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\TransGlobe;
use App\Http\Controllers\ins_bonus\bonus_from_suppliers_function\Yuanta;
use App\Imports\UsersImport;
use App\import_bonus_doc_rules;
use App\import_bonus_suppliers;
use Illuminate\Http\Request;
use \PhpOffice\PhpSpreadsheet\Shared\Date;

class bonus extends Controller
{
    public function supplier(Request $request)
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
            case 300006376: //元大人壽
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = Yuanta::bonus_ori($data, $doc_name, $period, $supplier);
                break;
            case 300000722: //台灣人壽
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = TaiwanLife::bonus_ori($data, $doc_name, $period, $supplier);
                break;
            case 300000749: //新光人壽
                $array = ShinKong::bonus_ori($file, $doc_name, $period, $supplier);
                break;
            case 300000717: //友邦人壽
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = AIA::bonus_ori($data, $doc_name, $period, $supplier);
                break;
            default:
                return response()->json(['Failed!']);
                exit;
        }

        ini_set("memory_limit", "1000M");
        $chunk = array_chunk($array, 1000);
        foreach ($chunk as $chunk) {
            import_bonus_suppliers::insert($chunk);
        }

        $today = date('Y_m_d');
        //uplaod to server
        $upload_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_bonus" . DIRECTORY_SEPARATOR . $today;
        exec("mkdir {$upload_path}");
        $upload_file_path = $upload_path . DIRECTORY_SEPARATOR . $doc_name;
        move_uploaded_file($file_path, $upload_file_path);

        return response()->json(['success!']);
    }

    public function rules(Request $request)
    {
        $supplier = $request->supplier;
        $file_path = $request->file->path();
        $doc_name = $request->file->getClientOriginalName();

        $path1 = $request->file('file')->store('temp');
        $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
        $datas = (new UsersImport)->toArray($path);

        $array = array();
        foreach ($datas[0] as $file_key => $file_value) {

            if (count($file_value) > 50 && empty($file_value[3]) == false
                && mb_strlen($file_value[3], "UTF-8") == strlen($file_value[3])) {

                $data = $file_value;

                //currency
                $AUD = 1;
                $CAD = 1;
                $EUR = 1;
                $GBP = 1;
                $HKD = 1;
                $JPY = 1;
                $NTD = 1;
                $NZD = 1;
                $RMB = 1;
                $USD = 1;
                $ZAR = 1;

                if ($data[10] == '外') {
                    $NTD = 0;
                }

                //period_rules
                if ($data[18] == 0) {
                    $data[18] = "全";
                }
                if ($data[14] == 0 && $data[14] !== '躉') {
                    $data[14] = "全";
                }

                $rule_types = ['NNN', 'AAA', 'BBB', 'CCC'];

                $empty_detecter = array();
                $rules_types_detecter = array();
                foreach ($rule_types as $rule_types) {
                    $start_period = array_keys($data, $rule_types);
                    array_push($empty_detecter, $start_period);

                    if (!empty($start_period)) {
                        $arr_num = (int) count($start_period) - 1;
                        $rules_start_period = $start_period[$arr_num] - 20;
                        $rules_types_insert = $rule_types;
                        if ($rules_start_period > 0) {
                            array_push($rules_types_detecter, array(
                                'rules_start_period' => $rules_start_period,
                                'rules_types_insert' => $rules_types_insert,
                            ));
                        }

                    }
                }

                if (count(array_filter($empty_detecter)) == 0) {
                    $rules_start_period = 0;
                    $rules_types_insert = 'NNN';
                } else {
                    $rules_start_period = $rules_types_detecter[0]["rules_start_period"];
                    $rules_types_insert = $rules_types_detecter[0]["rules_types_insert"];
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

                $first_period = ($data[21] == "-") ? '0' : $data[21];

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
                $y_period_lower_limit = (empty($data[14]) || $data[14] == '全') ? 0 : $data[14];
                $y_period_upper_limit = (empty($data[15])) ? 99 : $data[15];

                $doc_date_pre = is_null($data[0]) ? null : Date::excelToDateTimeObject($data[0]);
                $doc_date = is_null($data[0]) ? null : $doc_date_pre->format('Y-m-d');

                $rules_start_date_pre = is_null($data[3]) ? null : Date::excelToDateTimeObject($data[3]);
                $rules_start_date = is_null($data[3]) ? null : $rules_start_date_pre->format('Y-m-d');

                $rules_due_date_pre = is_null($data[4]) ? null : Date::excelToDateTimeObject($data[4]);
                $rules_due_date = is_null($data[4]) ? null : $rules_start_date_pre->format('Y-m-d');

                $benefit_lower_limit = ($data[16] == '全') ? 0 : $data[16];
                $benefit_upper_limit = ($data[17] == '全') ? 0 : $data[17];

                $insured_age_lower_limit = ($data[18] == '全') ? 0 : $data[18];
                $insured_age_upper_limit = ($data[19] == '全') ? 99 : $data[19];

                array_push($array, array(
                    "doc_name" => $doc_name,
                    "doc_date" => $doc_date,
                    "doc_number" => $data[1],
                    "doc_number_leishan" => $data[2],
                    "rules_start_date" => $rules_start_date,
                    "rules_due_date" => $rules_due_date,
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
                    "benefit_lower_limit" => $benefit_lower_limit,
                    "benefit_upper_limit" => $benefit_upper_limit,
                    "insured_age_lower_limit" => $insured_age_lower_limit,
                    "insured_age_upper_limit" => $insured_age_upper_limit,
                    "pay_type" => $pay_type,
                    "is_main" => $is_main,
                    "premium_type" => $data[20],
                    "rules_type" => $rules_types_insert,
                    "rules_start_period" => $rules_start_period,
                    "0" => '0',
                    "1" => $first_period,
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
                    "remark" => $data[42],
                    "created_at" => date('Y-m-d H:i:s'),
                    "created_by" => "jane",
                    "deleted_at" => null,
                    "deleted_by" => null,
                    "D" => $D,
                    "M" => $M,
                    "Q" => $Q,
                    "S" => $S,
                    "Y" => $Y,
                    "AUD" => $AUD,
                    "CAD" => $CAD,
                    "EUR" => $EUR,
                    "GBP" => $GBP,
                    "HKD" => $HKD,
                    "JPY" => $JPY,
                    "NTD" => $NTD,
                    "NZD" => $NZD,
                    "RMB" => $RMB,
                    "USD" => $USD,
                    "ZAR" => $ZAR,
                ));

            }
        }
        //將先前的規則改為delete
        import_bonus_doc_rules::where('supplier_code', $supplier)
            ->where('deleted_at', null)
            ->update(['deleted_at' => date('Y-m-d H:i:s'), "deleted_by" => "jane"]);

        //新增新的規則
        import_bonus_doc_rules::insert($array);

        $today = date('Y_m_d');
        //uplaod to server
        $upload_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_rules" . DIRECTORY_SEPARATOR . $today;
        exec("mkdir {$upload_path}");
        $upload_file_path = env("import_file_path") . DIRECTORY_SEPARATOR . $supplier . DIRECTORY_SEPARATOR . "supplier_rules" . DIRECTORY_SEPARATOR . $today . DIRECTORY_SEPARATOR . $doc_name;
        move_uploaded_file($file_path, $upload_file_path);

        return response()->json(['success!']);

    }
}
