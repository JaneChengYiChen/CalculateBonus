<?php

namespace App\Http\Controllers\ins_bonus;

use App\bonus_diff;
use App\bonus_diff_exception;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DiffController extends Controller
{
    public function mapping(Request $request)
    {
        date_default_timezone_set("Asia/Taipei");

        $supplier = $request->supplier;
        $start_period = $request->start_period;
        $end_period = $request->end_period;
        $last_month = $this::dateFormate($start_period)[-1];
        $next_ten_month = $this::dateFormate($end_period)[10];

        $prediction = $this::importBonusCalculation($supplier, $start_period, $end_period);
        $original = $this::importBonusSuppliers($supplier, $last_month, $next_ten_month);
        $originalException = $this::importBonusSuppliers($supplier, $start_period, $end_period);

        //DB:bonus_diff
        $array_insert = $this::findingMappingArray($prediction, $original, $supplier);
        $this::chunkArrayAndInsertTable($array_insert, 'bonus_diff');

        //DB:bonus_diff_exception
        $exception_array = $this::originalException($originalException, $prediction);
        $this::chunkArrayAndInsertTable($exception_array, 'bonus_diff_exception');

        return response()->json(['success!']);
    }

    private function importBonusCalculation($supplier, $start_period, $end_period)
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

    private function importBonusSuppliers($supplier, $start_period, $end_period)
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

    private function dateFormate($period)
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

    private function arrayPushMapping(
        $array_insert,
        $ins_no,
        $supplier,
        $period,
        $bonus,
        $period_ori,
        $bonus_ori,
        $remark,
        $remark_ic,
        $created_by,
        $pay_type,
        $bonus_diff
    ) {
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

    private function diffInsertCondition(
        $remark,
        $array_insert,
        $ins_no,
        $supplier,
        $period,
        $bonus,
        $pay_type,
        $period_ori,
        $bonus_ori,
        $remark_ic
    ) {

        switch (count($remark)) {
            case 0: //?????????1???2????????????
                $bonus_diff = 0 - (int) $bonus;
                $array_insert_after = $this::arrayPushMapping(
                    $array_insert,
                    $ins_no,
                    $supplier,
                    $period,
                    $bonus,
                    null,
                    null,
                    null,
                    $remark_ic,
                    'jane',
                    $pay_type,
                    $bonus_diff
                );
                break;
            case 1: //?????????remark
                $remark = null;
                $bonus_diff = $bonus_ori - (int) $bonus;
                $array_insert_after = $this::arrayPushMapping(
                    $array_insert,
                    $ins_no,
                    $supplier,
                    $period,
                    $bonus,
                    $period_ori,
                    $bonus_ori,
                    $remark,
                    $remark_ic,
                    'jane',
                    $pay_type,
                    $bonus_diff
                );
                break;
            default:
                $remark = implode(",", $remark);
                $bonus_diff = $bonus_ori - (int) $bonus;
                $array_insert_after = $this::arrayPushMapping(
                    $array_insert,
                    $ins_no,
                    $supplier,
                    $period,
                    $bonus,
                    $period_ori,
                    $bonus_ori,
                    $remark,
                    $remark_ic,
                    'jane',
                    $pay_type,
                    $bonus_diff
                );
                break;
        }

        return $array_insert_after;
    }

    private function bonusPeriodRanging(
        $period_formate,
        $original,
        $ins_no_arr,
        $last_period,
        $min_upper_period,
        $max_upper_period,
        $array_insert,
        $ins_no,
        $supplier,
        $period,
        $bonus,
        $pay_type,
        $remark_ic
    ) {
        $bonus_ori = 0;
        $period_ori = '';
        $remark = array();

        //example:?????????1???2???????????????
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

        $array_insert = $this::diffInsertCondition(
            $remark,
            $array_insert,
            $ins_no,
            $supplier,
            $period,
            $bonus,
            $pay_type,
            $period_ori,
            $bonus_ori,
            $remark_ic
        );

        return $array_insert;
    }

    private function findingMappingArray($prediction, $original, $supplier)
    {
        $array_insert = [];

        foreach ($prediction as $prediction_arr) {
            $pay_type = $prediction_arr->pay_type;
            $period = $prediction_arr->period;
            $ins_no = $prediction_arr->ins_no;
            $bonus = $prediction_arr->bonus;
            $remark_ic = $prediction_arr->remark;

            $period_formate = $this::dateFormate($period);

            //???????????????????????????
            $ins_no_arr = array_keys(array_column($original, 'ins_no'), $ins_no);
            $period_arr = array_keys(array_column($original, 'period'), $period);
            $intersect = array_values(array_intersect($ins_no_arr, $period_arr));

            if ($intersect) { //???????????????????????????
                $bonus_diff = (int) $original[$intersect[0]]->bonus - (int) $bonus;
                $array_insert = $this::arrayPushMapping(
                    $array_insert,
                    $ins_no,
                    $supplier,
                    $period,
                    $bonus,
                    $original[$intersect[0]]->period,
                    $original[$intersect[0]]->bonus,
                    null,
                    $remark_ic,
                    'jane',
                    $pay_type,
                    $bonus_diff
                );
            } elseif (!empty($ins_no_arr)) { //?????????????????????????????????????????????????????????????????????
                switch ($pay_type) {
                    case 'M': //????????????????????? //????????????????????????????????????
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::arrayPushMapping(
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            null,
                            null,
                            null,
                            $remark_ic,
                            'jane',
                            $pay_type,
                            $bonus_diff
                        );
                        break;
                    case 'D': //???????????? //????????????????????????????????????
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::arrayPushMapping(
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            null,
                            null,
                            null,
                            $remark_ic,
                            'jane',
                            $pay_type,
                            $bonus_diff
                        );
                        break;
                    case 'Y': //????????????1???10
                        //???1???2(????????????1???????????????10)

                        $array_insert = $this::bonusPeriodRanging(
                            $period_formate,
                            $original,
                            $ins_no_arr,
                            -1,
                            1,
                            10,
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            $pay_type,
                            $remark_ic
                        );
                        break;
                    case 'S': //???????????????1???4
                        //???1???2(????????????1???????????????4)
                        $array_insert = $this::bonusPeriodRanging(
                            $period_formate,
                            $original,
                            $ins_no_arr,
                            -1,
                            1,
                            4,
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            $pay_type,
                            $remark_ic
                        );
                        break;
                    case 'Q': //????????????1???2
                        //???1???2(????????????1???????????????2)
                        $array_insert = $this::bonusPeriodRanging(
                            $period_formate,
                            $original,
                            $ins_no_arr,
                            -1,
                            1,
                            2,
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            $pay_type,
                            $remark_ic
                        );
                        break;
                    default:
                        $bonus_diff = 0 - (int) $bonus;
                        $array_insert = $this::arrayPushMapping(
                            $array_insert,
                            $ins_no,
                            $supplier,
                            $period,
                            $bonus,
                            null,
                            null,
                            null,
                            $remark_ic,
                            'jane',
                            $pay_type,
                            $bonus_diff
                        );
                }
            } else { //origin?????????????????????????????????
                $bonus_diff = 0 - (int) $bonus;
                $array_insert = $this::arrayPushMapping(
                    $array_insert,
                    $ins_no,
                    $supplier,
                    $period,
                    $bonus,
                    null,
                    null,
                    null,
                    $remark_ic,
                    'jane',
                    $pay_type,
                    $bonus_diff
                );
            }
        }

        return $array_insert;
    }

    private function originalException($originalException, $prediction)
    {
        $exception_array = [];
        foreach ($originalException as $originalException) {
            $period_ori = $originalException->period;
            $ins_no_ori = $originalException->ins_no;
            $bonus_ori = $originalException->bonus;
            $supplier = $originalException->supplier_code;

            $ins_no_arr = array_keys(array_column($prediction, 'ins_no'), $ins_no_ori);
            $period_arr = array_keys(array_column($prediction, 'period'), $period_ori);
            $intersect = array_values(array_intersect($ins_no_arr, $period_arr));

            if (!$intersect) {
                array_push(
                    $exception_array,
                    array(
                        "ins_no" => $ins_no_ori,
                        "sup_code" => $supplier,
                        "period_ori" => $period_ori,
                        "bonus_ori" => $bonus_ori,
                        "created_at" => date('Y-m-d H:m:s'),
                        "created_by" => 'Jane',
                        "deleted_at" => null,
                        "deleted_by" => null,
                    )
                );
            }
        }
        return $exception_array;
    }

    private function chunkArrayAndInsertTable($array, $table)
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
