

<?php $__env->startSection('title', 'System Control'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <h3 class="mb-4"><i class="fas fa-cogs"></i> System Control Center</h3>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Control background processes for Queue Worker and Realtime Data Pull. Status auto-refreshes every 5 seconds.
    </div>

    <!-- Queue Worker Control -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-tasks"></i> Queue Worker Control</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="queueStatus">
                        <span class="badge <?php echo e($queueStatus['badge_class']); ?>">
                            <?php echo e($queueStatus['badge_text']); ?>

                        </span>
                    </h3>
                    <?php if($queueStatus['started_at']): ?>
                        <small class="text-muted">Started at: <?php echo e($queueStatus['started_at']); ?></small>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="d-grid gap-2 d-md-flex">
                        <button id="startQueueBtn" class="btn btn-success btn-lg" <?php echo e($queueStatus['running'] ? 'disabled' : ''); ?>>
                            <i class="fas fa-play"></i> Start Queue Worker
                        </button>
                        <button id="stopQueueBtn" class="btn btn-danger btn-lg" <?php echo e(!$queueStatus['running'] ? 'disabled' : ''); ?>>
                            <i class="fas fa-stop"></i> Stop Queue Worker
                        </button>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>What it does:</strong> Processes background jobs (import alarms, process idle alarms, etc)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Realtime Data Pull Control -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-sync"></i> Realtime Data Pull Control</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="realtimeStatus">
                        <span class="badge <?php echo e($realtimeStatus['badge_class']); ?>">
                            <?php echo e($realtimeStatus['badge_text']); ?>

                        </span>
                    </h3>
                    <?php if($realtimeStatus['started_at']): ?>
                        <small class="text-muted d-block">Started: <?php echo e($realtimeStatus['started_at']); ?></small>
                    <?php endif; ?>
                    <?php
                        $lastSuccess = \App\Models\SystemSetting::get('realtime_pull_last_success_at');
                        $lastError = \App\Models\SystemSetting::get('realtime_pull_last_error');
                        $lastErrorAt = \App\Models\SystemSetting::get('realtime_pull_last_error_at');
                    ?>
                    <?php if($lastSuccess): ?>
                        <small class="text-success d-block">
                            <i class="fas fa-check-circle"></i> Last success: <?php echo e(\Carbon\Carbon::parse($lastSuccess)->diffForHumans()); ?>

                        </small>
                    <?php endif; ?>
                    <?php if($lastError && $lastErrorAt): ?>
                        <small class="text-danger d-block" title="<?php echo e($lastError); ?>">
                            <i class="fas fa-exclamation-circle"></i> Last error: <?php echo e(\Carbon\Carbon::parse($lastErrorAt)->diffForHumans()); ?>

                        </small>
                    <?php endif; ?>
                </div>
                <div class="col-md-8">
                    <div class="d-grid gap-2 d-md-flex">
                        <button id="startRealtimeBtn" class="btn btn-success btn-lg" <?php echo e($realtimeStatus['running'] ? 'disabled' : ''); ?>>
                            <i class="fas fa-play"></i> Start Realtime Pull
                        </button>
                        <button id="stopRealtimeBtn" class="btn btn-danger btn-lg" <?php echo e(!$realtimeStatus['running'] ? 'disabled' : ''); ?>>
                            <i class="fas fa-stop"></i> Stop Realtime Pull
                        </button>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>What it does:</strong> Continuously pulls Idle Alarm data (last 48 hours) and GPS Track data (last 2 hours) every 3 minutes.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cleanup Control Section -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-trash-alt"></i> Automatic Cleanup Control</h5>
        </div>
        <div class="card-body">
            <!-- Status Badge -->
            <div class="row align-items-center mb-3">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="cleanupStatusBadge">
                        <span class="badge <?php echo e($cleanupSettings['cleanup_enabled'] ? 'bg-success' : 'bg-danger'); ?>">
                            <?php echo e($cleanupSettings['cleanup_enabled'] ? 'ENABLED' : 'DISABLED'); ?>

                        </span>
                    </h3>
                    <small class="text-muted">Last Run: <span id="cleanupLastRun"><?php echo e($cleanupSettings['cleanup_last_run'] ?? 'Never'); ?></span></small>
                </div>
                <div class="col-md-8">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> 
                        <strong>What it does:</strong> Automatically deletes old raw data (alarm_raw, gps_tracks_raw) based on retention period. 
                        Only deletes data that has been processed to final tables.
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <form id="cleanupSettingsForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Enable Automatic Cleanup</strong></label>
                            <select name="cleanup_enabled" class="form-control">
                                <option value="1" <?php echo e($cleanupSettings['cleanup_enabled'] ? 'selected' : ''); ?>>Enabled</option>
                                <option value="0" <?php echo e(!$cleanupSettings['cleanup_enabled'] ? 'selected' : ''); ?>>Disabled</option>
                            </select>
                            <small class="form-text text-muted">
                                Enable or disable automatic cleanup
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Retention Period (Days)</strong></label>
                            <input type="number" name="cleanup_retention_days" class="form-control" 
                                   value="<?php echo e($cleanupSettings['cleanup_retention_days']); ?>" min="7" max="365">
                            <small class="form-text text-muted">
                                Keep data for this many days (7-365)
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Schedule</strong></label>
                            <select name="cleanup_schedule" class="form-control">
                                <option value="daily" <?php echo e($cleanupSettings['cleanup_schedule'] === 'daily' ? 'selected' : ''); ?>>Daily (02:00 AM)</option>
                                <option value="weekly" <?php echo e($cleanupSettings['cleanup_schedule'] === 'weekly' ? 'selected' : ''); ?>>Weekly (Sunday 02:00 AM)</option>
                                <option value="monthly" <?php echo e($cleanupSettings['cleanup_schedule'] === 'monthly' ? 'selected' : ''); ?>>Monthly (1st, 02:00 AM)</option>
                            </select>
                            <small class="form-text text-muted">
                                How often to run cleanup
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" id="btnRunCleanup" class="btn btn-warning btn-lg">
                            <i class="fas fa-play"></i> Run Cleanup Now
                        </button>
                    </div>
                </div>
            </form>

            <!-- Statistics -->
            <div class="mt-4">
                <h6><i class="fas fa-chart-bar"></i> Cleanup Preview</h6>
                <p class="text-muted">
                    Data older than: <strong id="cutoffDate"><?php echo e($cleanupStats['cutoff_date']); ?></strong>
                </p>
                
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Table</th>
                            <th>Total Records</th>
                            <th>Old Records (Will Delete)</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody id="cleanupStats">
                        <tr>
                            <td><strong>alarm_raw</strong></td>
                            <td id="alarmRawTotal"><?php echo e(number_format($cleanupStats['alarm_raw']['total'])); ?></td>
                            <td id="alarmRawOld" class="text-danger">
                                <strong><?php echo e(number_format($cleanupStats['alarm_raw']['old'])); ?></strong>
                            </td>
                            <td id="alarmRawPct">
                                <?php if($cleanupStats['alarm_raw']['total'] > 0): ?>
                                    <?php echo e(number_format(($cleanupStats['alarm_raw']['old'] / $cleanupStats['alarm_raw']['total']) * 100, 1)); ?>%
                                <?php else: ?>
                                    0%
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>gps_tracks_raw</strong></td>
                            <td id="gpsRawTotal"><?php echo e(number_format($cleanupStats['gps_raw']['total'])); ?></td>
                            <td id="gpsRawOld" class="text-danger">
                                <strong><?php echo e(number_format($cleanupStats['gps_raw']['old'])); ?></strong>
                            </td>
                            <td id="gpsRawPct">
                                <?php if($cleanupStats['gps_raw']['total'] > 0): ?>
                                    <?php echo e(number_format(($cleanupStats['gps_raw']['old'] / $cleanupStats['gps_raw']['total']) * 100, 1)); ?>%
                                <?php else: ?>
                                    0%
                                <?php endif; ?>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Activity Log</h5>
        </div>
        <div class="card-body">
            <div id="activityLog" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <div class="text-muted">Activity log will appear here...</div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
$(document).ready(function() {
    console.log('System Control page loaded');

    // Auto-refresh status every 5 seconds
    setInterval(refreshStatus, 5000);

    // ========== QUEUE WORKER HANDLERS ==========
    
    // Start Queue Worker
    $('#startQueueBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('<?php echo e(route('admin.system-control.queue.start')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            $('#queueStatus').html('<span class="badge bg-success">Running</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 2000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#startQueueBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Start Queue Worker');
        });
    });

    // Stop Queue Worker
    $('#stopQueueBtn').click(function() {
        if (!confirm('Are you sure you want to stop the Queue Worker?')) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('<?php echo e(route('admin.system-control.queue.stop')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'warning');
            $('#queueStatus').html('<span class="badge bg-secondary">Stopped</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 1000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#stopQueueBtn').prop('disabled', false).html('<i class="fas fa-stop"></i> Stop Queue Worker');
        });
    });

    // ========== REALTIME PULL HANDLERS ==========
    
    // Start Realtime Pull
    $('#startRealtimeBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('<?php echo e(route('admin.system-control.realtime.start')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            $('#realtimeStatus').html('<span class="badge bg-success">Running</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 2000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#startRealtimeBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Start Realtime Pull');
        });
    });

    // Stop Realtime Pull
    $('#stopRealtimeBtn').click(function() {
        if (!confirm('Are you sure you want to stop the Realtime Data Pull?')) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('<?php echo e(route('admin.system-control.realtime.stop')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'warning');
            $('#realtimeStatus').html('<span class="badge bg-secondary">Stopped</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 1000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#stopRealtimeBtn').prop('disabled', false).html('<i class="fas fa-stop"></i> Stop Realtime Pull');
        });
    });

    // ========== CLEANUP HANDLERS ==========
    
    // Save cleanup settings
    $('#cleanupSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '<?php echo e(route("admin.system-control.update-cleanup")); ?>',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                addLog(response.message, 'success');
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000
                });
                refreshStatus();
            },
            error: function(xhr) {
                addLog('Failed to update cleanup settings', 'danger');
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to update settings'
                });
            }
        });
    });

    // Run cleanup manually
    $('#btnRunCleanup').on('click', function() {
        Swal.fire({
            title: 'Run Cleanup Now?',
            text: 'This will delete old raw data according to your retention settings.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, run cleanup',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '<?php echo e(route("admin.system-control.run-cleanup")); ?>',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        addLog('Cleanup job started', 'success');
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleanup Started',
                            text: response.message
                        });
                        setTimeout(refreshStatus, 5000);
                    },
                    error: function(xhr) {
                        addLog('Failed to run cleanup', 'danger');
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to run cleanup'
                        });
                    }
                });
            }
        });
    });

    // ========== STATUS REFRESH ==========
    
    // Refresh status
    function refreshStatus() {
        $.get('<?php echo e(route('admin.system-control.status')); ?>')
        .done(function(data) {
            // Update Queue Worker status
            $('#queueStatus').html('<span class="badge ' + data.queue.badge_class + '">' + data.queue.badge_text + '</span>');
            $('#startQueueBtn').prop('disabled', data.queue.running).html('<i class="fas fa-play"></i> Start Queue Worker');
            $('#stopQueueBtn').prop('disabled', !data.queue.running).html('<i class="fas fa-stop"></i> Stop Queue Worker');
            
            // Update Realtime Pull status
            $('#realtimeStatus').html('<span class="badge ' + data.realtime.badge_class + '">' + data.realtime.badge_text + '</span>');
            $('#startRealtimeBtn').prop('disabled', data.realtime.running).html('<i class="fas fa-play"></i> Start Realtime Pull');
            $('#stopRealtimeBtn').prop('disabled', !data.realtime.running).html('<i class="fas fa-stop"></i> Stop Realtime Pull');
            
            // Update Cleanup status
            if (data.cleanup) {
                const enabled = data.cleanup.settings.cleanup_enabled;
                $('#cleanupStatusBadge span')
                    .removeClass('bg-success bg-danger')
                    .addClass(enabled ? 'bg-success' : 'bg-danger')
                    .text(enabled ? 'ENABLED' : 'DISABLED');
                
                $('#cleanupLastRun').text(data.cleanup.settings.cleanup_last_run || 'Never');
                
                // Update stats
                const stats = data.cleanup.stats;
                $('#alarmRawTotal').text(stats.alarm_raw.total.toLocaleString());
                $('#alarmRawOld').html('<strong>' + stats.alarm_raw.old.toLocaleString() + '</strong>');
                $('#gpsRawTotal').text(stats.gps_raw.total.toLocaleString());
                $('#gpsRawOld').html('<strong>' + stats.gps_raw.old.toLocaleString() + '</strong>');
                
                // Update percentages
                if (stats.alarm_raw.total > 0) {
                    const pct = (stats.alarm_raw.old / stats.alarm_raw.total * 100).toFixed(1);
                    $('#alarmRawPct').text(pct + '%');
                }
                if (stats.gps_raw.total > 0) {
                    const pct = (stats.gps_raw.old / stats.gps_raw.total * 100).toFixed(1);
                    $('#gpsRawPct').text(pct + '%');
                }
                
                $('#cutoffDate').text(stats.cutoff_date);
            }
        });
    }

    // Add log entry
    function addLog(message, type = 'info') {
        const time = new Date().toLocaleTimeString();
        const colors = {
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        const logEntry = `<div style="color: ${colors[type]};">[${time}] ${message}</div>`;
        $('#activityLog').prepend(logEntry);
    }
});
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views/admin/system-control/index.blade.php ENDPATH**/ ?>