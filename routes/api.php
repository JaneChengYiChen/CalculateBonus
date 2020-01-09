<?php

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

//收入驗證
Route::group(['prefix' => 'ins_bonus', 'namespace' => 'ins_bonus'], function () {
    //匯入來自保險公司的佣金
    Route::post('import_bonus_suppliers', 'bonus@supplier');
    //匯入公文規則
    Route::post('import_bonus_doc_rules', 'bonus@rules');
    //計算應該要有的pks佣金
    Route::post('ins_details_caculation', 'ins_details@query');
    //計算bonus_diff
    Route::post('bonus_diff', 'bonus_diff_data@mapping');
});
