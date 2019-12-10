<?php

namespace App\Http\Controllers;

use App\ins_details_calculation;
use App\table_supplier_bonus_doc_rules;
use Illuminate\Support\Facades\DB;

class ins_details extends Controller
{

    public function query()
    {
        $ins_details = $this::get_ins_details_from_pks();
        $ins_rules = $this::get_official_rules();

        $ins_detail_insert_arr = [];
        foreach ($ins_details as $ins_details_keys) {
            $ins_code = $ins_details_keys->Ins_Code;
            $YPeriod = (int) $ins_details_keys->YPeriod;
            $Effe_Date = strtotime($ins_details_keys->Effe_Date);
            $FYP = (int) $ins_details_keys->RFYP;
            $diff = $ins_details_keys->diff;

            $lower_limit = range(0, $YPeriod);
            $upper_liimt = range($YPeriod, 100);

            //商品、年限上限、年限下限之集合
            $product_arr_initial = array_keys(array_column($ins_rules, 'product_code'), substr($ins_code, 0, 3));

            $exception_mark = empty($product_arr_initial) ? 1 : 0; //is exception
            $rule_arr = $this::ruls_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_liimt, $Effe_Date);

            //case1: 公文有商品，不過日期不符合公文日期 ==> 納入除
            //納入除後，日期不符合/條件不符合 ==> bonus rate is 0
            //納入除後，日期、條件符合 ==> depends y period
            //case2: 本身要納入 exception，但日期不符合 ==> bonus rate is 0
            if (count($rule_arr) == 0) {
                $blank_rules = [
                    [
                        "doc_number" => null,
                        "rules_start_date" => null,
                        "rules_due_date" => null,
                    ],
                ];
                if ($exception_mark == 1) { //如果本身是exception
                    $rate = 0;
                    $rule_arr = $blank_rules;
                } else {
                    $product_arr_initial = [];
                    $rule_arr = $this::ruls_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_liimt, $Effe_Date);
                    $rate = empty($rule_arr) ? 0 : (float) $rule_arr[0][$diff];
                    $rule_arr = empty($rule_arr) ? $blank_rules : (float) $rule_arr[0][$diff];
                }
            } else {
                $rate = (float) $rule_arr[0][$diff];
            }
            $bonus = $FYP * $rate;

            array_push($ins_detail_insert_arr, [
                "code" => $ins_details_keys->code,
                "ins_no" => $ins_details_keys->Ins_No,
                "receive_date" => $ins_details_keys->Receive_Date,
                "effe_date" => $ins_details_keys->Effe_Date,
                "pay_type" => $ins_details_keys->PayType,
                "crc" => $ins_details_keys->CRC,
                "handle" => $ins_details_keys->Handle,
                "void" => $ins_details_keys->Void,
                "y_period" => $ins_details_keys->YPeriod,
                "rfyp" => $ins_details_keys->RFYP,
                "fyb" => $ins_details_keys->FYB,
                "fya" => $ins_details_keys->FYA,
                "ins_code" => $ins_details_keys->Ins_Code,
                "pro_name" => $ins_details_keys->Pro_Name,
                "ins_type" => $ins_details_keys->InsType,
                "fullname" => $ins_details_keys->FullName,
                "rate" => $ins_details_keys->rate,
                "recent_pay_period" => $ins_details_keys->diff,
                "bonus" => $bonus,
                "period" => '201808',
                "doc_number" => $rule_arr[0]["doc_number"],
                "rules_start_date" => $rule_arr[0]["rules_start_date"],
                "rules_due_date" => $rule_arr[0]["rules_due_date"],
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => "jane",
            ]);

        }
        $chunk = array_chunk($ins_detail_insert_arr, 100);
        foreach ($chunk as $chunk) {
            ins_details_calculation::insert($chunk);
        }

        echo json_encode("success!");

    }

    private function get_ins_details_from_pks()
    {
        $data = DB::connection('sqlsrv')
            ->select("SELECT
            ins.code,
            ins.Ins_No,
            ins.Receive_Date,
            ins.Effe_Date,
            ins.PayType,
            ins.CRC,
            ins.Handle,
            ins.Void,
            ic.YPeriod,
            ic.RFYP,
            ic.FYB,
            ic.FYA,
            p.Ins_Code,
            p.Pro_Name,
            p.InsType,
            p.FullName,
            CASE WHEN crc.CRC = 'NTD' THEN
                1
            ELSE
                crc.NewRate
            END rate,
            (DATEDIFF(Month, ins.Effe_Date,'2018-08-31')/12)+1 diff
        FROM
            Insurance ins
            LEFT JOIN V_LS_Ins_Content ic ON ins.code = ic.MainCode
            LEFT JOIN Product p ON ic.Pro_No = p.Pro_No
            LEFT JOIN V_CRC crc ON ins.CRC = crc.CRC
        WHERE
            ins.Ins_No in(
                SELECT
                    SS_Detail.INo FROM SS_Detail
                WHERE
                    ss_detail. [Period] = '201808'
                    AND ss_detail.SupCode = 300000737
                    AND ss_detail.YR > 1)");

        return $data;
    }

    private function get_official_rules()
    {
        $ins_rules = table_supplier_bonus_doc_rules::orderBy('doc_date', 'desc')
            ->get();

        return $ins_rules->toArray();
    }

    private function ruls_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_liimt, $Effe_Date)
    {

        $product_arr = empty($product_arr_initial)
        ? array_keys(array_column($ins_rules, 'product_code'), 'exception') : $product_arr_initial;

        $lower_limit_arr = array_keys(array_intersect(array_column($ins_rules, 'y_period_lower_limit'), $lower_limit));
        $upper_limit_arr = array_keys(array_intersect(array_column($ins_rules, 'y_period_upper_limit'), $upper_liimt));

        $rule_set = array_intersect($product_arr, $lower_limit_arr, $upper_limit_arr);

        $rule_arr = [];

        foreach ($rule_set as $rule_set) {
            $rule_start_date = strtotime($ins_rules[$rule_set]["rules_start_date"]);
            $rule_due_date = strtotime($ins_rules[$rule_set]["rules_due_date"]);

            if ($rule_due_date) {
                if ($Effe_Date >= $rule_start_date && $Effe_Date <= $rule_due_date) {
                    array_push($rule_arr, $ins_rules[$rule_set]);
                }

            } else {
                if ($Effe_Date >= $rule_start_date) {
                    array_push($rule_arr, $ins_rules[$rule_set]);
                }
            }
        }

        return $rule_arr;
    }

}
