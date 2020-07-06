<?php

namespace App\Http\Controllers\ins_bonus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ins_bonus\BonusClass\RulesColumn;
use App\Http\Controllers\ins_bonus\SupplierImport\AIA;
use App\Http\Controllers\ins_bonus\SupplierImport\Farglory;
use App\Http\Controllers\ins_bonus\SupplierImport\Fubon;
use App\Http\Controllers\ins_bonus\SupplierImport\ShinKong;
use App\Http\Controllers\ins_bonus\SupplierImport\TaiwanLife;
use App\Http\Controllers\ins_bonus\SupplierImport\TransGlobe;
use App\Http\Controllers\ins_bonus\SupplierImport\Yuanta;
use App\Imports\UsersImport;
use App\import_bonus_doc_rules;
use App\import_bonus_suppliers;
use File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use \PhpOffice\PhpSpreadsheet\Shared\Date;

class BonusController extends Controller
{
    public function __construct(Request $request)
    {
        $this->middleware('auth:api');
        $this->supplier = $request->supplier;
        $this->file_path = $request->file->path();
        $this->doc_name = $request->file->getClientOriginalName();
        $this->period = $request->period;
    }

    public function supplier(Request $request)
    {

        $supplier = $request->supplier;
        $period = $request->period;
        $file_path = $request->file->path();
        $doc_name = $request->file->getClientOriginalName();

        $file = file_get_contents($file_path);

        switch ($supplier) {
            case 300000737: //全球人壽 ---> all csv
                if ((preg_match('/csv/i', strtolower($doc_name)))) {
                    $array = TransGlobe::bonusOri($file, $doc_name, $period, $supplier);
                } else {
                    return response()->json(['Please Convert File to CSV Format!']);
                }
                break;
            case 300000735: //遠雄人壽
                $sup = new Farglory;
                $array = $sup->bonusOri($file, $doc_name, $period, $supplier);
                break;
            case 300000734: //富邦人壽
                $array = Fubon::bonusOri($file, $doc_name, $period, $supplier);
                break;
            case 300006376: //元大人壽
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = Yuanta::bonusOri($data, $doc_name, $period, $supplier);
                File::delete($path);
                break;
            case 300000722: //台灣人壽
                ini_set("memory_limit", "1000M");
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = TaiwanLife::bonusOri($data, $doc_name, $period, $supplier);
                File::delete($path);
                break;
            case 300000749: //新光人壽
                $array = ShinKong::bonusOri($file, $doc_name, $period, $supplier);
                break;
            case 300000717: //友邦人壽
                $path1 = $request->file('file')->store('temp');
                $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
                $data = (new UsersImport)->toArray($path);
                $array = AIA::bonusOri($data, $doc_name, $period, $supplier);
                File::delete($path);
                break;
            default:
                return response()->json(['Failed! Please Check Your Input Info!']);
                exit;
        }

        switch ($array) {
            case is_null($array):
                return response()->json(['Empty insert!']);
            default:
                ini_set("memory_limit", "1000M");
                $chunk = array_chunk($array, 1000);
                foreach ($chunk as $chunk) {
                    import_bonus_suppliers::insert($chunk);
                }

                $this->uploadToServer('supplier_bonus');
                return response()->json(['success!']);
        }
    }

    public function rules(Request $request)
    {
        $path1 = $request->file('file')->store('temp');
        $path = storage_path('app') . DIRECTORY_SEPARATOR . $path1;
        $datas = (new UsersImport)->toArray($path);

        $array = array();
        foreach ($datas[0] as $file_key => $file_value) {
            if (count($file_value) > 40
                && empty($file_value[3]) == false
                && mb_strlen($file_value[3], "UTF-8") == strlen($file_value[3])
            ) {
                $data = $file_value;
                $RulesColumn = new RulesColumn($file_value);
                $ColumnModel = $RulesColumn->getReturn();
                $ColumnModel['doc_name'] = $this->doc_name;
                $ColumnModel['supplier_code'] = $this->supplier;

                array_push($array, $ColumnModel);
            }
        }
        //將先前的規則改為delete
        import_bonus_doc_rules::where('supplier_code', $this->supplier)
            ->where('deleted_at', null)
            ->update(['deleted_at' => date('Y-m-d H:i:s'), "deleted_by" => Auth::guard('api')->user()->name]);

        //新增新的規則
        import_bonus_doc_rules::insert($array);

        $this->uploadToServer('supplier_rules');
        File::delete($path);
        return response()->json(['success!']);
    }

    private function uploadToServer($dir)
    {
        $today = date('Y_m_d');
        $fileDir = storage_path("app/{$this->supplier}/{$dir}/{$today}");
        File::makeDirectory($fileDir, $mode = 0755, true, true);

        $upload_file_path = $fileDir . DIRECTORY_SEPARATOR . $this->doc_name;

        move_uploaded_file($this->file_path, $upload_file_path);
    }
}
