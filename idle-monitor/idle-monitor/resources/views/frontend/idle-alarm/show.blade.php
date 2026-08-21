@extends('frontend.layouts.app')

@section('title', 'Idle Alarm Detail - Fleet Manager')

@section('content')
<div class="container-fluid">
    <!-- Page Header -->
    <div class="row mb-4">
        <div class="col-md-8">
            <h1 class="h2">
                <i class="fas fa-info-circle"></i> Idle Alarm Detail
            </h1>
        </div>
        <div class="col-md-4 text-end">
            <a href="{{ route('frontend.idle-alarm.index') }}" class="btn btn-secondary">
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
                            <p class="h6">{{ $alarm->id }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Alarm Status</label>
                            <p>
                                <span class="badge bg-{{ $alarm->alarm_status === 'CLOSED' ? 'success' : 'warning' }}">
                                    {{ $alarm->alarm_status }}
                                </span>
                            </p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Start Time</label>
                            <p class="h6">{{ $alarm->starting_time->format('Y-m-d H:i:s') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Time</label>
                            <p class="h6">{{ $alarm->ending_time->format('Y-m-d H:i:s') }}</p>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="text-muted">Duration</label>
                            <p class="h6">{{ $alarm->duration_minutes }} minutes</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">Total Duration</label>
                            <p class="h6">{{ floor($alarm->duration_minutes / 60) }}h {{ $alarm->duration_minutes % 60 }}m</p>
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
                            <p class="h6">{{ $alarm->start_speed ?? 0 }} km/h</p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Speed</label>
                            <p class="h6">{{ $alarm->end_speed ?? 0 }} km/h</p>
                        </div>
                    </div>

                    <div class="alert alert-info">
                        <i class="fas fa-lightbulb"></i>
                        <strong>Idle Detection:</strong>
                        Vehicle started at {{ $alarm->start_speed ?? 0 }} km/h and ended at {{ $alarm->end_speed ?? 0 }} km/h
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
                                @if($alarm->latitude_start && $alarm->longitude_start)
                                    {{ number_format($alarm->latitude_start, 6) }}, {{ number_format($alarm->longitude_start, 6) }}
                                    <br>
                                    <small><a href="https://maps.google.com/?q={{ $alarm->latitude_start }},{{ $alarm->longitude_start }}" 
                                        target="_blank" class="btn btn-sm btn-link">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a></small>
                                @else
                                    <span class="text-muted">Not available</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="text-muted">End Location</label>
                            <p class="h6">
                                @if($alarm->latitude_end && $alarm->longitude_end)
                                    {{ number_format($alarm->latitude_end, 6) }}, {{ number_format($alarm->longitude_end, 6) }}
                                    <br>
                                    <small><a href="https://maps.google.com/?q={{ $alarm->latitude_end }},{{ $alarm->longitude_end }}" 
                                        target="_blank" class="btn btn-sm btn-link">
                                        <i class="fas fa-external-link-alt"></i> View on Map
                                    </a></small>
                                @else
                                    <span class="text-muted">Not available</span>
                                @endif
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
                        <p class="h6">{{ $alarm->device_name }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Device ID</label>
                        <p class="h6">{{ $alarm->device_id }}</p>
                    </div>
                    <div class="mb-3">
                        <label class="text-muted">Serial Number</label>
                        <p class="h6">{{ $alarm->serial_no ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <a href="{{ route('frontend.device.show', $alarm->id_device) }}" class="btn btn-sm btn-primary w-100">
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
                    <p class="h6">{{ $alarm->alarm_type }}</p>
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
                    <p class="h6">{{ $alarm->report_time ? $alarm->report_time->format('Y-m-d H:i:s') : 'Not available' }}</p>

                    <label class="text-muted mt-3">Created At</label>
                    <p class="h6">{{ $alarm->created_at->format('Y-m-d H:i:s') }}</p>

                    <label class="text-muted mt-3">Last Updated</label>
                    <p class="h6">{{ $alarm->updated_at->format('Y-m-d H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
