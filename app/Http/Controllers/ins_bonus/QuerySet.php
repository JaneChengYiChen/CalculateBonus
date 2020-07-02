<?php

namespace App\Http\Controllers\ins_bonus;

use Illuminate\Support\Facades\DB;

class QuerySet
{
    public function __construct($supplier, $period, $date)
    {
        $this->supplier = $supplier;
        $this->period = $period;
        $this->date = $date;
    }

    public function predictData()
    {
        return $this->mainQuery();
    }

    private function mainQuery()
    {
        $this->tempTable1();
        // $this->tempTable2();
        // $this->tempTable3();

        return DB::connection('sqlsrv')
            ->select(
                DB::raw(
                    "
                    SELECT
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
                            ins.Ins_No COLLATE DATABASE_DEFAULT = Insurance.Ins_No
                            AND ins.code = Insurance.Code
                            AND Ins_Content.Main = 1) AS main_period,
                        ic.FYP * ic.crcrate FYP,
                        ic.FYB,
                        ic.FYA,
                        p.Ins_Code,
                        p.Pro_Name,
                        p.InsType,
                        p.FullName,
                        ins.diff,
                        F.first_pay_period,
                        RIGHT(F.first_pay_period, 2) first_pay_month,
                        year(ins.Receive_Date) - year(ic.birthday) payer_age
                    FROM
                        #tmptable3 ins
                        LEFT JOIN #tmptable ic ON ins.code = ic.MainCode
                        LEFT JOIN Product p ON ic.Pro_No = p.Pro_No
                        LEFT JOIN V_CRC crc ON ins.CRC COLLATE DATABASE_DEFAULT = crc.CRC
                        LEFT JOIN #tmptable2 F ON F.INO COLLATE DATABASE_DEFAULT = ins.Ins_No;

                drop table #tmptable;
				drop table #tmptable2;
				drop table #tmptable3;"
                )
            );
    }

    private function tempTable1()
    {
        return DB::connection('sqlsrv')
            ->unprepared(
                DB::raw(
                    "
                        SET ANSI_WARNINGS off
                        CREATE TABLE #tmptable
                            (
                                [CNO] int,
                                [Main] int,
                                [Rec_No] int,
                                [remark] nvarchar (50),
                                [MainCode] int,
                                [Ins_No] nvarchar (50),
                                [Receive_Date] datetime,
                                [Effe_Date] datetime,
                                [PDate] datetime,
                                [PayType] nvarchar (50),
                                [SupCode] int,
                                [Supplier] nvarchar (50),
                                [Pro_No] int,
                                [Pro_Name] nvarchar (200),
                                [InsType] int,
                                [InsItem] nvarchar (200),
                                [CRCRate] float,
                                [FYP] float,
                                [RFYP] float,
                                [FYB] float,
                                [FYA] float,
                                [Benefits] nvarchar (50),
                                [Unit] nvarchar (10),
                                [YPeriod] int,
                                [CRC] nvarchar (10),
                                [Audit] int,
                                [Void] int,
                                [Birthday] datetime
                                )

                        INSERT INTO #tmptable
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
                                        AND x. DATE <= '{$this->date}'
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
                                b.Void,
                                cus.Birthday
                            FROM
                                dbo.Ins_Content AS a
                                INNER JOIN Insurance AS b ON a.MainCode = b.Code
                                INNER JOIN Company AS c ON b.CNO = c.CNO
                                LEFT OUTER JOIN Product AS d ON a.Pro_No = d.Pro_No
                                LEFT OUTER JOIN Ins_Type AS e ON d.InsType = e.Type
                                INNER JOIN Supplier AS f ON b.SupCode = f.SupCode
                                LEFT JOIN Customer AS cus ON b.PayerCode = cus.code
                            WHERE
                                c.CNO <> 10000;

                    SET ANSI_WARNINGS off
                        CREATE TABLE #tmptable2
                        (
                        [INo] nvarchar (50),
                        [first_pay_period] int
                        )

                        INSERT INTO #tmptable2
                        SELECT DISTINCT SS_Detail.INo, MIN(ss_detail. [Period]) first_pay_period
                                FROM
                                    SS_Detail
                                WHERE
                                    ss_detail.SupCode = '{$this->supplier}'
                                            AND ss_detail. [Period] <= '{$this->period}'
                                GROUP BY
                                            SS_Detail.INo;

                CREATE TABLE #tmptable3
                    (
                    [code] int,
                    [Ins_No] nvarchar (200),
                    [Receive_Date] datetime,
                    [Effe_Date] datetime,
                    [PayType] nvarchar (4),
                    [CRC] nvarchar (20),
                    [Handle] int,
                    [Void] int,
                    [diff] int
                    )

                    INSERT INTO #tmptable3

                    SELECT
                        ins.code,
                        ins.Ins_No,
                        ins.Receive_Date,
                        ins.Effe_Date,
                        ins.PayType,
                        ins.CRC,
                        ins.Handle,
                        ins.Void,
                        (DATEDIFF(Month, ins.Effe_Date, '{$this->date}') / 12) + 1 diff
                    FROM
                        Insurance ins
                    WHERE
                        ins.Ins_No in( SELECT DISTINCT
                                SS_Detail.INo FROM SS_Detail
                            WHERE
                                ss_detail.SupCode = '{$this->supplier}'
                                AND ss_detail. [Period] <= '{$this->period}')
                        and((DATEDIFF(Month, ins.Effe_Date, '{$this->date}') / 12) + 1) > 0;"
                )
            );
    }
    private function tempTable2()
    {
        return DB::connection('sqlsrv')
            ->unprepared(
                DB::raw(
                    "
                        SET ANSI_WARNINGS off
                        CREATE TABLE #tmptable2
                        (
                        [INo] nvarchar (50),
                        [first_pay_period] int
                        )

                        INSERT INTO #tmptable2
                        SELECT DISTINCT SS_Detail.INo, MIN(ss_detail. [Period]) first_pay_period
                                FROM
                                    SS_Detail
                                WHERE
                                    ss_detail.SupCode = '{$this->supplier}'
                                            AND ss_detail. [Period] <= '{$this->period}'
                                GROUP BY
                                            SS_Detail.INo;"
                )
            );
    }

    private function tempTable3()
    {
        return DB::connection('sqlsrv')
            ->unprepared(
                DB::raw(
                    "
                    CREATE TABLE #tmptable3
                    (
                    [code] int,
                    [Ins_No] nvarchar (200),
                    [Receive_Date] datetime,
                    [Effe_Date] datetime,
                    [PayType] nvarchar (4),
                    [CRC] nvarchar (20),
                    [Handle] int,
                    [Void] int,
                    [diff] int
                    )

                    INSERT INTO #tmptable3

                    SELECT
                        ins.code,
                        ins.Ins_No,
                        ins.Receive_Date,
                        ins.Effe_Date,
                        ins.PayType,
                        ins.CRC,
                        ins.Handle,
                        ins.Void,
                        (DATEDIFF(Month, ins.Effe_Date, '{$this->date}') / 12) + 1 diff
                    FROM
                        Insurance ins
                    WHERE
                        ins.Ins_No in( SELECT DISTINCT
                                SS_Detail.INo FROM SS_Detail
                            WHERE
                                ss_detail.SupCode = '{$this->supplier}'
                                AND ss_detail. [Period] <= '{$this->period}')
                        and((DATEDIFF(Month, ins.Effe_Date, '{$this->date}') / 12) + 1) > 0;"
                )
            );
    }
}
