<?php

namespace App\Http\Controllers\ins_bonus;

use App\bonus_diff;
use App\bonus_diff_exception;
use App\Http\Controllers\Controller;
use App\import_bonus_suppliers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class bonus_diff_data extends Controller
{
    public function mapping(Request $request)
    {
        date_default_timezone_set("Asia/Taipei");

        $supplier = $request->supplier;
        $start_period = $request->start_period;
        $end_period = $request->end_period;
        $last_month = $this::date_formate($start_period)[-1];
        $next_ten_month = $this::date_formate($end_period)[10];

        $prediction = $this::import_bonus_calculation($supplier, $start_period, $end_period);
        $original = $this::import_bonus_suppliers($supplier, $last_month, $next_ten_month);
        $original_exception = $this::import_bonus_suppliers($supplier, $start_period, $end_period);

        //DB:bonus_diff
        $array_insert = $this::finding_mapping_array($prediction, $original, $supplier);
        $this::chunk_array_and_insert_table($array_insert, 'bonus_diff');

        //DB:bonus_diff_exception
        $exception_array = $this::original_exception($original_exception, $prediction);
        $this::chunk_array_and_insert_table($exception_array, 'bonus_diff_exception');

        return response()->json(['success!']);

    }

    private function import_bonus_calculation($supplier, $start_period, $end_period)
    {
        ini_set("memory_limit", "3000M");
        $calculation_data = DB::connection('mysql')
            ->select("SELECT
            sum(bonus) bonus,
            ins_no,
            pay_type,
            period,
            GROUP_CONCAT(remark) remark
        FROM (
            SELECT
                bonus,
                ins_no,
                CONCAT(pro_name, ':', remark) remark,
                pro_name,
                period,
                pay_type
            FROM
                `ins_details_calculation`
            WHERE
                `period` >= '{$start_period}'
                AND `period` <= '{$end_period}'
                AND `sup_code` = '{$supplier}'
                AND `is_expired` != 2
                AND `bonus` != 0
            GROUP BY
                ins_no,
                pro_name,
                period,
                remark,
                bonus,
                pay_type) remark
        GROUP BY
            ins_no,
            period,
            pay_type");

        return $calculation_data;
    }

    private function import_bonus_suppliers($supplier, $start_period, $end_period)
    {
        ini_set("memory_limit", "3000M");
        $supplier_data = DB::connection('mysql')
            ->select("SELECT
            period,
            supplier_code,
            ins_no,
            sum(bonus) bonus
        FROM
            import_bonus_suppliers
        WHERE
            supplier_code = '{$supplier}'
            AND `period` >= '{$start_period}'
            AND `period` <= '{$end_period}'
        GROUP BY
            `period`,
            supplier_code,
            ins_no
        ORDER BY
            `period`");

        return $supplier_data;
    }

    private function date_formate($period)
    {
        $year = substr($period, 0, 4);
        $month = substr($period, 4, 2);

        $date = date("Ymd", strtotime("{$year}/{$month}/01"));

        $periods = array(
            -1 => date("Ym", strtotime("-1 month", strtotime($date))),
            0 => date("Ym", strtotime($date)),
            1 => date("Ym", strtotime("+1 month", strtotime($date))),
            2 => date("Ym", strtotime("+2 month", strtotime($date))),
            3 => date("Ym", strtotime("+3 month", strtotime($date))),
            4 => date("Ym", strtotime("+4 month", strtotime($date))),
            5 => date("Ym", strtotime("+5 month", strtotime($date))),
            6 => date("Ym", strtotime("+6 month", strtotime($date))),
            7 => date("Ym", strtotime("+7 month", strtotime($date))),
            8 => date("Ym", strtotime("+8 month", strtotime($date))),
            9 => date("Ym", strtotime("+9 month", strtotime($date))),
            10 => date("Ym", strtotime("+10 month", strtotime($date))),
            11 => date("Ym", strtotime("+11 month", strtotime($date))),
            12 => date("Ym", strtotime("+12 month", strtotime($date))),
        );

        return $periods;
    }

    private function array_push_mapping($array_insert, $ins_no, $supplier,
        $period, $bonus, $period_ori, $bonus_ori, $remark, $remark_ic,
        $created_by, $pay_type, $bonus_diff) {
        array_push($array_insert, array(
            "ins_no" => $ins_no,
            "sup_code" => $supplier,
            "pay_type" => $pay_type,
            "period_cal" => $period,
            "bonus_cal" => $bonus,
            "period_ori" => $period_ori,
            "bonus_ori" => $bonus_ori,
            "bonus_diff" => $bonus_diff,
            "remark" => $remark,
            "remark_ic" => $remark_ic,
            "created_at" => date('Y-m-d H:i:s'),
            "created_by" => $created_by,
            "deleted_at" => null,
            "deleted_by" => null,
        ));

        return $array_insert;
    }

    private function diff_insert_condition($remark, $array_insert, $ins_no,
        $supplier, $period, $bonus, $pay_type, $period_ori, $bonus_ori, $remark_ic) {

        switch (count($remark)) {
            case 0: //如果前1後2沒有資料
                $bonus_diff = 0 - (int) $bonus;
                $array_insert_after = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                    null, null, null, $remark_ic, 'jane', $pay_type, $bonus_diff);
                break;
            case 1: //不用寫remark
                $remark = null;
                $bonus_diff = $bonus_ori - (int) $bonus;
                $array_insert_after = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                    $period_ori, $bonus_ori, $remark, $remark_ic, 'jane', $pay_type, $bonus_diff);
                break;
            default:
                $remark = implode("\n", $remark);
                $bonus_diff = $bonus_ori - (int) $bonus;
                $array_insert_after = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                    $period_ori, $bonus_ori, $remark, $remark_ic, 'jane', $pay_type, $bonus_diff);
                break;
        }

        return $array_insert_after;
    }

    private function bonus_period_ranging($period_formate, $original, $ins_no_arr,
        $last_period, $min_upper_period, $max_upper_period, $array_insert, $ins_no,
        $supplier, $period, $bonus, $pay_type, $remark_ic) {

        $bonus_ori = 0;
        $period_ori = '';
        $remark = array();

        //example:檢查前1後2有沒有資料
        foreach ($period_formate as $period_formate_key => $period_formate_value) {
            if ($period_formate_key == $last_period ||
                ($period_formate_key >= $min_upper_period && $period_formate_key <= $max_upper_period)) {

                $month_arr = array_keys(array_column($original, 'period'), $period_formate_value);
                $intersect = array_values(array_intersect($ins_no_arr, $month_arr));

                if (!empty($intersect)) {
                    $bonus_ori = $bonus_ori + (float) $original[$intersect[0]]->bonus;
                    $period_ori = (string) $original[$intersect[0]]->period;
                    array_push($remark, $period_ori . ':' . $original[$intersect[0]]->bonus);
                }

            }
        }

        $array_insert = $this::diff_insert_condition($remark, $array_insert, $ins_no,
            $supplier, $period, $bonus, $pay_type, $period_ori, $bonus_ori, $remark_ic);

        return $array_insert;

    }

    private function finding_mapping_array($prediction, $original, $supplier)
    {
        $array_insert = [];

        foreach ($prediction as $prediction_arr) {
            $pay_type = $prediction_arr->pay_type;
            $period = $prediction_arr->period;
            $ins_no = $prediction_arr->ins_no;
            $bonus = $prediction_arr->bonus;
            $remark_ic = $prediction_arr->remark;

            $period_formate = $this::date_formate($period);

            //判斷當月有沒有資料
            $ins_no_arr = array_keys(array_column($original, 'ins_no'), $ins_no);
            $period_arr = array_keys(array_column($original, 'period'), $period);
            $intersect = array_values(array_intersect($ins_no_arr, $period_arr));

            if ($intersect) { //當月有該保單的資料
                $bonus_diff = (int) $original[$intersect[0]]->bonus - (int) $bonus;
                $array_insert = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                    $original[$intersect[0]]->period, $original[$intersect[0]]->bonus,
                    null, $remark_ic, 'jane', $pay_type, $bonus_diff);

            } elseif (!empty($ins_no_arr)) { //當月沒有該保單的資料，但是資料中有該保單的資料
                switch ($pay_type) {
                    case 'M': //月繳：比對當月 //如果當月沒有，那就是沒有
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus, null, null, null, $remark_ic, 'jane', $pay_type, $bonus_diff);
                        break;
                    case 'D': //比對當月 //如果當月沒有，那就是沒有
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus, null, null, null, $remark_ic, 'jane', $pay_type, $bonus_diff);
                        break;
                    case 'Y': //年繳：前1後10
                        //前1後2(大於等於1且小於等於10)

                        $array_insert = $this::bonus_period_ranging($period_formate, $original, $ins_no_arr,
                            -1, 1, 10, $array_insert, $ins_no, $supplier, $period, $bonus, $pay_type, $remark_ic);
                        break;
                    case 'S': //半年繳：前1後4
                        //前1後2(大於等於1且小於等於4)
                        $array_insert = $this::bonus_period_ranging($period_formate, $original, $ins_no_arr,
                            -1, 1, 4, $array_insert, $ins_no, $supplier, $period, $bonus, $pay_type, $remark_ic);
                        break;
                    case 'Q': //季繳：前1後2
                        //前1後2(大於等於1且小於等於2)
                        $array_insert = $this::bonus_period_ranging($period_formate, $original, $ins_no_arr,
                            -1, 1, 2, $array_insert, $ins_no, $supplier, $period, $bonus, $pay_type, $remark_ic);
                        break;
                    default:
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                            null, null, null, $remark_ic, 'jane', $pay_type, $bonus_diff);
                }

            } else { //origin中完全沒有該保單的資料
                $bonus_diff = 0 - (int) $bonus;
                $array_insert = $this::array_push_mapping($array_insert, $ins_no, $supplier, $period, $bonus,
                    null, null, null, $remark_ic, 'jane', $pay_type, $bonus_diff);

            }
        }

        return $array_insert;
    }

    private function original_exception($original_exception, $prediction)
    {
        $exception_array = [];
        foreach ($original_exception as $original_exception) {
            $period_ori = $original_exception->period;
            $ins_no_ori = $original_exception->ins_no;
            $bonus_ori = $original_exception->bonus;
            $supplier = $original_exception->supplier_code;

            $ins_no_arr = array_keys(array_column($prediction, 'ins_no'), $ins_no_ori);
            $period_arr = array_keys(array_column($prediction, 'period'), $period_ori);
            $intersect = array_values(array_intersect($ins_no_arr, $period_arr));

            if (!$intersect) {
                array_push($exception_array,
                    array(
                        "ins_no" => $ins_no_ori,
                        "sup_code" => $supplier,
                        "period_ori" => $period_ori,
                        "bonus_ori" => $bonus_ori,
                        "created_at" => date('Y:m:d H:m:s'),
                        "created_by" => 'Jane',
                        "deleted_at" => null,
                        "deleted_by" => null,
                    ));
            }

        }
        return $exception_array;
    }

    private function chunk_array_and_insert_table($array, $table)
    {
        ini_set("memory_limit", "3000M");
        $chunk = array_chunk($array, 1000);

        switch ($table) {
            case 'bonus_diff':
                foreach ($chunk as $chunk) {
                    bonus_diff::insert($chunk);
                }
                break;
            case 'bonus_diff_exception':
                foreach ($chunk as $chunk) {
                    bonus_diff_exception::insert($chunk);
                }
                break;
        }
    }
}
