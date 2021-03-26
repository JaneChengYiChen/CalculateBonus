<?php

namespace App\Http\Controllers\ins_bonus;

use App\Http\Controllers\Controller;
use App\Http\Controllers\ins_bonus\BonusClass\DataFromSupplier;
use App\Http\Controllers\ins_bonus\BonusClass\RulesColumn;
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
        $this->tmpFile = $request->file('file')->store('temp');
    }

    public function supplier(Request $request)
    {
        $DataFromSupplier = new DataFromSupplier($request);
        $array = $DataFromSupplier->insertData();

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

    public function rules()
    {
        $path = storage_path('app') . DIRECTORY_SEPARATOR . $this->tmpFile;
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
