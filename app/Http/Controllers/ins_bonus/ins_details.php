<?php

namespace App\Http\Controllers\ins_bonus;

use App\Http\Controllers\Controller;
use App\import_bonus_doc_rules;
use App\ins_details_calculation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ins_details extends Controller
{

    public function query(Request $request)
    {
        $supplier = $request->supplier;
        $period = $request->period;
        $date = $request->date;

        $cal_month = (int) \substr($period, -2, 2);
        $ins_details = $this::get_ins_details_from_pks($supplier, $period, $date);
        $ins_rules = $this::get_official_rules($supplier);
        $ins_detail_insert_arr = [];
        foreach ($ins_details as $ins_details_keys) {
            $ins_code = $ins_details_keys->Ins_Code; //商品編號
            $YPeriod = (int) $ins_details_keys->YPeriod; //應繳年期
            $Effe_Date = strtotime($ins_details_keys->Effe_Date); //生效日
            $FYP = (int) $ins_details_keys->FYP; //FYP
            $diff = (int) $ins_details_keys->diff; //目前位於第幾個繳費年期
            $PayType = $ins_details_keys->PayType; //繳別，D為躉繳
            $is_main = (int) $ins_details_keys->Main; //主附約
            $main_period = $ins_details_keys->main_period; //主約繳費年期
            $Ins_No = $ins_details_keys->Ins_No; //保單號碼
            $first_pay_month = (int) $ins_details_keys->first_pay_month; //首年繳費的月份
            $first_pay_period = $ins_details_keys->first_pay_period; //首年繳費
            $pro_name = $ins_details_keys->Pro_Name; //商品名稱
            $remark = $ins_details_keys->remark; //備註

            switch ($supplier) {
                case ($supplier == '300000734'): //富邦人壽，取前四碼
                    $ins_code_search = substr($ins_code, 0, 4);
                    break;
                default:
                    $ins_code_search = substr($ins_code, 0, 3);
            }

            $lower_limit = range(0, $YPeriod);
            $upper_limit = range($YPeriod, 100);

            //商品、年限上限、年限下限、繳別之集合
            $product_arr_initial = array_keys(array_column($ins_rules, 'product_code'), $ins_code_search);

            $exception_mark = empty($product_arr_initial) ? 1 : 0; //is exception

            $blank_rules = [
                [
                    "id" => null,
                ],
            ];

            //躉繳且首佣時間不等於該計算時間
            if (($PayType == 'D' && ($period != $first_pay_period))
                //季繳且首佣月份減該計算月份後除以3之餘數不等於零
                 || ($PayType == 'Q' && (($first_pay_month - $cal_month) % 3) != 0)
                //半年繳且首佣月份減該計算月份後除以6之餘數不等於零
                 || ($PayType == 'S' && (($first_pay_month - $cal_month) % 6) != 0)
                //年繳且首佣月份不等於該計算月份
                 || ($PayType == 'Y' && ($first_pay_month != $cal_month))) {
                $is_expired = 2;
                $rate = 0;
                $rule_arr = $blank_rules;

            } else {
                //caseA:至今繳費年期 > 應繳年期，保單可能符合「是否改成與主約繳費年期一致」的保單公文
                if ($diff > $YPeriod && !empty($product_arr_initial)) {
                    $rule_arr = $this::rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, 'follow_main_period', $PayType, $pro_name);

                    //如果主約繳費年期大於保單到現在的時間，表示可以依照主約的繳費年期計算
                    if (empty($rule_arr) != true && $main_period >= $diff) {
                        $is_expired = 0;
                        $rules_start_period = (int) $rule_arr[0]["rules_start_period"];
                        //如果客制規則起始年大於現在繳費年，直接使用欄位
                        if ($rules_start_period > $diff) {
                            $rate = $rule_arr[0][$diff];
                        }
                        //如果客制規則起始年小於或等於現在繳費年，計算
                        else {
                            $rate = $this::counting_rate($rule_arr);
                        }
                    }
                    //如果主約繳費年期小於保單到現在的時間，或未符合「是否改成與主約繳費年期一致」的保單公文，表示不用計算
                    else {
                        $is_expired = 1;
                        $rate = 0;
                        $rule_arr = $blank_rules;
                    }
                }
                //caseB:至今繳費年期 > 應繳年期，且保單未於商品編號中，表示不可能符合「是否改成與主約繳費年期一致」
                if ($diff > $YPeriod && empty($product_arr_initial)) { //不用計算
                    $is_expired = 1;
                    $rate = 0;
                    $rule_arr = $blank_rules;
                }
                //caseC:如果至今繳費年期小於應繳年期，表示保單還沒過期
                else {
                    $is_expired = 0;
                    $rule_arr = $this::is_contract($is_main, $product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, $PayType, 'exception', $pro_name);

                    //case1: 公文有商品，不過日期不符合公文日期 ==> 納入除
                    //納入除後，日期不符合/條件不符合 ==> bonus rate is 0
                    //納入除後，日期、條件符合 ==> depends y period
                    //case2: 本身要納入 exception，但日期不符合 ==> bonus rate is 0
                    if (count($rule_arr) == 0) {
                        if ($exception_mark == 1) { //如果本身是exception
                            $rate = 0;
                            $rule_arr = $blank_rules;
                        } else {
                            $product_arr_initial = [];
                            $rule_arr = $this::rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, 'exception', $PayType, $pro_name);

                            if (empty($rule_arr)) {
                                $rate = 0;
                            } else {
                                $rules_start_period = (int) $rule_arr[0]["rules_start_period"];
                                //如果客制規則起始年大於現在繳費年，表示直接使用欄位計算
                                if ($rules_start_period > $diff) {
                                    $rate = $rule_arr[0][$diff];
                                }
                                //如果客制規則起始年小於或等於現在繳費年，計算規則
                                else {
                                    $rate = $this::counting_rate($rule_arr);
                                }
                            }

                            $rule_arr = empty($rule_arr) ? $blank_rules : $rule_arr;

                        }
                    } else {
                        $rules_start_period = (int) $rule_arr[0]["rules_start_period"];
                        //如果客制規則起始年大於現在繳費年，表示要依據條件規則
                        if ($rules_start_period > $diff) {
                            $rate = $rule_arr[0][$diff];
                        }
                        //如果客制規則起始年小於或等於現在繳費年，直接使用欄為計算
                        else {
                            $rate = $this::counting_rate($rule_arr);
                        }
                    }
                }
            }

            //如果是月繳，FYP則要除以2
            if ($PayType == 'M') {
                $bonus = ((float) $FYP * (float) $rate) / 2;
            } else {
                $bonus = (float) $FYP * (float) $rate;
            }

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
                "rfyp" => $ins_details_keys->FYP,
                "fyb" => $ins_details_keys->FYB,
                "fya" => $ins_details_keys->FYA,
                "ins_code" => $ins_details_keys->Ins_Code,
                "pro_name" => $ins_details_keys->Pro_Name,
                "ins_type" => $ins_details_keys->InsType,
                "fullname" => $ins_details_keys->FullName,
                "rate" => $ins_details_keys->crcrate,
                "recent_pay_period" => $ins_details_keys->diff,
                "bonus" => $bonus,
                "period" => $period,
                "is_expired" => $is_expired,
                "doc_id" => $rule_arr[0]["id"],
                "created_at" => date('Y-m-d H:i:s'),
                "created_by" => "jane",
                "sup_code" => $supplier,
                "remark" => $remark,
            ]);

        }
        ini_set("memory_limit", "3000M");
        $chunk = array_chunk($ins_detail_insert_arr, 50);

        foreach ($chunk as $chunk) {
            ins_details_calculation::insert($chunk);
        }

        echo json_encode("success!");

    }

    private function get_ins_details_from_pks($supplier, $period, $date)
    {
        ini_set("memory_limit", "3000M");
        $data = DB::connection('sqlsrv')
            ->select("SELECT
            ins.code,
            ic.Rec_No,
            ins.Ins_No,
            ins.Receive_Date,
            ins.Effe_Date,
            ins.PayType,
            ins.CRC,
            ic.crcrate,
            ins.Handle,
            ins.Void,
            ic.Main,
            ic.YPeriod,
            ic.remark,
            (
                SELECT
                    TOP 1 Ins_Content.YPeriod
                FROM
                    Ins_Content
                LEFT JOIN Insurance ON Ins_Content.MainCode = Insurance.Code
            WHERE
                ins.Ins_No = Insurance.Ins_No
                AND ins.code = Insurance.Code
                AND Ins_Content.Main = 1) AS main_period, ic.FYP * ic.crcrate FYP, ic.FYB, ic.FYA, p.Ins_Code, p.Pro_Name, p.InsType, p.FullName, (DATEDIFF(Month, ins.Effe_Date, '{$date}') / 12) + 1 diff, F.first_pay_period,
            RIGHT(F.first_pay_period, 2) first_pay_month
        FROM
            Insurance ins
            LEFT JOIN (
                SELECT
                    b.CNO,
                    a.Main,
                    a.Rec_No,
                    a.remark,
                    a.MainCode,
                    b.Ins_No,
                    b.Receive_Date,
                    b.Effe_Date,
                    b.PDate,
                    b.PayType,
                    b.SupCode,
                    f.Alias AS Supplier,
                    a.Pro_No,
                    d.Pro_Name,
                    d.InsType,
                    e.Item AS InsItem,
                    ISNULL((
                        SELECT
                            TOP 1 x.Rate FROM CRCRate x
                        WHERE
                            x.CNO = b.CNO
                            AND x.CRC = b.CRC
                            AND x. DATE <= '{$date}'
                        ORDER BY
                            x. DATE DESC), 1) AS CRCRate,
                    ROUND(a.FYP * ISNULL(d.FeatBR, 1) * (
                            CASE WHEN b.PayType = 'D' THEN
                                0.2
                            WHEN isnull(a.Type, 0) = 2 THEN
                                1
                            ELSE
                                1
                            END) * (
                            CASE WHEN b.PayType = 'M'
                                AND isnull(a.Type, 0) <> 3 THEN
                                ISNULL(f.MFeat, 2)
                            ELSE
                                1
                            END), 2) AS FYP,
                    ROUND(a.FYP * (
                            CASE WHEN b.PayType = 'M'
                                AND isnull(a.Type, 0) <> 3 THEN
                                ISNULL(f.MFeat, 2)
                            ELSE
                                1
                            END), 2) AS RFYP,
                    ROUND(a.FYB * (
                            CASE WHEN b.PayType = 'M'
                                AND isnull(a.Type, 0) <> 3 THEN
                                ISNULL(f.MFeat, 2)
                            ELSE
                                1
                            END), 2) AS FYB,
                    ROUND(a.FYA * ISNULL(d.FeatBR, 1) * (
                            CASE WHEN b.PayType = 'D' THEN
                                0.2
                            WHEN isnull(a.Type, 0) = 2 THEN
                                1
                            ELSE
                                1
                            END) * (
                            CASE WHEN b.PayType = 'M'
                                AND isnull(a.Type, 0) <> 3 THEN
                                ISNULL(f.MFeat, 2)
                            ELSE
                                1
                            END), 2) AS FYA,
                    a.Benefits,
                    a.Unit,
                    a.YPeriod,
                    b.CRC,
                    b.Audit,
                    b.Void
                FROM
                    dbo.Ins_Content AS a
                    INNER JOIN Insurance AS b ON a.MainCode = b.Code
                    INNER JOIN Company AS c ON b.CNO = c.CNO
                    LEFT OUTER JOIN Product AS d ON a.Pro_No = d.Pro_No
                    LEFT OUTER JOIN Ins_Type AS e ON d.InsType = e.Type
                    INNER JOIN Supplier AS f ON b.SupCode = f.SupCode
                WHERE
                    c.CNO <> 10000) ic ON ins.code = ic.MainCode
            LEFT JOIN Product p ON ic.Pro_No = p.Pro_No
            LEFT JOIN V_CRC crc ON ins.CRC = crc.CRC
            LEFT JOIN ( SELECT DISTINCT
                    SS_Detail.INo, MIN(ss_detail. [Period]) first_pay_period
                FROM
                    SS_Detail
                WHERE
                    ss_detail.SupCode = '{$supplier}'
                    AND ss_detail. [Period] <= '{$period}'
                GROUP BY
                    SS_Detail.INo) F ON F.INO = ins.Ins_No
            WHERE
                ins.Ins_No in( SELECT DISTINCT
                        SS_Detail.INo FROM SS_Detail
                    WHERE
                        ss_detail.SupCode = '{$supplier}'
                        AND ss_detail. [Period] <= '{$period}')
                and((DATEDIFF(Month, ins.Effe_Date, '{$date}') / 12) + 1) > 0");

        return $data;
    }

    private function get_official_rules($supplier)
    {
        $ins_rules = import_bonus_doc_rules::whereNull('deleted_at')
            ->where('supplier_code', '=', "{$supplier}")
            ->orderBy('doc_date', 'desc')
            ->get();

        return $ins_rules->toArray();
    }

    private function rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, $situation, $PayType, $pro_name)
    {
        if ($situation == 'exception') {
            $product_arr = empty($product_arr_initial)
            ? array_keys(array_column($ins_rules, 'product_code'), 'exception') : $product_arr_initial;
        }if ($situation == 'follow_main_period') {
            $product_arr = empty($product_arr_initial)
            ? array_keys(array_column($ins_rules, 'is_same_as_main_period'), 'Y') : $product_arr_initial;
        }if ($situation == 'is_main') {
            $product_arr = empty($product_arr_initial)
            ? array_keys(array_column($ins_rules, 'is_main'), '0') : $product_arr_initial;
        }

        $lower_limit_arr = array_keys(array_intersect(array_column($ins_rules, 'y_period_lower_limit'), $lower_limit));
        $upper_limit_arr = array_keys(array_intersect(array_column($ins_rules, 'y_period_upper_limit'), $upper_limit));
        $paytype_arr = array_keys(array_column($ins_rules, $PayType), '1');

        $rule_set = array_intersect($product_arr, $lower_limit_arr, $upper_limit_arr, $paytype_arr);

        $rule_arr = [];

        foreach ($rule_set as $rule_set) {
            $rule_start_date = strtotime($ins_rules[$rule_set]["rules_start_date"]);
            $rule_due_date = strtotime($ins_rules[$rule_set]["rules_due_date"]);
            $main_keyword1 = $ins_rules[$rule_set]["main_keyword1"];
            $main_keyword2 = $ins_rules[$rule_set]["main_keyword2"];

            //如果規則有關鍵字，那必須中其中一項關鍵字
            //也就是說count($keywords array) != 0
            $keywords = [];

            if ($main_keyword1 || $main_keyword2) {
                $is_keywords = 1; //規則有關鍵字
                if ($main_keyword1) {
                    if (strpos($pro_name, $main_keyword1)) {
                        array_push($keywords, '1');
                    }
                }
                if ($main_keyword2) {
                    if (strpos($pro_name, $main_keyword2)) {
                        array_push($keywords, '2');
                    }
                }

            } else {
                //如果規則沒有關鍵字，那不用判斷關鍵字的部分
                $is_keywords = 2; //規則沒有關鍵字
            }

            if ($rule_due_date) {
                if ($Effe_Date >= $rule_start_date && $Effe_Date <= $rule_due_date
                    && ($is_keywords == 2
                        || ($is_keywords == 1 && count($keywords) > 0))) {
                    array_push($rule_arr, $ins_rules[$rule_set]);
                }

            } else {
                if ($Effe_Date >= $rule_start_date
                    && ($is_keywords == 2
                        || ($is_keywords == 1 && count($keywords) > 0))) {
                    array_push($rule_arr, $ins_rules[$rule_set]);
                }
            }
        }

        return $rule_arr;
    }

    private function counting_rate($rule_arr)
    {

        $rules_type = $rule_arr[0]["rules_type"];
        $rules_start_period = (int) $rule_arr[0]["rules_start_period"];
        $rules_start_period_pre = $rules_start_period - 1;

        switch ($rules_type) {
            case ($rules_type == 'AAA'): //前一年的比率至繳費期滿
                $rate = $rule_arr[0][$rules_start_period_pre];
                break;
            case ($rules_type == 'BBB'): //前一年的比率至滿期
                $rate = $rule_arr[0][$rules_start_period_pre];
                break;
            case ($rules_type == 'CCC'): //前一年的比率至附約保障期滿
                $rate = $rule_arr[0][$rules_start_period_pre];
                break;
            case ($rules_type == 'NNN'): //當年度或之後都沒有服務津貼
                $rate = 0;
                break;
            default:
                $rate = 0;
                break;
        }

        return $rate;
    }

    private function is_contract($is_main, $product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, $PayType, $scenario, $pro_name)
    {

        if ($is_main == 1) { //如果是主約
            $rule_arr = $this::rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, $scenario, $PayType, $pro_name);

        } else { //default 全是附約
            //如果附約不在規則公文中，檢查是否有符合全附約適用的規則
            $contract_arr = $this::rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, 'is_main', $PayType, $pro_name);

            if (empty($contract_arr)) { //如果沒有適用的規則，丟進exception
                $rule_arr = $this::rules_setting($product_arr_initial, $ins_rules, $lower_limit, $upper_limit, $Effe_Date, $scenario, $PayType, $pro_name);
            } else {
                $rule_arr = $contract_arr;
            }
        }

        return $rule_arr;
    }
}
