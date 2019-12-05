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

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('import_bonus_from_suppliers', 'bonus@supplier_import');
Route::get('PKS', 'Man_Data@index');
// Route::get('revenue', function () {
//     // phpinfo();
//     //return phpinfo();
// });
