@extends('admin.layouts.app')

@section('title', 'System Settings')

@section('content')
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
                <div class="card-header bg-{{ $apiStatus['color'] }}">
                    <h6 class="mb-0 text-white">
                        <i class="fas fa-wifi"></i> API Status
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <h6>Status:</h6>
                            <p>
                                @if($apiStatus['status'] === 'connected')
                                    <span class="badge bg-success">🟢 Connected</span>
                                @elseif($apiStatus['status'] === 'warning')
                                    <span class="badge bg-warning">🟡 Warning</span>
                                @else
                                    <span class="badge bg-danger">🔴 Disconnected</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-9">
                            <h6>Message:</h6>
                            <p>{{ $apiStatus['message'] }}</p>
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
                        @if($settings['last_alarm_sync'])
                            {{ date('H:i:s', strtotime($settings['last_alarm_sync'])) }}
                        @else
                            Never
                        @endif
                    </h4>
                    <small class="text-muted">
                        @if($settings['last_alarm_sync'])
                            {{ date('Y-m-d', strtotime($settings['last_alarm_sync'])) }}
                        @endif
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
                        @if($settings['last_device_sync'])
                            {{ date('H:i:s', strtotime($settings['last_device_sync'])) }}
                        @else
                            Never
                        @endif
                    </h4>
                    <small class="text-muted">
                        @if($settings['last_device_sync'])
                            {{ date('Y-m-d', strtotime($settings['last_device_sync'])) }}
                        @endif
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
                        @if($settings['last_token_refresh'])
                            {{ date('H:i:s', strtotime($settings['last_token_refresh'])) }}
                        @else
                            Never
                        @endif
                    </h4>
                    <small class="text-muted">
                        @if($settings['last_token_refresh'])
                            {{ date('Y-m-d', strtotime($settings['last_token_refresh'])) }}
                        @endif
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
                            @forelse($latestImports as $log)
                                <tr>
                                    <td><strong>{{ $log->job_name }}</strong></td>
                                    <td>{{ $log->started_at ? $log->started_at->format('Y-m-d H:i:s') : '-' }}</td>
                                    <td>{{ $log->finished_at ? $log->finished_at->format('Y-m-d H:i:s') : '-' }}</td>
                                    <td>{{ $log->total_record }}</td>
                                    <td>
                                        @if($log->status === 'completed')
                                            <span class="badge bg-success">Completed</span>
                                        @elseif($log->status === 'failed')
                                            <span class="badge bg-danger">Failed</span>
                                        @else
                                            <span class="badge bg-warning">{{ ucfirst($log->status) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ Str::limit($log->message, 50) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No import logs available</td>
                                </tr>
                            @endforelse
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
                            <td>{{ config('app.name') }}</td>
                        </tr>
                        <tr>
                            <th>Environment:</th>
                            <td>{{ config('app.env') }}</td>
                        </tr>
                        <tr>
                            <th>Debug Mode:</th>
                            <td>
                                @if(config('app.debug'))
                                    <span class="badge bg-warning">Enabled</span>
                                @else
                                    <span class="badge bg-success">Disabled</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th>Database:</th>
                            <td>{{ config('database.default') }}</td>
                        </tr>
                        <tr>
                            <th>Queue Driver:</th>
                            <td>{{ config('queue.default') }}</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
