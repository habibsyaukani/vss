<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IdleAlarmController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Dashboard API Routes
Route::prefix('dashboard')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/statistics', [DashboardController::class, 'statistics']);
    Route::get('/recent-alarms', [DashboardController::class, 'recentAlarms']);
});

// Idle Alarm API Routes
Route::prefix('alarms')->middleware('auth:sanctum')->group(function () {
    Route::get('/', [IdleAlarmController::class, 'index']);
    Route::post('/', [IdleAlarmController::class, 'store']);
    Route::get('/{id}', [IdleAlarmController::class, 'show']);
    Route::put('/{id}', [IdleAlarmController::class, 'update']);
    Route::delete('/{id}', [IdleAlarmController::class, 'destroy']);
});
