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

//匯入來自保險公司的佣金
Route::post('import_bonus_from_suppliers', 'bonus@supplier_import');
//匯入公文規則
Route::post('import_bonus_doc_rules', 'bonus@rules');
//計算應該要有的pks佣金
Route::post('pks_ins_details', 'ins_details@query');
