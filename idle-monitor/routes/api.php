<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\IdleAlarmController;
use App\Http\Controllers\Api\HistoricalDataController;
use App\Http\Controllers\GpsTrackController;

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

// Historical Data API Routes (Admin)
Route::prefix('admin')->group(function () {
    Route::post('/pull-idle-alarms-range', [HistoricalDataController::class, 'pullIdleAlarmsDateRange']);
    Route::get('/historical-data-status', [HistoricalDataController::class, 'status']);
});

// GPS Tracks API Routes
Route::prefix('gps-tracks')->group(function () {
    Route::get('preview', [GpsTrackController::class, 'preview']);
    Route::post('sync',   [GpsTrackController::class, 'sync']);
});

// Authenticated routes (for future use)
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Tracksolid Real-time Webhook Push
Route::post('/tracksolid/webhook', [\App\Http\Controllers\Api\TracksolidWebhookController::class, 'handlePush']);

