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

//Authorization
Route::group(['prefix' => 'auth'], function () {
    Route::get('/', 'AuthController@me');
    Route::post('login', 'AuthController@login');
    Route::post('logout', 'AuthController@logout');
    Route::post('register', 'AuthController@register');
    Route::get('login', ['as' => 'login', 'uses' => 'AuthController@login']);
});

//收入驗證
Route::group(['prefix' => 'ins_bonus', 'namespace' => 'ins_bonus'], function () {
    Route::post('import_bonus_suppliers', 'BonusController@supplier'); //匯入來自保險公司的佣金
    Route::post('import_bonus_doc_rules', 'BonusController@rules'); //匯入公文規則
    Route::post('ins_details_caculation', 'CalculationController@query'); //計算應該要有的pks佣金
    Route::post('bonus_diff', 'DiffController@mapping'); //計算bonus_diff
    Route::post('diffExportExcel', 'ExcelController@export'); //export Excel
    Route::post('email', 'MailController@send');//send Email
});
