

<?php $__env->startSection('title', 'Device Detail - Fleet Manager'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-truck"></i> Device Detail
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo e(route('frontend.device.index')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Details -->
        <div class="col-md-8">
            <!-- Device Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-info-circle"></i> Device Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Device Name</label>
                            <p class="h6"><?php echo e($device->device_name); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Device ID</label>
                            <p class="h6"><?php echo e($device->device_id); ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">IMEI</label>
                            <p class="h6"><?php echo e($device->imei ?? 'N/A'); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">SIM Number</label>
                            <p class="h6"><?php echo e($device->sim ?? 'N/A'); ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Group</label>
                            <p class="h6">
                                <?php if($device->group_name): ?>
                                    <span class="badge bg-info"><?php echo e($device->group_name); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Not assigned</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Last Sync</label>
                            <p class="h6">
                                <?php if($device->last_sync_at): ?>
                                    <?php echo e($device->last_sync_at->format('Y-m-d H:i:s')); ?>

                                <?php else: ?>
                                    <span class="text-muted">Never</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="alert alert-info">
                        <strong>Current Status:</strong>
                        <?php if($device->last_sync_at): ?>
                            <?php
                                $mins = now()->diffInMinutes($device->last_sync_at);
                            ?>
                            <?php if($mins < 30): ?>
                                <span class="badge bg-success">Active</span> (Last sync <?php echo e($mins); ?> minutes ago)
                            <?php elseif($mins < 120): ?>
                                <span class="badge bg-warning">Idle</span> (Last sync <?php echo e($mins); ?> minutes ago)
                            <?php else: ?>
                                <span class="badge bg-danger">Offline</span> (Last sync <?php echo e($mins); ?> minutes ago)
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="badge bg-danger">Never Synced</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Idle History -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Idle History
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="historyTable" class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Start Time</th>
                                    <th>End Time</th>
                                    <th>Duration</th>
                                    <th>Start Speed</th>
                                    <th>End Speed</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar Stats -->
        <div class="col-md-4">
            <!-- Statistics -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-chart-bar"></i> Statistics (Last 30 Days)
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted">Total Idle Events</label>
                        <p class="h4 fw-bold" id="totalIdleEvents">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Total Idle Hours</label>
                        <p class="h4 fw-bold" id="totalIdleHours">-</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Average Duration per Event</label>
                        <p class="h4 fw-bold" id="avgDuration">-</p>
                    </div>
                </div>
            </div>

            <!-- Information Card -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-question-circle"></i> About This Device
                    </h5>
                </div>
                <div class="card-body">
                    <p class="small text-muted">
                        This page shows all idle time events for this specific vehicle. 
                        An idle event occurs when the vehicle stops and remains stationary for at least 5 minutes.
                    </p>
                    <hr>
                    <p class="small text-muted">
                        <strong>Click "View"</strong> on any event to see detailed information including 
                        location, exact timing, and vehicle speed before and after the idle period.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    let historyTable;
    const deviceId = <?php echo e($device->id); ?>;
    
    // Initialize DataTable for idle history
    function initHistoryTable() {
        if (historyTable) {
            historyTable.destroy();
        }

        historyTable = $('#historyTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?php echo e(route('frontend.idle-alarm.data')); ?>",
                data: function(d) {
                    d.device_id = deviceId;
                    d.start_date = new Date(new Date().setDate(new Date().getDate() - 30)).toISOString().split('T')[0];
                    d.end_date = new Date().toISOString().split('T')[0];
                }
            },
            columns: [
                { 
                    data: 'starting_time', 
                    name: 'starting_time',
                    render: function(data) {
                        let date = new Date(data);
                        return date.toLocaleString();
                    }
                },
                { 
                    data: 'ending_time', 
                    name: 'ending_time',
                    render: function(data) {
                        let date = new Date(data);
                        return date.toLocaleString();
                    }
                },
                { 
                    data: 'duration_minutes', 
                    name: 'duration_minutes',
                    render: function(data) {
                        return data + ' min';
                    }
                },
                { data: 'start_speed', name: 'start_speed' },
                { data: 'end_speed', name: 'end_speed' },
                { 
                    data: 'alarm_status', 
                    name: 'alarm_status',
                    render: function(data) {
                        let badge = data === 'CLOSED' ? 'success' : 'warning';
                        return '<span class="badge bg-' + badge + '">' + data + '</span>';
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        return '<a href="<?php echo e(url('\'idle-alarm/\'')); ?>/' + data + '" class="btn btn-sm btn-info">' +
                            '<i class="fas fa-eye"></i> View</a>';
                    }
                }
            ],
            pageLength: 25,
            ordering: [[0, 'desc']]
        });

        loadStatistics();
    }

    // Load device statistics
    function loadStatistics() {
        $.ajax({
            url: "<?php echo e(route('frontend.idle-alarm.data')); ?>",
            data: {
                device_id: deviceId,
                length: 999
            },
            success: function(response) {
                let alarms = response.data;
                let totalDuration = 0;

                alarms.forEach(function(alarm) {
                    totalDuration += alarm.duration_minutes;
                });

                let totalHours = (totalDuration / 60).toFixed(1);
                let avgDuration = alarms.length > 0 ? (totalDuration / alarms.length).toFixed(1) : 0;

                $('#totalIdleEvents').text(alarms.length);
                $('#totalIdleHours').text(totalHours + ' h');
                $('#avgDuration').text(avgDuration + ' min');
            }
        });
    }

    // Initialize on load
    initHistoryTable();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\frontend\device\show.blade.php ENDPATH**/ ?>