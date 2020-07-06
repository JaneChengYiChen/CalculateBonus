<?php
namespace App\Http\Controllers\ins_bonus\BonusClass;

use App\Http\Controllers\ins_bonus\SupplierImport\AIA;
use App\Http\Controllers\ins_bonus\SupplierImport\Farglory;
use App\Http\Controllers\ins_bonus\SupplierImport\Fubon;
use App\Http\Controllers\ins_bonus\SupplierImport\Hontai;
use App\Http\Controllers\ins_bonus\SupplierImport\ShinKong;
use App\Http\Controllers\ins_bonus\SupplierImport\TaiwanLife;
use App\Http\Controllers\ins_bonus\SupplierImport\TransGlobe;
use App\Http\Controllers\ins_bonus\SupplierImport\Yuanta;
use App\Imports\UsersImport;
use File;

class DataFromSupplier
{
    public function __construct($request)
    {
        $this->supplier = $request->supplier;
        $this->file_path = $request->file->path();
        $this->file = file_get_contents($this->file_path);
        $this->doc_name = $request->file->getClientOriginalName();
        $this->period = $request->period;
        $this->tmpFile = $request->file('file')->store('temp');
        $this->dataType = 1;
    }

    public function insertData()
    {
        $this->getWhichSupplierAndFileData();
        return $this->sup->bonusOri($this->file, $this->doc_name, $this->period, $this->supplier);
    }

    private function getWhichSupplierAndFileData()
    {
        switch ($this->supplier) {
            case 300000737: //全球人壽
                if ((preg_match('/csv/i', strtolower($this->doc_name)))) {
                    $this->sup = new TransGlobe;
                } else {
                    echo response()->json(['Please Convert File to CSV Format!']);
                    exit;
                }
                break;
            case 300000735: //遠雄人壽
                $this->sup = new Farglory;
                break;
            case 300000734: //富邦人壽
                $this->sup = new Fubon;
                break;
            case 300000749: //新光人壽
                $this->sup = new ShinKong;
                break;
            case 300000736: //宏泰人壽
                $this->sup = new Hontai;
                break;
            case 300006376: //元大人壽
                $this->file = $this->tmpFile();
                $this->sup = new Yuanta;
                break;
            case 300000722: //台灣人壽
                $this->file = $this->tmpFile();
                $this->sup = new TaiwanLife;
                break;
            case 300000717: //友邦人壽
                $this->file = $this->tmpFile();
                $this->sup = new AIA;
                break;
            default:
                echo response()->json(['Failed! Please Check Your Input Info!']);
                exit;
        }
    }

    private function tmpFile()
    {
        ini_set("memory_limit", "1000M");
        $this->tmpFilePath = storage_path('app') . DIRECTORY_SEPARATOR . $this->tmpFile;
        $this->dataType = 2;
        return (new UsersImport)->toArray($this->tmpFilePath);
    }

    private function deleteTmpfile()
    {
        if ($this->dataType == 2) {
            File::delete($this->tmpFilePath);
        }
    }
}
