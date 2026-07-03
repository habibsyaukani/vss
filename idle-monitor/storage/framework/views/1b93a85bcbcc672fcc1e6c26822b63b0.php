

<?php $__env->startSection('title', 'System Settings'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <h3><i class="fas fa-cogs"></i> System Settings & Status</h3>
        </div>
    </div>

    <!-- API Status -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-<?php echo e($apiStatus['color']); ?>">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-wifi"></i> API Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Status:</h6>
                            <p>
                                <?php if($apiStatus['status'] === 'connected'): ?>
                                    <span class="badge bg-success">🟢 Connected</span>
                                <?php elseif($apiStatus['status'] === 'warning'): ?>
                                    <span class="badge bg-warning">🟡 Warning</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">🔴 Disconnected</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div class="col-md-9">
                            <h6>Message:</h6>
                            <p><?php echo e($apiStatus['message']); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Last Sync Times -->
    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock"></i> Last Alarm Sync</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="text-primary">
                        <?php if($settings['last_alarm_sync']): ?>
                            <?php echo e(date('H:i:s', strtotime($settings['last_alarm_sync']))); ?>

                        <?php else: ?>
                            Never
                        <?php endif; ?>
                    </h4>
                    <small class="text-muted">
                        <?php if($settings['last_alarm_sync']): ?>
                            <?php echo e(date('Y-m-d', strtotime($settings['last_alarm_sync']))); ?>

                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock"></i> Last Device Sync</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="text-primary">
                        <?php if($settings['last_device_sync']): ?>
                            <?php echo e(date('H:i:s', strtotime($settings['last_device_sync']))); ?>

                        <?php else: ?>
                            Never
                        <?php endif; ?>
                    </h4>
                    <small class="text-muted">
                        <?php if($settings['last_device_sync']): ?>
                            <?php echo e(date('Y-m-d', strtotime($settings['last_device_sync']))); ?>

                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-clock"></i> Last Token Refresh</h6>
                </div>
                <div class="card-body text-center">
                    <h4 class="text-primary">
                        <?php if($settings['last_token_refresh']): ?>
                            <?php echo e(date('H:i:s', strtotime($settings['last_token_refresh']))); ?>

                        <?php else: ?>
                            Never
                        <?php endif; ?>
                    </h4>
                    <small class="text-muted">
                        <?php if($settings['last_token_refresh']): ?>
                            <?php echo e(date('Y-m-d', strtotime($settings['last_token_refresh']))); ?>

                        <?php endif; ?>
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Import Jobs -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-list"></i> Recent Import Jobs</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Job Name</th>
                                <th>Started</th>
                                <th>Finished</th>
                                <th>Records</th>
                                <th>Status</th>
                                <th>Message</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $latestImports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><strong><?php echo e($log->job_name); ?></strong></td>
                                    <td><?php echo e($log->started_at ? $log->started_at->format('Y-m-d H:i:s') : '-'); ?></td>
                                    <td><?php echo e($log->finished_at ? $log->finished_at->format('Y-m-d H:i:s') : '-'); ?></td>
                                    <td><?php echo e($log->total_record); ?></td>
                                    <td>
                                        <?php if($log->status === 'completed'): ?>
                                            <span class="badge bg-success">Completed</span>
                                        <?php elseif($log->status === 'failed'): ?>
                                            <span class="badge bg-danger">Failed</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning"><?php echo e(ucfirst($log->status)); ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo e(Str::limit($log->message, 50)); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No import logs available</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- System Info -->
    <div class="row mt-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-server"></i> System Information</h6>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr>
                            <th width="30%">Application Name:</th>
                            <td><?php echo e(config('app.name')); ?></td>
                        </tr>
                        <tr>
                            <th>Environment:</th>
                            <td><?php echo e(config('app.env')); ?></td>
                        </tr>
                        <tr>
                            <th>Debug Mode:</th>
                            <td>
                                <?php if(config('app.debug')): ?>
                                    <span class="badge bg-warning">Enabled</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Disabled</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <th>Database:</th>
                            <td><?php echo e(config('database.default')); ?></td>
                        </tr>
                        <tr>
                            <th>Queue Driver:</th>
                            <td><?php echo e(config('queue.default')); ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\admin\system-setting\index.blade.php ENDPATH**/ ?>