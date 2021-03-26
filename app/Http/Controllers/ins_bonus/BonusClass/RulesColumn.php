<?php
namespace App\Http\Controllers\ins_bonus\BonusClass;

use Illuminate\Support\Facades\Auth;
use \PhpOffice\PhpSpreadsheet\Shared\Date;

class RulesColumn
{
    private $data;
    private $currency;
    private $date;
    private $rules_date;
    private $rules_due_date;
    private $first_period;

    public function __construct($columns)
    {
        $this->data = $columns;
        $this->date = $this->data[0];
        $this->doc_number = $this->data[1];
        $this->doc_number_leishan = $this->data[2];
        $this->rules_date = $this->data[3];
        $this->rules_due_date = $this->data[4];
        $this->auto_extension = $this->data[5];
        $this->supplier_name = $this->data[6];
        $this->product_name = $this->data[7];
        $this->product_code = $this->data[8];
        $this->pro_keyword = $this->data[9];
        $this->currency = $this->data[10];
        $this->main_keyword1 = $this->data[11];
        $this->main_keyword2 = $this->data[12];
        $this->is_same_as_main_period = $this->data[13];
        $this->y_period_lower_limit = $this->data[14];
        $this->y_period_upper_limit = $this->data[15];
        $this->benefit_lower_limit = $this->data[16];
        $this->benefit_upper_limit = $this->data[17];
        $this->insured_age_lower_limit = $this->data[18];
        $this->insured_age_upper_limit = $this->data[19];
        $this->premium_type = $this->data[20];
        $this->remark = $this->data[42];
        $this->doc_date = null;
        $this->rules_start_date = null;
        $this->rules_due_date = null;
        $this->created_at = date('Y-m-d H:i:s');
        $this->created_by = Auth::guard('api')->user()->name;
        $this->deleted_at = null;
        $this->deleted_by = null;
        $this->{0} = '0';
        $this->{1} = $this->data[21];
        $this->{2} = $this->data[22];
        $this->{3} = $this->data[23];
        $this->{4} = $this->data[24];
        $this->{5} = $this->data[25];
        $this->{6} = $this->data[26];
        $this->{7} = $this->data[27];
        $this->{8} = $this->data[28];
        $this->{9} = $this->data[29];
        $this->{10} = $this->data[30];
        $this->{11} = $this->data[31];
        $this->{12} = $this->data[32];
        $this->{13} = $this->data[33];
        $this->{14} = $this->data[34];
        $this->{15} = $this->data[35];
        $this->{16} = $this->data[36];
        $this->{17} = $this->data[37];
        $this->{18} = $this->data[38];
        $this->{19} = $this->data[39];
        $this->{20} = $this->data[40];
    }

    public function getReturn()
    {
        $this->setCurrency();
        $this->setPeriodRules();
        $this->setPayType();
        $this->setPeriodLimit();
        $this->setRuleStartPeriod();
        $this->setProductCode();
        $this->setFirstPeriod();
        $this->setIsMain();
        $this->setDate($this->date, $this->doc_date);
        $this->setDate($this->rules_date, $this->rules_start_date);
        $this->setDate($this->rules_due_date, $this->rules_due_date);
        $this->setBenefitLimit();
        return json_decode(json_encode($this), true);
    }

    private function setCurrency()
    {
        $this->AUD = 0;
        $this->CAD = 0;
        $this->EUR = 0;
        $this->GBP = 0;
        $this->HKD = 0;
        $this->JPY = 0;
        $this->NTD = 0;
        $this->NZD = 0;
        $this->RMB = 0;
        $this->USD = 0;
        $this->ZAR = 0;

        switch ($this->currency) {
            case '外':
                $this->AUD = 1;
                $this->CAD = 1;
                $this->EUR = 1;
                $this->GBP = 1;
                $this->HKD = 1;
                $this->JPY = 1;
                $this->NZD = 1;
                $this->RMB = 1;
                $this->USD = 1;
                $this->ZAR = 1;
                break;
            case '美':
                $this->USD = 1;
                break;
            default:
                $this->AUD = 1;
                $this->CAD = 1;
                $this->EUR = 1;
                $this->GBP = 1;
                $this->HKD = 1;
                $this->JPY = 1;
                $this->NTD = 1;
                $this->NZD = 1;
                $this->RMB = 1;
                $this->USD = 1;
                $this->ZAR = 1;
                break;
        }
    }

    private function setPeriodRules()
    {
        if ($this->insured_age_lower_limit == 0 ||
            is_null($this->insured_age_upper_limit)) {
            $this->insured_age_lower_limit = 0;
            $this->insured_age_upper_limit = 99;
        }
    }

    private function setPayType()
    {
        if ($this->y_period_lower_limit === 0 && $this->y_period_lower_limit !== '躉') {
            $this->y_period_lower_limit = "全";
        } elseif ($this->y_period_lower_limit == '彈性') {
            $this->y_period_lower_limit = "彈性";
        }

        $this->pay_type = ($this->y_period_lower_limit == '躉') ? 'D' : 'all';

        if ($this->y_period_lower_limit == '躉') {
            $this->D = 1;
            $this->M = 0;
            $this->Q = 0;
            $this->S = 0;
            $this->Y = 0;
        } else {
            $this->D = 1;
            $this->M = 1;
            $this->Q = 1;
            $this->S = 1;
            $this->Y = 1;
        }
    }

    private function setPeriodLimit()
    {
        $this->y_period_lower_limit = (empty($this->y_period_lower_limit) || $this->y_period_lower_limit == '全')
        ? 0 : $this->y_period_lower_limit;
        $this->y_period_upper_limit = (empty($this->y_period_upper_limit)) ? 99 : $this->y_period_upper_limit;
    }

    private function setRuleStartPeriod()
    {
        $rule_types = ['NNN', 'AAA', 'BBB', 'CCC'];

        $empty_detecter = array();
        $rules_types_detecter = array();
        foreach ($rule_types as $rule_types) {
            $start_period = array_keys($this->data, $rule_types);
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
            $this->rules_start_period = 0;
            $this->rules_type = 'NNN';
        } else {
            $this->rules_start_period = $rules_types_detecter[0]["rules_start_period"];
            $this->rules_type = $rules_types_detecter[0]["rules_types_insert"];
        }
    }

    private function setProductCode()
    {
        switch ($this->product_name) {
            case '除':
                $this->product_code = 'exception';
                break;
            case '全':
                $this->product_code = 'all_pro';
                break;
        }
    }

    private function setFirstPeriod()
    {
        if ($this->{1} == "-") {
            $this->{1} = '0';
        }
    }

    private function setIsMain()
    {
        $this->is_main = ($this->pro_keyword == '附約') ? '0' : 'all';
    }

    private function setDate($originalVar, &$returnVar)
    {
        if (is_null($originalVar)) {
            $returnVar = null;
            return;
        }

        $returnVar = Date::excelToDateTimeObject($originalVar)->format('Y-m-d');
    }

    private function setBenefitLimit()
    {
        if ($this->benefit_lower_limit == '全') {
            $this->benefit_lower_limit = 0;
        }

        if ($this->benefit_upper_limit == '全') {
            $this->benefit_upper_limit = 0;
        }
    }
}
