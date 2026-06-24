<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Frontend\DashboardController;
use App\Http\Controllers\Frontend\IdleAlarmController;
use App\Http\Controllers\Frontend\DeviceController;
use App\Http\Controllers\Frontend\SpeedController;

Route::middleware(['auth', 'fleet_manager'])->prefix('')->name('frontend.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Idle Alarm Management (Read-only)
    Route::get('/idle-alarm', [IdleAlarmController::class, 'index'])->name('idle-alarm.index');
    Route::get('/idle-alarm/data', [IdleAlarmController::class, 'data'])->name('idle-alarm.data');
    Route::get('/idle-alarm/{idleAlarm}', [IdleAlarmController::class, 'show'])->name('idle-alarm.show');
    Route::post('/idle-alarm/export', [IdleAlarmController::class, 'export'])->name('idle-alarm.export');
    Route::get('/idle-alarm/export-status/{jobId}', [IdleAlarmController::class, 'checkExportStatus'])->name('idle-alarm.export-status');
    Route::get('/idle-alarm/download-export/{jobId}', [IdleAlarmController::class, 'downloadExport'])->name('idle-alarm.download-export');

    // Speed Management (Coming Soon)
    Route::get('/speed', [SpeedController::class, 'index'])->name('speed.index');
    Route::get('/speed/data', [SpeedController::class, 'getData'])->name('speed.data');
    Route::post('/speed/export', [SpeedController::class, 'export'])->name('speed.export');
    
    // Speed Performance
    Route::get('/speed-performance', [App\Http\Controllers\Frontend\SpeedPerformanceController::class, 'index'])->name('speed-performance.index');
    Route::get('/speed-performance/data', [App\Http\Controllers\Frontend\SpeedPerformanceController::class, 'getData'])->name('speed-performance.data');
    Route::post('/speed-performance/export', [App\Http\Controllers\Frontend\SpeedPerformanceController::class, 'export'])->name('speed-performance.export');
    
    // Device Management (Read-only) - Hidden, accessible via direct URL only
    Route::get('/device', [DeviceController::class, 'index'])->name('device.index');
    Route::get('/device/data', [DeviceController::class, 'data'])->name('device.data');
    Route::get('/device/{device}', [DeviceController::class, 'show'])->name('device.show');

    // Logout
    Route::post('/logout', function () {
        auth()->logout();
        session()->invalidate();
        return redirect('/login');
    })->name('logout');
});
