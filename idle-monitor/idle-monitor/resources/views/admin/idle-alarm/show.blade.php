@extends('admin.layouts.app')

@section('title', 'Idle Alarm Detail')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-8">
            <h3><i class="fas fa-info-circle"></i> Idle Alarm Detail</h3>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('admin.idle-alarm.index') }}" class="btn btn-secondary">
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
                            <td><strong>{{ $idleAlarm->device_id }}</strong></td>
                        </tr>
                        <tr>
                            <th>Device Name:</th>
                            <td>{{ $idleAlarm->device_name }}</td>
                        </tr>
                        <tr>
                            <th>Serial No:</th>
                            <td>{{ $idleAlarm->serial_no ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Status:</th>
                            <td>
                                @if($idleAlarm->alarm_status === 'ALARM_END')
                                    <span class="badge bg-success">ALARM_END</span>
                                @else
                                    <span class="badge bg-warning">{{ $idleAlarm->alarm_status }}</span>
                                @endif
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
                            <td>{{ $idleAlarm->starting_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->starting_time)) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>End Time:</th>
                            <td>{{ $idleAlarm->ending_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->ending_time)) : 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Duration:</th>
                            <td><strong>{{ $idleAlarm->duration_minutes }} minutes</strong></td>
                        </tr>
                        <tr>
                            <th>Report Time:</th>
                            <td>{{ $idleAlarm->report_time ? date('Y-m-d H:i:s', strtotime($idleAlarm->report_time)) : 'N/A' }}</td>
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
                            <td>{{ $idleAlarm->starting_location ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Latitude:</th>
                            <td>{{ $idleAlarm->latitude_start ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Longitude:</th>
                            <td>{{ $idleAlarm->longitude_start ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Speed:</th>
                            <td><strong>{{ $idleAlarm->start_speed }} km/h</strong></td>
                        </tr>
                        <tr>
                            <th>Detail:</th>
                            <td>{{ $idleAlarm->start_detail ?? 'N/A' }}</td>
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
                            <td>{{ $idleAlarm->ending_location ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Latitude:</th>
                            <td>{{ $idleAlarm->latitude_end ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Longitude:</th>
                            <td>{{ $idleAlarm->longitude_end ?? 'N/A' }}</td>
                        </tr>
                        <tr>
                            <th>Speed:</th>
                            <td><strong>{{ $idleAlarm->end_speed }} km/h</strong></td>
                        </tr>
                        <tr>
                            <th>Detail:</th>
                            <td>{{ $idleAlarm->end_detail ?? 'N/A' }}</td>
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
                        <strong>{{ $idleAlarm->device_name }}</strong> was idle for <strong>{{ $idleAlarm->duration_minutes }} minutes</strong>
                        from <strong>{{ $idleAlarm->starting_time ? date('H:i', strtotime($idleAlarm->starting_time)) : 'N/A' }}</strong>
                        to <strong>{{ $idleAlarm->ending_time ? date('H:i', strtotime($idleAlarm->ending_time)) : 'N/A' }}</strong>
                        on <strong>{{ $idleAlarm->starting_time ? date('Y-m-d', strtotime($idleAlarm->starting_time)) : 'N/A' }}</strong>.
                        Speed increased from <strong>{{ $idleAlarm->start_speed }} km/h</strong> to <strong>{{ $idleAlarm->end_speed }} km/h</strong>.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
