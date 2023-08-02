<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\api\VehicleController;
// use App\Http\Middleware\StaffDetailsJwtMiddleware;

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
Route::post('/login', [AuthController::class, 'login']);


Route::middleware('staff.jwt')->group(function () {
    // Routes that require staff_details authentication and authorization
    Route::post('/vehicle_in', [VehicleController::class, 'vehicle_in']);
    Route::post('/vehicle_out', [VehicleController::class, 'vehicle_out']);
    Route::post('/vehicle_history', [VehicleController::class, 'vehicle_history']);

});

