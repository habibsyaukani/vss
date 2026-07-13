@extends('admin.layouts.app')

@section('title', 'Device Management')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-car"></i> Device Management</h3>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.device.create') }}" class="btn btn-primary me-2">
                <i class="fas fa-plus"></i> Add Device
            </a>
            <a href="{{ route('admin.device.import-form') }}" class="btn btn-success">
                <i class="fas fa-upload"></i> Import CSV
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Filters -->
    <div class="card mb-3">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Filter by Group</label>
                    <select id="filterGroup" class="form-select form-select-sm">
                        <option value="all">-- All Groups --</option>
                        <option value="BUS - GPE">BUS - GPE</option>
                        <option value="DT - GPE">DT - GPE</option>
                        <option value="FT - GPE">FT - GPE</option>
                        <option value="HD - GPE">HD - GPE</option>
                        <option value="PATROL - GPE">PATROL - GPE</option>
                        <option value="WT - GPE">WT - GPE</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Filter by Series</label>
                    <select id="filterSeries" class="form-select form-select-sm">
                        <option value="all">Semua Series</option>
                        <option value="OHT 773">OHT 773</option>
                        <option value="DT HINO">DT HINO</option>
                        <option value="DT VOLVO">DT VOLVO</option>
                        <option value="HD 465">HD 465</option>
                        <option value="HD 785">HD 785</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Filter by Location</label>
                    <select id="filterLocation" class="form-select form-select-sm">
                        <option value="all">Semua Location</option>
                        <option value="JO SELATAN">JO SELATAN</option>
                        <option value="MUD">MUD</option>
                        <option value="SELATAN">SELATAN</option>
                        <option value="UTARA">UTARA</option>
                        <option value="M.SERVICE">M.SERVICE</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Filter by Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="all">-- All Status --</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-body">
            <table class="table table-hover table-sm datatable" width="100%" style="font-size: 12px;">
                <thead>
                    <tr>
                        <th>Device ID</th>
                        <th>Device Name</th>
                        <th>Unit Code</th>
                        <th>Location</th>
                        <th>Series</th>
                        <th>Group Name</th>
                        <th>Plate No</th>
                        <th>IMEI</th>
                        <th>SIM Number</th>
                        <th>Status</th>
                        <th>Last Sync</th>
                        <th>Created At</th>
                        <th>Updated At</th>
                        <th>Group ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function() {
    console.log('✅ Device Management JavaScript loaded');
    console.log('DataTables available?', typeof $.fn.DataTable !== 'undefined');
    console.log('AJAX URL:', "{{ route('admin.device.data') }}");
    
    const table = $('.datatable').DataTable({
        processing: true,
        serverSide: true,
        scrollX: true,
        ajax: {
            url: "{{ route('admin.device.data') }}",
            data: function(d) {
                d.group_name = $('#filterGroup').val();
                d.series = $('#filterSeries').val();
                d.location = $('#filterLocation').val();
                d.status = $('#filterStatus').val();
            }
        },
        columns: [
            {data: 'device_id'},
            {data: 'device_name'},
            {data: 'unit_code'},
            {data: 'location'},
            {data: 'series'},
            {data: 'group_name'},
            {data: 'plate_no'},
            {data: 'imei'},
            {data: 'sim_number'},
            {data: 'status_badge', orderable: false, searchable: false},
            {data: 'last_sync_formatted'},
            {data: 'created_at_formatted'},
            {data: 'updated_at_formatted'},
            {data: 'group_id'},
            {data: 'actions', orderable: false, searchable: false}
        ],
        error: function(xhr, error, thrown) {
            console.error('❌ DataTables error:', {xhr, error, thrown});
            alert('Error loading devices: ' + error);
        }
    });

    // Auto-filter on dropdown change
    $('#filterGroup, #filterSeries, #filterLocation, #filterStatus').on('change', function() {
        console.log('Filter changed, reloading table...');
        table.ajax.reload();
    });

    // Delete device
    $(document).on('click', '.btn-delete', function() {
        const id = $(this).data('id');
        if (confirm('Are you sure want to delete this device?')) {
            $.ajax({
                type: 'DELETE',
                url: '/admin/device/' + id,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function() {
                    table.ajax.reload();
                    alert('Device deleted successfully!');
                },
                error: function(xhr) {
                    console.error('Delete error:', xhr);
                    alert('Error: ' + (xhr.responseJSON ? xhr.responseJSON.message : 'Unknown error'));
                }
            });
        }
    });
});
</script>
@endpush
