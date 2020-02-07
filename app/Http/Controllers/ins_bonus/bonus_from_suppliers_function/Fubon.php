<?php

namespace App\Http\Controllers\ins_bonus\bonus_from_suppliers_function;

use App\Http\Controllers\Controller;

//富邦人壽
class Fubon extends Controller
{
    public static function bonusOri($file, $doc_name, $period, $supplier)
    {
        $array = array();

        foreach (explode("\n", $file) as $file_key => $file_value) {
            switch ($doc_name) {
                case (preg_match('/csv/i', strtolower($doc_name)) == 1):
                    $array = self::formatCsvFile(
                        $file_value,
                        $array,
                        $doc_name,
                        $period,
                        $supplier
                    );
                    break;
                default:
                    $file_value = mb_convert_encoding($file_value, "UTF-8", "BIG5");
                    $array = self::formatDefault(
                        $file_value,
                        $array,
                        $doc_name,
                        $period,
                        $supplier
                    );
                    break;
            }
        }
        return $array;
    }

    private static function formatCsvFile($file_value, $array, $doc_name, $period, $supplier)
    {
        $file_value = explode(',', $file_value);
        $handle_id = substr($file_value[3], 10, 10);
        $insured_id = '';
        $ins_no = substr($file_value[0], 13, 10);
        $main_code = '';
        $effe_date = substr($file_value[3], 20, 7);
        $ins_type = substr($file_value[3], 27, 3);
        $total_pay_period = substr($file_value[3], 31, 2);
        $pay_type = substr($file_value[3], 33, 1);
        $recent_pay_period = substr($file_value[3], 34, 2);
        $premium_ori = (int) $file_value[5];
        $premium_twd = (int) $file_value[3];
        $bonus = (int) $file_value[19];
        $crc = substr($file_value[23], -3, 3);
        $crc_rate = (int) $file_value[26];
        $bonus_rate = '';
        $recent_pay_times = '';

        array_push($array, array(
            "doc_name" => $doc_name,
            "period" => $period,
            "supplier_code" => $supplier,
            "handle_id" => $handle_id,
            "handle_name" => " ",
            "insured_id" => $insured_id,
            "insured_name" => " ",
            "ins_no" => $ins_no,
            "main_code" => $main_code,
            "effe_date" => $effe_date,
            "ins_type" => $ins_type,
            "tatal_pay_period" => $total_pay_period,
            "pay_type" => $pay_type,
            "recent_pay_period" => $recent_pay_period,
            "pay_date" => " ",
            "premium_ori" => $premium_ori,
            "premium_twd" => $premium_twd,
            "bonus" => $bonus,
            "crc" => $crc,
            "crc_rate" => $crc_rate,
            "created_at" => date('Y-m-d H:i:s'),
            "created_by" => "Jane",
            "bonus_rate" => $bonus_rate,
            "recent_pay_times" => $recent_pay_times,
        ));

        return $array;
    }

    private static function formatDefault($file_value, $array, $doc_name, $period, $supplier)
    {
        # substr($file_value, -6, 6);#集匯代碼
        # substr($file_value, -17, 10);#受理編號
        # substr($file_value, -24, 7);#服務佣金
        # substr($file_value, -31, 7);#績效佣金
        # substr($file_value, -38, 7);#增額佣金
        # substr($file_value, -45, 7);#基本佣金
        # substr($file_value, -46, 1);#職業等級
        # substr($file_value, -57, 11);#匯率
        # substr($file_value, -60, 3);#幣別
        # substr($file_value, -75, 15);#原幣增額保費
        # substr($file_value, -90, 15);#原幣基本保費
        # substr($file_value, -100, 10);#服務佣金
        # substr($file_value, -112, 12);#績效獎金
        # substr($file_value, -124, 12);#增額佣金
        # substr($file_value, -134, 10);#基本佣金
        # substr($file_value, -146, 12);#增額保費
        # substr($file_value, -158, 12);#基本保費
        # substr($file_value, -164, 6);#保額
        # substr($file_value, -166, 2);#繳費次數
        # substr($file_value, -168, 2);#繳費年度
        # substr($file_value, -169, 1);#繳別
        # substr($file_value, -171, 2);#實繳年期
        # substr($file_value, -177, 4);#險種
        # substr($file_value, -178, 1);#主附約別
        # substr($file_value, -186, 8);#契約始期
        # substr($file_value, -196, 10);#業務員ID
        # substr($file_value, -199, 3);#被保險人年齡
        # substr($file_value, -200, 1);#被保險人性別
        # trim(substr($file_value, -210, 10));#被保險人ID
        # trim(substr($file_value, -230, 14));#被保險人姓名
        # substr($file_value, 0, 6);#單位代碼
        # substr($file_value, 6, 6);#工作年月
        # substr($file_value, 12, 1);#資料來源
        # trim(substr($file_value, 13, 14));#保單號碼
        # trim(substr($file_value, 27, 14));#要保人姓名
        # trim(substr($file_value, 94, 12));#要保人ID

        $sup_code = substr($file_value, 0, 6); #單位代號
        $sup_period = substr($file_value, 6, 6); #保單期間
        $source = substr($file_value, 12, 1); #資料來源；A-壽險;B-個人傷害險;C-旅行險
        $ins_no = trim(substr($file_value, 13, 14)); #保單號碼
        $payer_name = trim(substr($file_value, 27, 14)); #要保人姓名
        $payer_ID = trim(substr($file_value, 94, 12)); #要保人ID
        $insured_name = trim(substr($file_value, -230, 14)); #被保人姓名
        $insured_id = trim(substr($file_value, -210, 10)); #被保人ID
        $bonus_rate = null;
        $handle_id = substr($file_value, -196, 10); #業務員ID
        $effe_date = substr($file_value, -186, 8); #生效日
        $ins_type = substr($file_value, -177, 4); #險種
        $total_pay_period = substr($file_value, -171, 2); #年期
        $pay_type = substr($file_value, -169, 1); #繳別
        $recent_pay_period = (int) substr($file_value, -168, 2); #繳費年度
        $recent_pay_times = (int) substr($file_value, -166, 2); #繳費次數
        $premium_ori = (int) substr($file_value, -90, 15); #主約基本保費
        $premium_twd = (int) substr($file_value, -158, 12);
        $bonus = (int) substr($file_value, -100, 10); #服務津貼
        $crc = substr($file_value, -60, 3); #幣別
        $crc_rate = trim(substr($file_value, -57, 11)); #匯率
        $occupation = substr($file_value, -46, 1); #職業等級
        $main_code = substr($file_value, -178, 1);

        array_push($array, array(
            "doc_name" => $doc_name,
            "period" => $period,
            "supplier_code" => $supplier,
            "handle_id" => $handle_id,
            "handle_name" => " ",
            "insured_id" => $insured_id,
            "insured_name" => " ",
            "ins_no" => $ins_no,
            "main_code" => $main_code,
            "effe_date" => $effe_date,
            "ins_type" => $ins_type,
            "tatal_pay_period" => $total_pay_period,
            "pay_type" => $pay_type,
            "recent_pay_period" => $recent_pay_period,
            "pay_date" => " ",
            "premium_ori" => $premium_ori,
            "premium_twd" => $premium_twd,
            "bonus" => $bonus,
            "crc" => $crc,
            "crc_rate" => $crc_rate,
            "created_at" => date('Y-m-d H:i:s'),
            "created_by" => "Jane",
            "bonus_rate" => $bonus_rate,
            "recent_pay_times" => $recent_pay_times,
        ));

        return $array;
    }
}
