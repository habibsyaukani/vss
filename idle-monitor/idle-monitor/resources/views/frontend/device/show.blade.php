@extends('frontend.layouts.app')

@section('title', 'Device Detail - Fleet Manager')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-truck"></i> Device Detail
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('frontend.device.index') }}" class="btn btn-secondary">
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
                            <p class="h6">{{ $device->device_name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Device ID</label>
                            <p class="h6">{{ $device->device_id }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">IMEI</label>
                            <p class="h6">{{ $device->imei ?? 'N/A' }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">SIM Number</label>
                            <p class="h6">{{ $device->sim ?? 'N/A' }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Group</label>
                            <p class="h6">
                                @if($device->group_name)
                                    <span class="badge bg-info">{{ $device->group_name }}</span>
                                @else
                                    <span class="text-muted">Not assigned</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Last Sync</label>
                            <p class="h6">
                                @if($device->last_sync_at)
                                    {{ $device->last_sync_at->format('Y-m-d H:i:s') }}
                                @else
                                    <span class="text-muted">Never</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    <!-- Status Badge -->
                    <div class="alert alert-info">
                        <strong>Current Status:</strong>
                        @if($device->last_sync_at)
                            @php
                                $mins = now()->diffInMinutes($device->last_sync_at);
                            @endphp
                            @if($mins < 30)
                                <span class="badge bg-success">Active</span> (Last sync {{ $mins }} minutes ago)
                            @elseif($mins < 120)
                                <span class="badge bg-warning">Idle</span> (Last sync {{ $mins }} minutes ago)
                            @else
                                <span class="badge bg-danger">Offline</span> (Last sync {{ $mins }} minutes ago)
                            @endif
                        @else
                            <span class="badge bg-danger">Never Synced</span>
                        @endif
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

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let historyTable;
    const deviceId = {{ $device->id }};
    
    // Initialize DataTable for idle history
    function initHistoryTable() {
        if (historyTable) {
            historyTable.destroy();
        }

        historyTable = $('#historyTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('frontend.idle-alarm.data') }}",
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
                        return '<a href="{{ url('\'idle-alarm/\'') }}/' + data + '" class="btn btn-sm btn-info">' +
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
            url: "{{ route('frontend.idle-alarm.data') }}",
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
@endsection
