<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api;

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

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('/survey', [Api::class, 'survey']);
Route::post('/salesProgress', [Api::class, 'salesProgress']);
Route::put('/demographic_data/{id}', [Api::class, 'demographic_data']);
Route::get('/reconciliation', [Api::class, 'reconciliation']);
