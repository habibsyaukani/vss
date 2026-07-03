<?php $__env->startSection('title', 'Dashboard'); ?>

<?php $__env->startPush('styles'); ?>
<style>
    /* Dashboard specific styles */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 25px;
    }
    .page-title {
        display: flex;
        align-items: center;
        font-size: 24px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 4px;
    }
    .page-title i {
        color: #5a5ced;
        margin-right: 12px;
        font-size: 22px;
    }
    .page-subtitle {
        color: #6b7280;
        font-size: 14px;
        margin-left: 34px;
    }
    .date-picker-btn {
        background: white;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        padding: 8px 16px;
        font-size: 14px;
        color: #4b5563;
        font-weight: 500;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        display: flex;
        align-items: center;
        gap: 10px;
        cursor: pointer;
    }

    /* Stat Cards */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 20px 10px;
        display: flex;
        flex-direction: column;
        align-items: center;
        text-align: center;
        border: 1px solid #eef2f7;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
    }
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-purple { background: #f3e8ff; color: #8b5cf6; }
    .icon-green { background: #ecfdf5; color: #10b981; }
    .icon-orange { background: #fffbeb; color: #f59e0b; }
    
    .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 2px;
    }
    .stat-label {
        font-size: 13px;
        font-weight: 500;
        color: #6b7280;
        margin-bottom: 10px;
    }
    .stat-trend {
        font-size: 11px;
        color: #9ca3af;
    }

    /* Widget Cards */
    .widget-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #eef2f7;
        box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        margin-bottom: 24px;
        height: calc(100% - 24px);
        display: flex;
        flex-direction: column;
    }
    .widget-header {
        padding: 16px 20px;
        border-bottom: 1px solid #eef2f7;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .widget-title {
        font-size: 15px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .title-purple { color: #8b5cf6; }
    .title-blue { color: #3b82f6; }
    .title-green { color: #10b981; }
    .title-orange { color: #f59e0b; }
    
    .widget-select {
        border: 1px solid #e5e7eb;
        border-radius: 6px;
        font-size: 12px;
        padding: 4px 8px;
        color: #6b7280;
        background: white;
        outline: none;
    }
    .widget-body {
        padding: 20px;
        flex-grow: 1;
    }

    /* Tables */
    .custom-table {
        width: 100%;
        font-size: 13px;
    }
    .custom-table th {
        color: #6b7280;
        font-weight: 600;
        padding: 12px 10px;
        border-bottom: 1px solid #eef2f7;
        text-align: left;
    }
    .custom-table td {
        padding: 12px 10px;
        border-bottom: 1px solid #f9fafb;
        color: #374151;
        vertical-align: middle;
    }
    .custom-table tbody tr:last-child td { border-bottom: none; }
    
    .device-name { font-weight: 600; color: #111827; }
    
    .badge-status {
        padding: 4px 8px;
        border-radius: 4px;
        font-weight: 500;
        font-size: 11px;
        border: 1px solid transparent;
    }
    .badge-running { background: #fffbeb; color: #d97706; border-color: #fde68a; }
    .badge-completed { background: #ecfdf5; color: #059669; border-color: #a7f3d0; }
    .badge-failed { background: #fef2f2; color: #dc2626; border-color: #fecaca; }

    .widget-footer {
        padding: 15px;
        text-align: center;
        border-top: 1px solid #eef2f7;
    }
    .btn-outline-custom {
        background: transparent;
        border-radius: 6px;
        padding: 6px 20px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-outline-green { border: 1px solid #10b981; color: #10b981; }
    .btn-outline-green:hover { background: #ecfdf5; color: #059669; }
    .btn-outline-orange { border: 1px solid #f59e0b; color: #f59e0b; }
    .btn-outline-orange:hover { background: #fffbeb; color: #d97706; }

    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
</style>
<?php $__env->stopPush(); ?>

<?php $__env->startSection('content'); ?>
<div class="page-header">
    <div>
        <div class="page-title">
            <i class="fas fa-chart-bar"></i> Backend Dashboard
        </div>
        <div class="page-subtitle">Overview of system idle monitoring data</div>
    </div>
    <button class="date-picker-btn">
        <i class="far fa-calendar-alt text-muted"></i> 
        May 8 &ndash; May 14, 2025 
        <i class="fas fa-chevron-down ms-2" style="font-size: 10px;"></i>
    </button>
</div>

<!-- Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon icon-blue"><i class="fas fa-desktop"></i></div>
        <div class="stat-value"><?php echo e(number_format($stats['total_devices'])); ?></div>
        <div class="stat-label">Total Devices</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_devices']['color']); ?>">
                <i class="fas <?php echo e($stats['trend_devices']['icon']); ?>"></i> <?php echo e($stats['trend_devices']['value']); ?>

            </span>
            <br><?php echo e($stats['trend_devices']['label']); ?>

        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple"><i class="far fa-clock"></i></div>
        <div class="stat-value"><?php echo e(number_format($stats['total_idle_today'])); ?></div>
        <div class="stat-label">Idle Today</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_idle']['color']); ?>">
                <i class="fas <?php echo e($stats['trend_idle']['icon']); ?>"></i> <?php echo e($stats['trend_idle']['value']); ?>

            </span>
            <br><?php echo e($stats['trend_idle']['label']); ?>

        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green"><i class="fas fa-arrow-trend-up"></i></div>
        <div class="stat-value"><?php echo e(number_format($stats['total_idle_min'])); ?></div>
        <div class="stat-label">Total Idle (min)</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_total_idle']['color']); ?>">
                <i class="fas <?php echo e($stats['trend_total_idle']['icon']); ?>"></i> <?php echo e($stats['trend_total_idle']['value']); ?>

            </span>
            <br><?php echo e($stats['trend_total_idle']['label']); ?>

        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-orange"><i class="fas fa-stopwatch"></i></div>
        <div class="stat-value"><?php echo e(number_format(round($stats['avg_duration']))); ?></div>
        <div class="stat-label">Avg Duration (min)</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_avg']['color']); ?>">
                <i class="fas <?php echo e($stats['trend_avg']['icon']); ?>"></i> <?php echo e($stats['trend_avg']['value']); ?>

            </span>
            <br><?php echo e($stats['trend_avg']['label']); ?>

        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-purple"><i class="fas fa-users"></i></div>
        <div class="stat-value"><?php echo e(number_format($stats['total_users'])); ?></div>
        <div class="stat-label">Total Users</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_users']['color']); ?>"><?php echo e($stats['trend_users']['value']); ?></span>
            <br><?php echo e($stats['trend_users']['label']); ?>

        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon icon-green"><i class="fas fa-user-check"></i></div>
        <div class="stat-value"><?php echo e(number_format($stats['active_users'])); ?></div>
        <div class="stat-label">Active Users</div>
        <div class="stat-trend">
            <span class="<?php echo e($stats['trend_active_users']['color']); ?>">
                <i class="fas <?php echo e($stats['trend_active_users']['icon']); ?>"></i> <?php echo e($stats['trend_active_users']['value']); ?>

            </span>
            <br><?php echo e($stats['trend_active_users']['label']); ?>

        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row mb-4">
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title title-purple">
                    <i class="fas fa-chart-line"></i> Idle Per Hour (Last 24 Hours)
                </h5>
                <select class="widget-select">
                    <option>Last 24 Hours</option>
                </select>
            </div>
            <div class="widget-body">
                <canvas id="idlePerHourChart" height="250"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title title-blue">
                    <i class="fas fa-chart-bar"></i> Idle Per Day (Last 7 Days)
                </h5>
                <select class="widget-select">
                    <option>Last 7 Days</option>
                </select>
            </div>
            <div class="widget-body">
                <canvas id="idlePerDayChart" height="250"></canvas>
            </div>
        </div>
    </div>
</div>

<!-- Tables Row -->
<div class="row">
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title title-green">
                    <i class="fas fa-chart-pie"></i> Top 10 Devices with Idle
                </h5>
            </div>
            <div class="widget-body p-0">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 20px;">#</th>
                            <th>Device Name</th>
                            <th>Idle Count</th>
                            <th>Total Duration (min)</th>
                            <th>Last Seen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $topDevices; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td style="padding-left: 20px; color: #6b7280;"><?php echo e($index + 1); ?></td>
                            <td class="device-name"><?php echo e($device->device_name); ?></td>
                            <td><?php echo e(number_format($device->total_idle)); ?></td>
                            <td><?php echo e(number_format(round($device->total_duration))); ?></td>
                            <td style="color: #6b7280;"><?php echo e($device->last_seen ? \Carbon\Carbon::parse($device->last_seen)->format('M d, Y H:i') : '-'); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="widget-footer">
                <a href="<?php echo e(route('admin.device.index')); ?>" class="btn-outline-custom btn-outline-green">
                    View All Devices <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="widget-card">
            <div class="widget-header">
                <h5 class="widget-title title-orange">
                    <i class="fas fa-table"></i> Recent Import Logs
                </h5>
            </div>
            <div class="widget-body p-0">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th style="padding-left: 20px;">#</th>
                            <th>Job Name</th>
                            <th>Status</th>
                            <th>Records</th>
                            <th>Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $__currentLoopData = $importLogs; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $index => $log): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td style="padding-left: 20px; color: #6b7280;"><?php echo e($index + 1); ?></td>
                            <td class="device-name"><?php echo e($log->job_name); ?></td>
                            <td>
                                <?php if($log->status === 'completed'): ?>
                                    <span class="badge-status badge-completed">Completed</span>
                                <?php elseif($log->status === 'failed'): ?>
                                    <span class="badge-status badge-failed">Failed</span>
                                <?php else: ?>
                                    <span class="badge-status badge-running"><?php echo e(ucfirst($log->status)); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo e(number_format($log->total_record)); ?></td>
                            <td style="color: #6b7280;"><?php echo e($log->finished_at ? $log->finished_at->format('M d, Y H:i') : ($log->created_at ? $log->created_at->format('M d, Y H:i') : '-')); ?></td>
                        </tr>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                </table>
            </div>
            <div class="widget-footer">
                <a href="<?php echo e(route('admin.import-log.index')); ?>" class="btn-outline-custom btn-outline-orange">
                    View All Logs <i class="fas fa-chevron-right" style="font-size: 10px;"></i>
                </a>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
    // Idle Per Hour Line Chart
    const ctxHour = document.getElementById('idlePerHourChart').getContext('2d');
    
    // Gradient for line chart
    const gradientPurple = ctxHour.createLinearGradient(0, 0, 0, 300);
    gradientPurple.addColorStop(0, 'rgba(139, 92, 246, 0.4)');
    gradientPurple.addColorStop(1, 'rgba(139, 92, 246, 0.0)');

    new Chart(ctxHour, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($idlePerHour['hours']); ?>,
            datasets: [{
                label: 'Idle Count',
                data: <?php echo json_encode($idlePerHour['counts']); ?>,
                borderColor: '#8b5cf6',
                backgroundColor: gradientPurple,
                borderWidth: 2,
                pointBackgroundColor: '#8b5cf6',
                pointBorderColor: '#fff',
                pointBorderWidth: 2,
                pointRadius: 3,
                pointHoverRadius: 5,
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { borderDash: [4, 4], color: '#f3f4f6' },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });

    // Idle Per Day Bar Chart
    const ctxDay = document.getElementById('idlePerDayChart').getContext('2d');
    new Chart(ctxDay, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($idlePerDay['days']); ?>,
            datasets: [{
                label: 'Idle Count',
                data: <?php echo json_encode($idlePerDay['counts']); ?>,
                backgroundColor: '#3b82f6',
                borderRadius: 4,
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { display: false },
                    border: { display: false }
                },
                x: {
                    grid: { display: false },
                    border: { display: false }
                }
            }
        }
    });
</script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\admin\dashboard.blade.php ENDPATH**/ ?>