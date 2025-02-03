<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\VerifyApiKey;

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
/** @noinspection PhpParamsInspection */

Route::get('/', function () {
    return 'API';
});



Route::prefix('v1')
    ->name('v1.')
    ->namespace('V1')
    ->middleware(VerifyApiKey::class)
    ->group(function () {
        require_once('api/v1.php');
    });
