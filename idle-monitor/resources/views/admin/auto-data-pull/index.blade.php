@extends('admin.layouts.app')

@section('title', 'Auto Data Pull')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4"><i class="fas fa-sync-alt"></i> Auto Data Pull Control</h3>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> This module automatically pulls Idle Alarms and GPS Tracks alternatively every {{ $settings['interval'] }} minutes.
    </div>

    <div class="row">
        <!-- Control Panel -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-sliders-h"></i> Configuration</h5>
                </div>
                <div class="card-body">
                    <form id="configForm">
                        <div class="mb-4">
                            <label class="form-label fw-bold">Auto Pull Status</label>
                            <div class="form-check form-switch fs-4">
                                <input class="form-check-input" type="checkbox" id="autoPullToggle" {{ $settings['enabled'] ? 'checked' : '' }}>
                                <label class="form-check-label fs-5 ms-2" for="autoPullToggle" id="toggleLabel">
                                    {{ $settings['enabled'] ? 'ENABLED' : 'DISABLED' }}
                                </label>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold">Interval (Minutes)</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="intervalInput" value="{{ $settings['interval'] }}" min="5" max="1440">
                                <button class="btn btn-outline-primary" type="button" id="saveIntervalBtn">Save Interval</button>
                            </div>
                            <small class="text-muted">How often the pull should happen.</small>
                        </div>
                        
                        <hr>
                        
                        <div class="d-grid">
                            <button type="button" id="runNowBtn" class="btn btn-warning btn-lg">
                                <i class="fas fa-bolt"></i> Run Pull Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Status Panel -->
        <div class="col-md-6 mb-4">
            <div class="card h-100">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-chart-line"></i> Status & Statistics</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="40%">Current Status:</th>
                            <td>
                                <span id="statusBadge" class="badge bg-secondary fs-6">Waiting</span>
                            </td>
                        </tr>
                        <tr>
                            <th>Next Task:</th>
                            <td id="nextTaskLabel" class="text-uppercase fw-bold">{{ $settings['next_type'] }}</td>
                        </tr>
                        <tr>
                            <th>Countdown:</th>
                            <td id="countdownLabel" class="fs-5 text-primary fw-bold">Loading...</td>
                        </tr>
                        <tr>
                            <th>Next Run Time:</th>
                            <td id="nextRunLabel">-</td>
                        </tr>
                        <tr>
                            <th>Last Run:</th>
                            <td id="lastRunLabel">{{ $settings['last_run'] ?? 'Never' }}</td>
                        </tr>
                        <tr>
                            <th>Last Success:</th>
                            <td id="lastSuccessLabel" class="text-success">{{ $settings['last_success'] ?? 'Never' }}</td>
                        </tr>
                        <tr>
                            <th>Last Error:</th>
                            <td id="lastErrorLabel" class="text-danger">{{ $settings['last_error'] ?? 'None' }}</td>
                        </tr>
                        <tr>
                            <th>Last Idle Records:</th>
                            <td id="lastIdleCount">{{ $settings['idle_last_count'] }}</td>
                        </tr>
                        <tr>
                            <th>Last GPS Records:</th>
                            <td id="lastGpsCount">{{ $settings['gps_last_count'] }}</td>
                        </tr>
                    </table>
                    
                    <div id="progressContainer" class="d-none mt-3">
                        <p class="mb-1 fw-bold" id="progressMessage">Processing...</p>
                        <div class="progress" style="height: 20px;">
                            <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100">0%</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    let csrfToken = $('meta[name="csrf-token"]').attr('content');
    let isRunning = false;
    
    // Initial fetch
    fetchStatus();
    
    // Status polling every 5 seconds
    setInterval(fetchStatus, 5000);

    // Toggle Enable/Disable
    $('#autoPullToggle').change(function() {
        let enabled = $(this).is(':checked');
        let label = enabled ? 'ENABLED' : 'DISABLED';
        $('#toggleLabel').text(label);
        
        $.ajax({
            url: '{{ route("admin.auto-data-pull.toggle") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                enabled: enabled ? 1 : 0
            },
            success: function(res) {
                alert(res.message);
                fetchStatus();
            },
            error: function() {
                alert('Failed to toggle status.');
                // Revert
                $('#autoPullToggle').prop('checked', !enabled);
                $('#toggleLabel').text(!enabled ? 'ENABLED' : 'DISABLED');
            }
        });
    });

    // Save Interval
    $('#saveIntervalBtn').click(function() {
        let interval = $('#intervalInput').val();
        if(interval < 5) {
            alert('Interval must be at least 5 minutes');
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '{{ route("admin.auto-data-pull.update-interval") }}',
            type: 'POST',
            data: {
                _token: csrfToken,
                interval: interval
            },
            success: function(res) {
                alert(res.message);
                $('#saveIntervalBtn').prop('disabled', false).text('Save Interval');
                fetchStatus();
            },
            error: function() {
                alert('Failed to update interval.');
                $('#saveIntervalBtn').prop('disabled', false).text('Save Interval');
            }
        });
    });

    // Run Now
    $('#runNowBtn').click(function() {
        if(!confirm('Are you sure you want to run the pull process manually now?')) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Running...');
        isRunning = true;
        
        // Show progress UI immediately
        $('#progressContainer').removeClass('d-none');
        $('#progressMessage').text('Starting manual pull...');
        $('#progressBar').css('width', '5%').text('5%').removeClass('bg-success bg-danger').addClass('bg-primary progress-bar-animated');
        
        $.ajax({
            url: '{{ route("admin.auto-data-pull.run-now") }}',
            type: 'POST',
            data: {
                _token: csrfToken
            },
            success: function(res) {
                $('#runNowBtn').prop('disabled', false).html('<i class="fas fa-bolt"></i> Run Pull Now');
                if(res.success) {
                    alert('Manual run completed successfully.');
                } else {
                    alert('Error during manual run: ' + res.message);
                }
                isRunning = false;
                fetchStatus();
            },
            error: function(xhr) {
                $('#runNowBtn').prop('disabled', false).html('<i class="fas fa-bolt"></i> Run Pull Now');
                alert('Failed to execute run now. Check logs for details.');
                isRunning = false;
                fetchStatus();
            }
        });
    });

    // Fetch Status function
    function fetchStatus() {
        $.ajax({
            url: '{{ route("admin.auto-data-pull.status") }}',
            type: 'GET',
            success: function(res) {
                // Update general fields
                if(!isRunning) {
                    $('#autoPullToggle').prop('checked', res.enabled);
                    $('#toggleLabel').text(res.enabled ? 'ENABLED' : 'DISABLED');
                }
                
                $('#nextTaskLabel').text(res.next_type ? res.next_type.toUpperCase() : 'UNKNOWN');
                $('#countdownLabel').text(res.countdown);
                $('#nextRunLabel').text(res.next_run ? res.next_run + ' (' + res.next_run_human + ')' : '-');
                $('#lastRunLabel').text(res.last_run ? res.last_run + ' (' + res.last_run_human + ')' : 'Never');
                
                $('#lastSuccessLabel').text(res.last_success ? res.last_success + ' (' + res.last_success_human + ')' : 'Never');
                
                if(res.last_error) {
                    $('#lastErrorLabel').text(res.last_error + ' at ' + res.last_error_at_human);
                } else {
                    $('#lastErrorLabel').text('None');
                }
                
                $('#lastIdleCount').text(res.idle_last_count);
                $('#lastGpsCount').text(res.gps_last_count);
                
                // Status badge
                let badgeClass = 'bg-secondary';
                if(res.status === 'running') badgeClass = 'bg-primary progress-bar-striped progress-bar-animated';
                else if(res.status === 'completed') badgeClass = 'bg-success';
                else if(res.status === 'error') badgeClass = 'bg-danger';
                
                $('#statusBadge').attr('class', 'badge fs-6 ' + badgeClass).text(res.status.toUpperCase());
                
                // Progress tracking
                if(res.status === 'running' || isRunning) {
                    $('#progressContainer').removeClass('d-none');
                    let p = res.progress_percent || 0;
                    $('#progressBar').css('width', p + '%').text(p + '%');
                    $('#progressBar').removeClass('bg-success bg-danger').addClass('bg-primary progress-bar-animated');
                    
                    let msg = res.progress_message || 'Processing...';
                    if(res.current_type) msg = '[' + res.current_type.toUpperCase() + '] ' + msg;
                    $('#progressMessage').text(msg);
                    
                    $('#runNowBtn').prop('disabled', true);
                } else {
                    if(!isRunning) {
                        $('#runNowBtn').prop('disabled', false);
                        
                        if(res.status === 'completed') {
                            $('#progressBar').removeClass('bg-primary progress-bar-animated').addClass('bg-success').css('width', '100%').text('100%');
                            $('#progressMessage').text('Completed successfully.');
                            
                            // Hide after 5 seconds
                            setTimeout(() => {
                                if($('#statusBadge').text() === 'COMPLETED') {
                                    $('#progressContainer').addClass('d-none');
                                }
                            }, 5000);
                        } else if(res.status === 'error') {
                            $('#progressBar').removeClass('bg-primary progress-bar-animated').addClass('bg-danger');
                            $('#progressMessage').text('Failed: ' + res.last_error);
                        } else {
                            $('#progressContainer').addClass('d-none');
                        }
                    }
                }
            }
        });
    }
});
</script>
@endpush
@endsection
