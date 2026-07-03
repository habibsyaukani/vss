

<?php $__env->startSection('title', 'Idle Alarm Detail'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-info-circle"></i> Idle Alarm Detail</h3>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo e(route('admin.idle-alarm.index')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Device Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Device ID:</th>
                            <td><strong><?php echo e($idleAlarm->device_id); ?></strong></td>
                        </tr>
                        <tr>
                            <th>Device Name:</th>
                            <td><?php echo e($idleAlarm->device_name); ?></td>
                        </tr>
                        <tr>
                            <th>Serial No:</th>
                            <td><?php echo e($idleAlarm->serial_no ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                <?php if($idleAlarm->alarm_status === 'ALARM_END'): ?>
                                    <span class="badge bg-success">ALARM_END</span>
                                <?php else: ?>
                                    <span class="badge bg-warning"><?php echo e($idleAlarm->alarm_status); ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Time Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Start Time:</th>
                            <td><?php echo e($idleAlarm->starting_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->starting_time)) : 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>End Time:</th>
                            <td><?php echo e($idleAlarm->ending_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->ending_time)) : 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Duration:</th>
                            <td><strong><?php echo e($idleAlarm->duration_minutes); ?> minutes</strong></td>
                        </tr>
                        <tr>
                            <th>Report Time:</th>
                            <td><?php echo e($idleAlarm->report_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->report_time)) : 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">Start Location</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Location:</th>
                            <td><?php echo e($idleAlarm->starting_location ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Latitude:</th>
                            <td><?php echo e($idleAlarm->latitude_start ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Longitude:</th>
                            <td><?php echo e($idleAlarm->longitude_start ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Speed:</th>
                            <td><strong><?php echo e($idleAlarm->start_speed); ?> km/h</strong></td>
                        </tr>
                        <tr>
                            <th>Detail:</th>
                            <td><?php echo e($idleAlarm->start_detail ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card mb-3">
                <div class="card-header">
                    <h6 class="mb-0">End Location</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th>Location:</th>
                            <td><?php echo e($idleAlarm->ending_location ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Latitude:</th>
                            <td><?php echo e($idleAlarm->latitude_end ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Longitude:</th>
                            <td><?php echo e($idleAlarm->longitude_end ?? 'N/A'); ?></td>
                        </tr>
                        <tr>
                            <th>Speed:</th>
                            <td><strong><?php echo e($idleAlarm->end_speed); ?> km/h</strong></td>
                        </tr>
                        <tr>
                            <th>Detail:</th>
                            <td><?php echo e($idleAlarm->end_detail ?? 'N/A'); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Summary</h6>
                </div>
                <div class="card-body">
                    <p>
                        <strong><?php echo e($idleAlarm->device_name); ?></strong> was idle for <strong><?php echo e($idleAlarm->duration_minutes); ?> minutes</strong>
                        from <strong><?php echo e($idleAlarm->starting_time ? date('H:i', strtotime($idleAlarm->starting_time)) : 'N/A'); ?></strong>
                        to <strong><?php echo e($idleAlarm->ending_time ? date('H:i', strtotime($idleAlarm->ending_time)) : 'N/A'); ?></strong>
                        on <strong><?php echo e($idleAlarm->starting_time ? date('Y-m-d', strtotime($idleAlarm->starting_time)) : 'N/A'); ?></strong>.
                        Speed increased from <strong><?php echo e($idleAlarm->start_speed); ?> km/h</strong> to <strong><?php echo e($idleAlarm->end_speed); ?> km/h</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\admin\idle-alarm\show.blade.php ENDPATH**/ ?>