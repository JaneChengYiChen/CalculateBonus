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

Route::post('import_bonus_from_suppliers', 'bonus@supplier_import');
Route::post('import_bonus_doc_rules', 'bonus@rules');
Route::get('PKS', 'Man_Data@index');
