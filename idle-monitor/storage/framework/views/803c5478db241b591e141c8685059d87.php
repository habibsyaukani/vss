

<?php $__env->startSection('title', 'Idle Alarms'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-pause-circle"></i> Idle Alarms Monitoring</h3>
        </div>
        <div class="col-md-4 text-end">
            <button id="exportBtn" class="btn btn-success">
                <i class="fas fa-download"></i> Export CSV
            </button>
        </div>
    </div>

    <?php if(session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?php echo e(session('success')); ?>

            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Advanced Filters -->
    <div class="card mb-3">
        <div class="card-header">
            <h6 class="mb-0"><i class="fas fa-filter"></i> Filters</h6>
        </div>
        <div class="card-body">
            <div class="row g-2">
                <div class="col-md-2">
                    <label class="form-label">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="ALARM_END">ALARM_END</option>
                        <option value="ALARMING">ALARMING</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Start Date</label>
                    <input type="date" id="filterStartDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">End Date</label>
                    <input type="date" id="filterEndDate" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Min Duration (min)</label>
                    <input type="number" id="filterMinDuration" class="form-control form-control-sm" min="0">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Group</label>
                    <select id="filterGroup" class="form-select form-select-sm">
                        <option value="">All Groups</option>
                        <?php $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($group->group_name); ?>"><?php echo e($group->group_name); ?></option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button id="filterBtn" class="btn btn-sm btn-secondary w-100">
                        <i class="fas fa-search"></i> Filter
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm datatable" width="100%">
                    <thead>
                        <tr>
                            <th>Serial No</th>
                            <th>Device Name</th>
                            <th>Status</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Duration (min)</th>
                            <th>Speed (km/h)</th>
                            <th>Report Time</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    const table = $('.datatable').DataTable({
        processing: true,
        serverSide: true,
        pageLength: 25,
        ajax: {
            url: "<?php echo e(route('admin.idle-alarm.data')); ?>",
            data: function(d) {
                d.status = $('#filterStatus').val();
                d.start_date = $('#filterStartDate').val();
                d.end_date = $('#filterEndDate').val();
                d.min_duration = $('#filterMinDuration').val();
                d.group = $('#filterGroup').val();
            }
        },
        columns: [
            {data: 'serial_no'},
            {data: 'device_name'},
            {data: 'status_badge', orderable: false, searchable: false},
            {data: 'starting_time_formatted'},
            {data: 'ending_time_formatted'},
            {data: 'duration_minutes'},
            {data: 'speed_info', orderable: false},
            {data: 'report_time'},
            {data: 'actions', orderable: false, searchable: false}
        ],
        order: [[3, 'desc']]
    });

    $('#filterBtn').click(function() {
        table.ajax.reload();
    });

    $('#exportBtn').click(function() {
        const params = new URLSearchParams({
            status: $('#filterStatus').val(),
            start_date: $('#filterStartDate').val(),
            end_date: $('#filterEndDate').val(),
            min_duration: $('#filterMinDuration').val(),
            group: $('#filterGroup').val()
        });
        window.location.href = "<?php echo e(route('admin.idle-alarm.export')); ?>?" + params.toString();
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('admin.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\admin\idle-alarm\index.blade.php ENDPATH**/ ?>