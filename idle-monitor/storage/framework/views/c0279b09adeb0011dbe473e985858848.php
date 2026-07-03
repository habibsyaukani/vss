

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

    // Start Queue Worker
    $('#startQueueBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('<?php echo e(route('admin.system-control.queue.start')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            // Immediately update status
            $('#queueStatus').html('<span class="badge bg-success">Running</span>');
            // Wait 2 seconds then refresh from server
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
            // Immediately update status
            $('#queueStatus').html('<span class="badge bg-secondary">Stopped</span>');
            // Wait 1 second then refresh from server
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

    // Start Realtime Pull
    $('#startRealtimeBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('<?php echo e(route('admin.system-control.realtime.start')); ?>', {
            _token: '<?php echo e(csrf_token()); ?>'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            // Immediately update status
            $('#realtimeStatus').html('<span class="badge bg-success">Running</span>');
            // Wait 2 seconds then refresh from server
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
            // Immediately update status
            $('#realtimeStatus').html('<span class="badge bg-secondary">Stopped</span>');
            // Wait 1 second then refresh from server
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