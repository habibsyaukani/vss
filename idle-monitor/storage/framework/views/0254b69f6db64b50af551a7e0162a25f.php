

<?php $__env->startSection('title', 'Device List - Fleet Manager'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-truck"></i> My Devices
            </h1>
            <p class="text-muted">Monitor and view details of all vehicles in your fleet</p>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card shadow-sm mb-4">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search Filter -->
                <div class="col-md-4">
                    <label class="form-label">Search Device</label>
                    <input type="text" id="searchDevice" class="form-control" 
                        placeholder="Search by device name...">
                </div>

                <!-- Status Filter -->
                <div class="col-md-3">
                    <label class="form-label">Status</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Filter Button -->
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-success w-100" id="filterBtn">
                        <i class="fas fa-filter"></i> Filter
                    </button>
                </div>

                <!-- Reset Button -->
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-secondary w-100" id="resetBtn">
                        <i class="fas fa-redo"></i> Reset
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="deviceTable" class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Device Name</th>
                            <th>Device ID</th>
                            <th>IMEI</th>
                            <th>SIM Number</th>
                            <th>Last Sync</th>
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

<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>
<script>
$(document).ready(function() {
    let table;
    
    // Initialize DataTable
    function initTable() {
        if (table) {
            table.destroy();
        }

        table = $('#deviceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "<?php echo e(route('frontend.device.data')); ?>",
                data: function(d) {
                    d.search = $('#searchDevice').val();
                    d.status = $('#statusFilter').val();
                }
            },
            columns: [
                { data: 'device_name', name: 'device_name' },
                { data: 'device_id', name: 'device_id' },
                { data: 'imei', name: 'imei' },
                { data: 'sim', name: 'sim' },
                { 
                    data: 'last_sync_at', 
                    name: 'last_sync_at',
                    render: function(data) {
                        if (!data) return '<span class="text-muted">Never</span>';
                        let date = new Date(data);
                        return date.toLocaleString();
                    }
                },
                {
                    data: 'last_sync_at',
                    orderable: false,
                    render: function(data) {
                        if (!data) return '<span class="badge bg-danger">Offline</span>';
                        let date = new Date(data);
                        let now = new Date();
                        let mins = Math.floor((now - date) / 60000);
                        if (mins < 30) {
                            return '<span class="badge bg-success">Active</span>';
                        } else if (mins < 120) {
                            return '<span class="badge bg-warning">Idle</span>';
                        } else {
                            return '<span class="badge bg-danger">Offline</span>';
                        }
                    }
                },
                {
                    data: 'id',
                    orderable: false,
                    render: function(data) {
                        return '<a href="<?php echo e(url('\'device/\'')); ?>/' + data + '" class="btn btn-sm btn-info">' +
                            '<i class="fas fa-eye"></i> View</a>';
                    }
                }
            ],
            pageLength: 50,
            ordering: [[0, 'asc']]
        });
    }

    // Filter button
    $('#filterBtn').click(function() {
        table.ajax.reload();
    });

    // Reset button
    $('#resetBtn').click(function() {
        $('#searchDevice').val('');
        $('#statusFilter').val('');
        table.ajax.reload();
    });

    // Enter key search
    $(document).on('keypress', '#searchDevice', function(e) {
        if (e.which == 13) {
            $('#filterBtn').click();
        }
    });

    // Initialize on load
    initTable();
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views\frontend\device\index.blade.php ENDPATH**/ ?>