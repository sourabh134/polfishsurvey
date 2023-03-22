<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Controller;
use Spatie\Health\Http\Controllers\HealthCheckJsonResultsController;
use Spatie\Health\Http\Controllers\HealthCheckResultsController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/
Route::get('clear', function() {
    Artisan::call('cache:clear');
    return "Cleared!";
 });
Route::get('/', function () {
    return view('welcome');
});
Route::get('/survey-start', [Controller::class, 'survey_start']);
Route::post('/survey-save', [Controller::class, 'submit_survey_data']);
Route::get('/survey_complete', [Controller::class, 'survey_complete']);
Route::get('/survey_completed', [Controller::class, 'survey_completed']);
Route::get('/health/network', [Controller::class, 'networkCheck']);
Route::get('/health/deep', [Controller::class, 'checkconnection']);
Route::get('/health/check', [Controller::class, 'healthcheck']);
Route::get('/surveyList', [Controller::class, 'surveyList']);
Route::get('/reward_recheck', [Controller::class, 'reward_recheck']);
Route::get('/survey_closed', [Controller::class, 'survey_closed']);
Route::get('/survey_noteligible', [Controller::class, 'survey_noteligable']);
Route::get('/deleteLogFile', [Controller::class, 'deleteLogFile']);