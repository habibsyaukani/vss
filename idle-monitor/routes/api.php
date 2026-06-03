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

// Dashboard API Routes - Public (no auth required for MVP)
Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/statistics', [DashboardController::class, 'statistics']);
    Route::get('/recent', [DashboardController::class, 'recentAlarms']);
});

// Idle Alarm API Routes - Public (no auth required for MVP)
Route::prefix('idle-alarms')->group(function () {
    Route::get('/', [IdleAlarmController::class, 'index']);
    Route::get('/device/{deviceId}', [IdleAlarmController::class, 'byDevice']);
    Route::get('/group/{groupName}', [IdleAlarmController::class, 'byGroup']);
    Route::get('/{id}', [IdleAlarmController::class, 'show']);
    Route::put('/{id}', [IdleAlarmController::class, 'update']);
    Route::delete('/{id}', [IdleAlarmController::class, 'destroy']);
});

// Authenticated routes (for future use)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
