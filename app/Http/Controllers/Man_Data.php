<?php

namespace App\Http\Controllers;

use App\Man_Data_Table;

class Man_Data extends Controller
{
    public function index()
    {

        $Man_Data = Man_Data_Table::all();
        echo var_dump($Man_Data);
        // foreach ($Man_Data as $Man_Data) {
        //     echo $Man_Data->name;
        // }
        exit;
    }
    //
}
