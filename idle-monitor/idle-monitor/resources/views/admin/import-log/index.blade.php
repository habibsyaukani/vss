@extends('admin.layouts.app')

@section('title', 'Import Logs')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-history"></i> Import Logs</h3>
        </div>
        <div class="col-md-4 text-end">
            <button id="refreshBtn" class="btn btn-primary">
                <i class="fas fa-sync"></i> Refresh
            </button>
            <small class="text-muted d-block mt-2">Last update: <span id="lastUpdate">-</span></small>
        </div>
    </div>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> This page auto-refreshes every 30 seconds. Logs show all system import jobs (alarms, devices, etc).
    </div>

    <div class="card">
        <div class="card-body">
            <table id="importLogTable" class="table table-hover table-sm" width="100%">
                <thead>
                    <tr>
                        <th>Job Name</th>
                        <th>Started At</th>
                        <th>Finished At</th>
                        <th>Total Record</th>
                        <th>Status</th>
                        <th>Duration</th>
                        <th>Message</th>
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
console.log('Import Log page loaded');

let logTable;

$(document).ready(function() {
    console.log('Initializing DataTable...');
    console.log('Ajax URL:', "{{ route('admin.import-log.data') }}");
    
    logTable = $('#importLogTable').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('admin.import-log.data') }}",
            error: function(xhr, error, thrown) {
                console.error('DataTables Ajax Error:', error);
                console.error('Response:', xhr.responseText);
            }
        },
        columns: [
            {data: 'job_name', name: 'job_name'},
            {data: 'started_at_formatted', name: 'started_at'},
            {data: 'finished_at_formatted', name: 'finished_at'},
            {data: 'total_record', name: 'total_record'},
            {data: 'status_badge', name: 'status', orderable: false, searchable: false},
            {data: 'duration', orderable: false, searchable: false},
            {data: 'message', name: 'message'}
        ],
        order: [[1, 'desc']],
        pageLength: 50,
        language: {
            processing: "Loading data...",
            emptyTable: "No import logs found"
        }
    });

    console.log('DataTable initialized');

    // Auto-refresh every 30 seconds
    setInterval(function() {
        logTable.ajax.reload(null, false);
        updateLastRefreshTime();
    }, 30000);

    // Manual refresh button
    $('#refreshBtn').click(function() {
        console.log('Manual refresh triggered');
        logTable.ajax.reload();
        updateLastRefreshTime();
    });

    updateLastRefreshTime();
});

function updateLastRefreshTime() {
    const now = new Date();
    const timeString = now.getHours().toString().padStart(2, '0') + ':' +
                      now.getMinutes().toString().padStart(2, '0') + ':' +
                      now.getSeconds().toString().padStart(2, '0');
    $('#lastUpdate').text(timeString);
}
</script>
@endpush
