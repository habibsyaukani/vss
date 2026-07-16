<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DeviceGroupController;
use App\Http\Controllers\AlarmTypeController;
use App\Http\Controllers\IdleAlarmController;
use App\Http\Controllers\ImportLogController;
use App\Http\Controllers\SystemSettingController;
use App\Http\Controllers\DataPullController;
use App\Http\Controllers\AutoDataPullController;
use App\Http\Controllers\Admin\SystemHealthController;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

    // User Management
    Route::get('/user', [UserController::class, 'index'])->name('user.index');
    Route::get('/user/data', [UserController::class, 'data'])->name('user.data');
    Route::get('/user/create', [UserController::class, 'create'])->name('user.create');
    Route::post('/user', [UserController::class, 'store'])->name('user.store');
    Route::get('/user/{user}/edit', [UserController::class, 'edit'])->name('user.edit');
    Route::put('/user/{user}', [UserController::class, 'update'])->name('user.update');
    Route::delete('/user/{user}', [UserController::class, 'destroy'])->name('user.destroy');
    Route::post('/user/{user}/reset-password', [UserController::class, 'resetPassword'])->name('user.reset-password');

    // Device Management
    Route::get('/device', [DeviceController::class, 'index'])->name('device.index');
    Route::get('/device/data', [DeviceController::class, 'data'])->name('device.data');
    Route::get('/device/create', [DeviceController::class, 'create'])->name('device.create');
    Route::post('/device', [DeviceController::class, 'store'])->name('device.store');
    Route::get('/device/{device}/edit', [DeviceController::class, 'edit'])->name('device.edit');
    Route::put('/device/{device}', [DeviceController::class, 'update'])->name('device.update');
    Route::delete('/device/{device}', [DeviceController::class, 'destroy'])->name('device.destroy');
    Route::get('/device/import-form', [DeviceController::class, 'importForm'])->name('device.import-form');
    Route::post('/device/import', [DeviceController::class, 'import'])->name('device.import');

    // Device Group Management
    Route::get('/device-group', [DeviceGroupController::class, 'index'])->name('device-group.index');
    Route::get('/device-group/data', [DeviceGroupController::class, 'data'])->name('device-group.data');
    Route::get('/device-group/create', [DeviceGroupController::class, 'create'])->name('device-group.create');
    Route::post('/device-group', [DeviceGroupController::class, 'store'])->name('device-group.store');
    Route::get('/device-group/{deviceGroup}/edit', [DeviceGroupController::class, 'edit'])->name('device-group.edit');
    Route::put('/device-group/{deviceGroup}', [DeviceGroupController::class, 'update'])->name('device-group.update');
    Route::delete('/device-group/{deviceGroup}', [DeviceGroupController::class, 'destroy'])->name('device-group.destroy');

    // Alarm Type Management
    Route::get('/alarm-type', [AlarmTypeController::class, 'index'])->name('alarm-type.index');
    Route::get('/alarm-type/data', [AlarmTypeController::class, 'data'])->name('alarm-type.data');
    Route::get('/alarm-type/create', [AlarmTypeController::class, 'create'])->name('alarm-type.create');
    Route::post('/alarm-type', [AlarmTypeController::class, 'store'])->name('alarm-type.store');
    Route::get('/alarm-type/{alarmType}/edit', [AlarmTypeController::class, 'edit'])->name('alarm-type.edit');
    Route::put('/alarm-type/{alarmType}', [AlarmTypeController::class, 'update'])->name('alarm-type.update');
    Route::delete('/alarm-type/{alarmType}', [AlarmTypeController::class, 'destroy'])->name('alarm-type.destroy');

    // Idle Alarm Management
    Route::get('/idle-alarm', [IdleAlarmController::class, 'index'])->name('idle-alarm.index');
    Route::get('/idle-alarm/data', [IdleAlarmController::class, 'data'])->name('idle-alarm.data');
    Route::get('/idle-alarm/{idleAlarm}', [IdleAlarmController::class, 'show'])->name('idle-alarm.show');
    Route::post('/idle-alarm/export', [IdleAlarmController::class, 'export'])->name('idle-alarm.export');

    // Import Log Management
    Route::get('/import-log', [ImportLogController::class, 'index'])->name('import-log.index');
    Route::get('/import-log/data', [ImportLogController::class, 'data'])->name('import-log.data');
    Route::get('/import-log/latest', [ImportLogController::class, 'latest'])->name('import-log.latest');

    // System Settings
    Route::get('/system-setting', [SystemSettingController::class, 'index'])->name('system-setting.index');

    // Data Pull Management (Idle Alarm)
    Route::get('/data-pull', [DataPullController::class, 'index'])->name('data-pull.index');
    Route::post('/data-pull/execute', [DataPullController::class, 'execute'])->name('data-pull.execute');
    Route::get('/data-pull/statistics', [DataPullController::class, 'statistics'])->name('data-pull.statistics');
    Route::get('/data-pull/progress/{sessionId}', [DataPullController::class, 'progress'])->name('data-pull.progress');

    // GPS Track Pull Management
    Route::get('/gps-track-pull', [DataPullController::class, 'gpsTrackIndex'])->name('gps-track-pull.index');
    Route::post('/gps-track-pull/execute', [DataPullController::class, 'gpsTrackExecute'])->name('gps-track-pull.execute');
    Route::get('/gps-track-pull/statistics', [DataPullController::class, 'gpsTrackStatistics'])->name('gps-track-pull.statistics');
    Route::get('/gps-track-pull/devices', [DataPullController::class, 'getActiveDevices'])->name('gps-track-pull.devices');

    // Auto Data Pull (Alternating Idle & GPS every 30 minutes)
    Route::get('/auto-data-pull', [AutoDataPullController::class, 'index'])->name('auto-data-pull.index');
    Route::post('/auto-data-pull/toggle', [AutoDataPullController::class, 'toggle'])->name('auto-data-pull.toggle');
    Route::post('/auto-data-pull/run-now', [AutoDataPullController::class, 'runNow'])->name('auto-data-pull.run-now');
    Route::get('/auto-data-pull/status', [AutoDataPullController::class, 'getStatus'])->name('auto-data-pull.status');
    Route::post('/auto-data-pull/update-interval', [AutoDataPullController::class, 'updateInterval'])->name('auto-data-pull.update-interval');

    // System Control - REMOVED (UI only, background services still run via scheduler/CLI)
    // Background services remain functional:
    // - Queue Worker: php artisan queue:work
    // - Realtime Pull: Runs automatically via scheduler
    // - Cleanup: Runs automatically via scheduler


    // System Health Check
    Route::get('/system-health', [SystemHealthController::class, 'index'])->name('system-health.index');
    Route::get('/system-health/check', [SystemHealthController::class, 'checkHealth'])->name('system-health.check');
    Route::post('/system-health/migrate', [SystemHealthController::class, 'runMigration'])->name('system-health.migrate');
    Route::post('/system-health/heal', [SystemHealthController::class, 'manualHeal'])->name('system-health.heal');
    Route::get('/system-health/logs', [SystemHealthController::class, 'getHealingLogs'])->name('system-health.logs');

    // Batch Pull Migration (One-time setup)
    Route::get('/run-migration-batch-pull', [\App\Http\Controllers\MigrationController::class, 'index'])->name('run-migration.index');
    Route::post('/run-migration-batch-pull', [\App\Http\Controllers\MigrationController::class, 'runMigration'])->name('run-migration.execute');
});
