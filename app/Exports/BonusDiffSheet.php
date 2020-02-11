<?php

namespace App\Exports;

use App\bonus_diff;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

class BonusDiffSheet implements FromQuery, WithTitle, WithHeadings, ShouldAutoSize, WithMapping
{
    private $start;
    private $end;
    private $supplier;

    public function __construct(string $start, string $end, string $supplier)
    {
        $this->start = $start;
        $this->end = $end;
        $this->supplier = $supplier;
    }

    public function headings(): array
    {
        return [
            [
                '保單號碼',
                '繳別',
                '預期來佣月份',
                '預期服務津貼',
                '實際來佣月份',
                '實際服務津貼',
                '差額',
                '註記',
                'PKS 註記',
            ],
        ];
    }
    /**
     * @return Builder
     */
    public function query()
    {
        return bonus_diff
            ::query()
            ->where('period_cal', '>=', $this->start)
            ->where('period_cal', '<=', $this->end)
            ->where('sup_code', $this->supplier)
            ->orderby('bonus_diff', 'asc')
            ->select(
                'ins_no',
                'pay_type',
                'period_cal',
                'bonus_cal',
                'period_ori',
                'bonus_ori',
                'bonus_diff',
                'remark',
                'remark_ic'
            );
    }

    public function map($bonus_diff): array
    {

        return [
            $bonus_diff->ins_no,
            $this::payType($bonus_diff->pay_type),
            $bonus_diff->period_cal,
            $bonus_diff->bonus_cal,
            $bonus_diff->period_ori,
            $bonus_diff->bonus_ori,
            $bonus_diff->bonus_diff,
            $bonus_diff->remark,
            $bonus_diff->remark_ic,
        ];
    }

    /**
     * @return string
     */
    public function title(): string
    {
        switch ($this->supplier) {
            case 300000737:
                $supplier_name = '全球人壽';
                break;
            case 300000735:
                $supplier_name = '遠雄人壽';
                break;
            case 300000734:
                $supplier_name = '富邦人壽';
                break;
            case 300006376:
                $supplier_name = '元大人壽';
                break;
            case 300000722:
                $supplier_name = '台灣人壽';
                break;
            case 300000749:
                $supplier_name = '新光人壽';
                break;
            case 300000717:
                $supplier_name = '友邦人壽';
                break;
            default:
                $supplier_name = '保險公司';
                break;
        }

        return $supplier_name;
    }

    private function payType($paytype)
    {
        switch ($paytype) {
            case 'M':
                $return = '月繳';
                break;
            case 'D':
                $return = '躉繳';
                break;
            case 'Y':
                $return = '年繳';
                break;
            case 'S':
                $return = '半年繳';
                break;
            case 'Q':
                $return = '季繳';
                break;
        }

        return $return;
    }
}
