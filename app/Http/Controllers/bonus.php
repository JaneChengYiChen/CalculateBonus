<?php

namespace App\Http\Controllers;

use App\table_insurance_ori_bonus;

class bonus extends Controller
{

    public function supplier_import()
    {
        $project_root = "/Users/user/Documents/insurance_bonus_projects/300000737";
        $files = glob("{$project_root}/*.txt");
        $array = array();
        foreach ($files as $files_key => $files_value) {
            $content = file_get_contents($files_value);
            $content = mb_convert_encoding($content, 'UTF-8', 'BIG-5');

            foreach (explode("\n", $content) as $str_key => $str_value) {
                $data = explode(" ", $str_value);
                echo var_dump($data);
                exit;
                //echo var_dump($str_value);
                // $data2 = trim(str_replace("\0", '', $data[0]));
                array_push($array, array(
                    "period" => "201808",
                    "supplier_code" => "300000737",
                ));
                exit;

            }
        }
        table_insurance_ori_bonus::insert(
            ['supplier_code' => '12344', 'ins_no' => 'test']
        );
        exit;
        $pks_members = table_insurance_ori_bonus::all();
        foreach ($pks_members as $pks_members) {
            echo $pks_members->name;
        }
        exit;
    }
}
