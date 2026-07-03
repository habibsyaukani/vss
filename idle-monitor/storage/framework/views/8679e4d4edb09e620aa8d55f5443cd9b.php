

<?php $__env->startSection('title', 'Idle Alarm Detail - Fleet Manager'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-info-circle"></i> Idle Alarm Detail
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="<?php echo e(route('frontend.idle-alarm.index')); ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="row">
        <!-- Main Details -->
        <div class="col-md-8">
            <!-- Alarm Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock"></i> Alarm Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Alarm ID</label>
                            <p class="h6"><?php echo e($alarm->id); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Alarm Status</label>
                            <p>
                                <span class="badge bg-<?php echo e($alarm->alarm_status === 'CLOSED' ? 'success' : 'warning'); ?>">
                                    <?php echo e($alarm->alarm_status); ?>

                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Start Time</label>
                            <p class="h6"><?php echo e($alarm->starting_time->format('Y-m-d H:i:s')); ?></p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Time</label>
                            <p class="h6"><?php echo e($alarm->ending_time->format('Y-m-d H:i:s')); ?></p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Duration</label>
                            <p class="h6"><?php echo e($alarm->duration_minutes); ?> minutes</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Total Duration</label>
                            <p class="h6"><?php echo e(floor($alarm->duration_minutes / 60)); ?>h <?php echo e($alarm->duration_minutes % 60); ?>m</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Speed & Movement -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tachometer-alt"></i> Speed Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Start Speed</label>
                            <p class="h6"><?php echo e($alarm->start_speed ?? 0); ?> km/h</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Speed</label>
                            <p class="h6"><?php echo e($alarm->end_speed ?? 0); ?> km/h</p>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Idle Detection:</strong>
                        Vehicle started at <?php echo e($alarm->start_speed ?? 0); ?> km/h and ended at <?php echo e($alarm->end_speed ?? 0); ?> km/h
                    </div>
                </div>
            </div>

            <!-- Location Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-map-marker-alt"></i> Location Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Start Location</label>
                            <p class="h6">
                                <?php if($alarm->latitude_start && $alarm->longitude_start): ?>
                                    <?php echo e(number_format($alarm->latitude_start, 6)); ?>, <?php echo e(number_format($alarm->longitude_start, 6)); ?>

                                    <br>
                                    <small><a href="https://maps.google.com/?q=<?php echo e($alarm->latitude_start); ?>,<?php echo e($alarm->longitude_start); ?>" 
                                        target="_blank" class="btn btn-sm btn-link">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a></small>
                                <?php else: ?>
                                    <span class="text-muted">Not available</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Location</label>
                            <p class="h6">
                                <?php if($alarm->latitude_end && $alarm->longitude_end): ?>
                                    <?php echo e(number_format($alarm->latitude_end, 6)); ?>, <?php echo e(number_format($alarm->longitude_end, 6)); ?>

                                    <br>
                                    <small><a href="https://maps.google.com/?q=<?php echo e($alarm->latitude_end); ?>,<?php echo e($alarm->longitude_end); ?>" 
                                        target="_blank" class="btn btn-sm btn-link">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a></small>
                                <?php else: ?>
                                    <span class="text-muted">Not available</span>
                                <?php endif; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Device Information -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-truck"></i> Device Information
                    </h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="text-muted">Device Name</label>
                        <p class="h6"><?php echo e($alarm->device_name); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Device ID</label>
                        <p class="h6"><?php echo e($alarm->device_id); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Serial Number</label>
                        <p class="h6"><?php echo e($alarm->serial_no ?? 'N/A'); ?></p>
                    </div>
                    <div>
                        <a href="<?php echo e(route('frontend.device.show', $alarm->id_device)); ?>" class="btn btn-sm btn-primary w-100">
                            <i class="fas fa-eye"></i> View Device History
                        </a>
                    </div>
                </div>
            </div>

            <!-- Alarm Type -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-tag"></i> Alarm Type
                    </h5>
                </div>
                <div class="card-body">
                    <p class="h6"><?php echo e($alarm->alarm_type); ?></p>
                    <span class="badge bg-secondary">Idle Detection</span>
                </div>
            </div>

            <!-- Report Time -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar"></i> Report Details
                    </h5>
                </div>
                <div class="card-body">
                    <label class="text-muted">Report Time</label>
                    <p class="h6"><?php echo e($alarm->report_time ? $alarm->report_time->format('Y-m-d H:i:s') : 'Not available'); ?></p>

                    <label class="text-muted mt-3">Created At</label>
                    <p class="h6"><?php echo e($alarm->created_at->format('Y-m-d H:i:s')); ?></p>

                    <label class="text-muted mt-3">Last Updated</label>
                    <p class="h6"><?php echo e($alarm->updated_at->format('Y-m-d H:i:s')); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\frontend\idle-alarm\show.blade.php ENDPATH**/ ?>