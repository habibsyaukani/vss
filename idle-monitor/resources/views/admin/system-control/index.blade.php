@extends('admin.layouts.app')

@section('title', 'System Control')

@section('content')
<div class="container-fluid">
    <h3 class="mb-4"><i class="fas fa-cogs"></i> System Control Center</h3>

    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i> Control background processes for Queue Worker and Realtime Data Pull. Status auto-refreshes every 5 seconds.
    </div>

    <!-- Queue Worker Control -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-tasks"></i> Queue Worker Control</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="queueStatus">
                        <span class="badge {{ $queueStatus['badge_class'] }}">
                            {{ $queueStatus['badge_text'] }}
                        </span>
                    </h3>
                    @if($queueStatus['started_at'])
                        <small class="text-muted">Started at: {{ $queueStatus['started_at'] }}</small>
                    @endif
                </div>
                <div class="col-md-8">
                    <div class="d-grid gap-2 d-md-flex">
                        <button id="startQueueBtn" class="btn btn-success btn-lg" {{ $queueStatus['running'] ? 'disabled' : '' }}>
                            <i class="fas fa-play"></i> Start Queue Worker
                        </button>
                        <button id="stopQueueBtn" class="btn btn-danger btn-lg" {{ !$queueStatus['running'] ? 'disabled' : '' }}>
                            <i class="fas fa-stop"></i> Stop Queue Worker
                        </button>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>What it does:</strong> Processes background jobs (import alarms, process idle alarms, etc)
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Realtime Data Pull Control -->
    <div class="card mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-sync"></i> Realtime Data Pull Control</h5>
        </div>
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="realtimeStatus">
                        <span class="badge {{ $realtimeStatus['badge_class'] }}">
                            {{ $realtimeStatus['badge_text'] }}
                        </span>
                    </h3>
                    @if($realtimeStatus['started_at'])
                        <small class="text-muted d-block">Started: {{ $realtimeStatus['started_at'] }}</small>
                    @endif
                    @php
                        $lastSuccess = \App\Models\SystemSetting::get('realtime_pull_last_success_at');
                        $lastError = \App\Models\SystemSetting::get('realtime_pull_last_error');
                        $lastErrorAt = \App\Models\SystemSetting::get('realtime_pull_last_error_at');
                    @endphp
                    @if($lastSuccess)
                        <small class="text-success d-block">
                            <i class="fas fa-check-circle"></i> Last success: {{ \Carbon\Carbon::parse($lastSuccess)->diffForHumans() }}
                        </small>
                    @endif
                    @if($lastError && $lastErrorAt)
                        <small class="text-danger d-block" title="{{ $lastError }}">
                            <i class="fas fa-exclamation-circle"></i> Last error: {{ \Carbon\Carbon::parse($lastErrorAt)->diffForHumans() }}
                        </small>
                    @endif
                </div>
                <div class="col-md-8">
                    <div class="d-grid gap-2 d-md-flex">
                        <button id="startRealtimeBtn" class="btn btn-success btn-lg" {{ $realtimeStatus['running'] ? 'disabled' : '' }}>
                            <i class="fas fa-play"></i> Start Realtime Pull
                        </button>
                        <button id="stopRealtimeBtn" class="btn btn-danger btn-lg" {{ !$realtimeStatus['running'] ? 'disabled' : '' }}>
                            <i class="fas fa-stop"></i> Stop Realtime Pull
                        </button>
                    </div>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>What it does:</strong> Continuously pulls Idle Alarm data (last 48 hours) and GPS Track data (last 2 hours) every 3 minutes.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cleanup Control Section -->
    <div class="card mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0"><i class="fas fa-trash-alt"></i> Automatic Cleanup Control</h5>
        </div>
        <div class="card-body">
            <!-- Status Badge -->
            <div class="row align-items-center mb-3">
                <div class="col-md-4">
                    <h6>Status:</h6>
                    <h3 id="cleanupStatusBadge">
                        <span class="badge {{ $cleanupSettings['cleanup_enabled'] ? 'bg-success' : 'bg-danger' }}">
                            {{ $cleanupSettings['cleanup_enabled'] ? 'ENABLED' : 'DISABLED' }}
                        </span>
                    </h3>
                    <small class="text-muted">Last Run: <span id="cleanupLastRun">{{ $cleanupSettings['cleanup_last_run'] ?? 'Never' }}</span></small>
                </div>
                <div class="col-md-8">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-info-circle"></i> 
                        <strong>What it does:</strong> Automatically deletes old raw data (alarm_raw, gps_tracks_raw) based on retention period.<br>
                        <strong>How it works:</strong> Keeps last <strong>{{ $cleanupSettings['cleanup_retention_days'] }} days</strong> of data, deletes older data.<br>
                        <strong>Example:</strong> Today is {{ now()->format('d M Y') }} → Data before {{ now()->subDays($cleanupSettings['cleanup_retention_days'])->format('d M Y') }} will be deleted.<br>
                        <small class="text-muted"><em>Note: Only deletes data that has been processed to final tables (safe).</em></small>
                    </div>
                </div>
            </div>

            <!-- Settings Form -->
            <form id="cleanupSettingsForm">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Enable Automatic Cleanup</strong></label>
                            <select name="cleanup_enabled" class="form-control">
                                <option value="1" {{ $cleanupSettings['cleanup_enabled'] ? 'selected' : '' }}>Enabled</option>
                                <option value="0" {{ !$cleanupSettings['cleanup_enabled'] ? 'selected' : '' }}>Disabled</option>
                            </select>
                            <small class="form-text text-muted">
                                Enable or disable automatic cleanup
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Retention Period (Days)</strong></label>
                            <input type="number" name="cleanup_retention_days" class="form-control" 
                                   value="{{ $cleanupSettings['cleanup_retention_days'] }}" min="7" max="365">
                            <small class="form-text text-muted">
                                <strong>Keep last X days</strong> of data (7-365 days).<br>
                                Example: 30 = Keep 30 days, delete older data.
                            </small>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="form-group">
                            <label><strong>Schedule</strong></label>
                            <select name="cleanup_schedule" class="form-control">
                                <option value="daily" {{ $cleanupSettings['cleanup_schedule'] === 'daily' ? 'selected' : '' }}>Daily (Every day at 02:00 AM)</option>
                                <option value="weekly" {{ $cleanupSettings['cleanup_schedule'] === 'weekly' ? 'selected' : '' }}>Weekly (Every Sunday at 02:00 AM)</option>
                                <option value="monthly" {{ $cleanupSettings['cleanup_schedule'] === 'monthly' ? 'selected' : '' }}>Monthly (Every 1st of month at 02:00 AM)</option>
                            </select>
                            <small class="form-text text-muted">
                                How often cleanup runs automatically.<br>
                                <strong>Recommended:</strong> Monthly (for most cases)
                            </small>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="fas fa-save"></i> Save Settings
                        </button>
                        <button type="button" id="btnRunCleanup" class="btn btn-warning btn-lg">
                            <i class="fas fa-play"></i> Run Cleanup Now
                        </button>
                    </div>
                </div>
            </form>

            <!-- Statistics -->
            <div class="mt-4">
                <h6><i class="fas fa-chart-bar"></i> Cleanup Preview (What Will Be Deleted)</h6>
                <div class="alert alert-warning">
                    <strong><i class="fas fa-calendar-times"></i> Cutoff Date:</strong> <span id="cutoffDate">{{ $cleanupStats['cutoff_date'] }}</span><br>
                    <small>Data <strong>older than</strong> this date will be deleted. Data from this date onwards will be <strong>kept</strong>.</small>
                </div>
                
                <table class="table table-sm table-bordered">
                    <thead class="table-light">
                        <tr>
                            <th>Table</th>
                            <th>Total Records</th>
                            <th>Old Records (Will Delete)</th>
                            <th>Percentage</th>
                        </tr>
                    </thead>
                    <tbody id="cleanupStats">
                        <tr>
                            <td><strong>alarm_raw</strong></td>
                            <td id="alarmRawTotal">{{ number_format($cleanupStats['alarm_raw']['total']) }}</td>
                            <td id="alarmRawOld" class="text-danger">
                                <strong>{{ number_format($cleanupStats['alarm_raw']['old']) }}</strong>
                            </td>
                            <td id="alarmRawPct">
                                @if($cleanupStats['alarm_raw']['total'] > 0)
                                    {{ number_format(($cleanupStats['alarm_raw']['old'] / $cleanupStats['alarm_raw']['total']) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td><strong>gps_tracks_raw</strong></td>
                            <td id="gpsRawTotal">{{ number_format($cleanupStats['gps_raw']['total']) }}</td>
                            <td id="gpsRawOld" class="text-danger">
                                <strong>{{ number_format($cleanupStats['gps_raw']['old']) }}</strong>
                            </td>
                            <td id="gpsRawPct">
                                @if($cleanupStats['gps_raw']['total'] > 0)
                                    {{ number_format(($cleanupStats['gps_raw']['old'] / $cleanupStats['gps_raw']['total']) * 100, 1) }}%
                                @else
                                    0%
                                @endif
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Manual Cleanup by Month Section -->
    <div class="card mb-4">
        <div class="card-header bg-danger text-white">
            <h5 class="mb-0"><i class="fas fa-calendar-times"></i> Manual Cleanup by Month</h5>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i> 
                <strong>For Past Months Only:</strong> Use this to cleanup specific months that have already passed (+ 2 days buffer).<br>
                <strong>Example:</strong> Today is {{ now()->format('d M Y') }} → You can cleanup months before {{ now()->subDays(2)->format('F Y') }}.
            </div>

            <div class="row">
                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Select Month to Cleanup</strong></label>
                        <select id="selectMonth" class="form-control">
                            <option value="">-- Select Month --</option>
                        </select>
                        <small class="form-text text-muted">
                            Only months that have passed + 2 days buffer are shown
                        </small>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="form-group">
                        <label><strong>Actions</strong></label>
                        <div class="d-grid gap-2">
                            <button type="button" id="btnPreviewMonth" class="btn btn-info btn-lg" disabled>
                                <i class="fas fa-eye"></i> Preview Data
                            </button>
                            <button type="button" id="btnDeleteMonth" class="btn btn-danger btn-lg" disabled>
                                <i class="fas fa-trash"></i> Delete Selected Month
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Preview Results -->
            <div id="monthPreview" class="mt-3" style="display: none;">
                <h6><i class="fas fa-info-circle"></i> Preview</h6>
                <div class="alert alert-info">
                    <strong>Month:</strong> <span id="previewMonth"></span><br>
                    <strong>Date Range:</strong> <span id="previewDateRange"></span><br>
                    <strong>alarm_raw records:</strong> <span id="previewAlarmCount"></span><br>
                    <strong>gps_tracks_raw records:</strong> <span id="previewGpsCount"></span><br>
                    <strong class="text-danger">Total records to delete:</strong> <span id="previewTotalCount" class="text-danger"></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Activity Log -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0"><i class="fas fa-list"></i> Activity Log</h5>
        </div>
        <div class="card-body">
            <div id="activityLog" style="max-height: 300px; overflow-y: auto; font-family: monospace; font-size: 12px; background: #f5f5f5; padding: 15px; border-radius: 5px;">
                <div class="text-muted">Activity log will appear here...</div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    console.log('System Control page loaded');
    console.log('jQuery version:', $.fn.jquery);
    console.log('SweetAlert available:', typeof Swal !== 'undefined');
    console.log('CSRF token:', $('meta[name="csrf-token"]').attr('content'));

    // Auto-refresh status every 5 seconds
    setInterval(refreshStatus, 5000);
    
    // Load available months on page load
    loadAvailableMonths();

    // ========== QUEUE WORKER HANDLERS ==========
    
    // Start Queue Worker
    $('#startQueueBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('{{ route('admin.system-control.queue.start') }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            $('#queueStatus').html('<span class="badge bg-success">Running</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 2000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#startQueueBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Start Queue Worker');
        });
    });

    // Stop Queue Worker
    $('#stopQueueBtn').click(function() {
        if (!confirm('Are you sure you want to stop the Queue Worker?')) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('{{ route('admin.system-control.queue.stop') }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            addLog(response.message, 'warning');
            $('#queueStatus').html('<span class="badge bg-secondary">Stopped</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 1000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#stopQueueBtn').prop('disabled', false).html('<i class="fas fa-stop"></i> Stop Queue Worker');
        });
    });

    // ========== REALTIME PULL HANDLERS ==========
    
    // Start Realtime Pull
    $('#startRealtimeBtn').click(function() {
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Starting...');
        
        $.post('{{ route('admin.system-control.realtime.start') }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            addLog(response.message, 'success');
            $('#realtimeStatus').html('<span class="badge bg-success">Running</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 2000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#startRealtimeBtn').prop('disabled', false).html('<i class="fas fa-play"></i> Start Realtime Pull');
        });
    });

    // Stop Realtime Pull
    $('#stopRealtimeBtn').click(function() {
        if (!confirm('Are you sure you want to stop the Realtime Data Pull?')) return;
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Stopping...');
        
        $.post('{{ route('admin.system-control.realtime.stop') }}', {
            _token: '{{ csrf_token() }}'
        })
        .done(function(response) {
            addLog(response.message, 'warning');
            $('#realtimeStatus').html('<span class="badge bg-secondary">Stopped</span>');
            setTimeout(function() {
                refreshStatus();
                addLog('Status updated', 'info');
            }, 1000);
        })
        .fail(function(xhr) {
            addLog('Error: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            $('#stopRealtimeBtn').prop('disabled', false).html('<i class="fas fa-stop"></i> Stop Realtime Pull');
        });
    });

    // ========== CLEANUP HANDLERS ==========
    
    // Save cleanup settings
    $('#cleanupSettingsForm').on('submit', function(e) {
        e.preventDefault();
        
        // Disable submit button
        $(this).find('button[type="submit"]')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Saving...');
        
        $.ajax({
            url: '{{ route("admin.system-control.update-cleanup") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                addLog(response.message || 'Settings updated successfully', 'success');
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message || 'Cleanup settings updated successfully',
                        timer: 2000
                    });
                } else {
                    alert('SUCCESS: ' + (response.message || 'Cleanup settings updated successfully'));
                }
                
                refreshStatus();
                
                // Re-enable button
                $('#cleanupSettingsForm button[type="submit"]')
                    .prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Save Settings');
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to update settings';
                addLog('Failed to update cleanup settings: ' + errorMsg, 'danger');
                
                // Show error message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                } else {
                    alert('ERROR: ' + errorMsg);
                }
                
                // Re-enable button
                $('#cleanupSettingsForm button[type="submit"]')
                    .prop('disabled', false)
                    .html('<i class="fas fa-save"></i> Save Settings');
            }
        });
    });

    // Run cleanup manually
    $('#btnRunCleanup').on('click', function() {
        // Check if SweetAlert is available, fallback to confirm()
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Run Cleanup Now?',
                text: 'This will delete old raw data according to your retention settings.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, run cleanup',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    executeCleanup();
                }
            });
        } else {
            // Fallback to native confirm
            if (confirm('Run Cleanup Now?\n\nThis will delete old raw data according to your retention settings.\n\nAre you sure?')) {
                executeCleanup();
            }
        }
    });
    
    // Execute cleanup function
    function executeCleanup() {
        // Disable button and show loading
        $('#btnRunCleanup')
            .prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i> Running...');
        
        $.ajax({
            url: '{{ route("admin.system-control.run-cleanup") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                addLog('Cleanup job dispatched successfully', 'success');
                
                // Show success message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: 'Cleanup Started',
                        text: response.message || 'Cleanup job has been dispatched to queue.'
                    });
                } else {
                    alert('SUCCESS: ' + (response.message || 'Cleanup job has been dispatched to queue.'));
                }
                
                // Re-enable button
                $('#btnRunCleanup')
                    .prop('disabled', false)
                    .html('<i class="fas fa-play"></i> Run Cleanup Now');
                
                // Refresh status after 5 seconds
                setTimeout(refreshStatus, 5000);
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || 'Failed to run cleanup. Check logs for details.';
                addLog('Failed to run cleanup: ' + errorMsg, 'danger');
                
                // Show error message
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: errorMsg
                    });
                } else {
                    alert('ERROR: ' + errorMsg);
                }
                
                // Re-enable button
                $('#btnRunCleanup')
                    .prop('disabled', false)
                    .html('<i class="fas fa-play"></i> Run Cleanup Now');
            }
        });
    }

    // ========== STATUS REFRESH ==========
    
    // Refresh status
    function refreshStatus() {
        $.get('{{ route('admin.system-control.status') }}')
        .done(function(data) {
            // Update Queue Worker status
            $('#queueStatus').html('<span class="badge ' + data.queue.badge_class + '">' + data.queue.badge_text + '</span>');
            $('#startQueueBtn').prop('disabled', data.queue.running).html('<i class="fas fa-play"></i> Start Queue Worker');
            $('#stopQueueBtn').prop('disabled', !data.queue.running).html('<i class="fas fa-stop"></i> Stop Queue Worker');
            
            // Update Realtime Pull status
            $('#realtimeStatus').html('<span class="badge ' + data.realtime.badge_class + '">' + data.realtime.badge_text + '</span>');
            $('#startRealtimeBtn').prop('disabled', data.realtime.running).html('<i class="fas fa-play"></i> Start Realtime Pull');
            $('#stopRealtimeBtn').prop('disabled', !data.realtime.running).html('<i class="fas fa-stop"></i> Stop Realtime Pull');
            
            // Update Cleanup status
            if (data.cleanup) {
                const enabled = data.cleanup.settings.cleanup_enabled;
                $('#cleanupStatusBadge span')
                    .removeClass('bg-success bg-danger')
                    .addClass(enabled ? 'bg-success' : 'bg-danger')
                    .text(enabled ? 'ENABLED' : 'DISABLED');
                
                $('#cleanupLastRun').text(data.cleanup.settings.cleanup_last_run || 'Never');
                
                // Update stats
                const stats = data.cleanup.stats;
                $('#alarmRawTotal').text(stats.alarm_raw.total.toLocaleString());
                $('#alarmRawOld').html('<strong>' + stats.alarm_raw.old.toLocaleString() + '</strong>');
                $('#gpsRawTotal').text(stats.gps_raw.total.toLocaleString());
                $('#gpsRawOld').html('<strong>' + stats.gps_raw.old.toLocaleString() + '</strong>');
                
                // Update percentages
                if (stats.alarm_raw.total > 0) {
                    const pct = (stats.alarm_raw.old / stats.alarm_raw.total * 100).toFixed(1);
                    $('#alarmRawPct').text(pct + '%');
                }
                if (stats.gps_raw.total > 0) {
                    const pct = (stats.gps_raw.old / stats.gps_raw.total * 100).toFixed(1);
                    $('#gpsRawPct').text(pct + '%');
                }
                
                $('#cutoffDate').text(stats.cutoff_date);
            }
        });
    }

    // Add log entry
    function addLog(message, type = 'info') {
        const time = new Date().toLocaleTimeString();
        const colors = {
            success: '#28a745',
            danger: '#dc3545',
            warning: '#ffc107',
            info: '#17a2b8'
        };
        
        const logEntry = `<div style="color: ${colors[type]};">[${time}] ${message}</div>`;
        $('#activityLog').prepend(logEntry);
    }
    
    // ========== MANUAL CLEANUP BY MONTH ==========
    
    // Load available months
    function loadAvailableMonths() {
        $.get('{{ route("admin.system-control.months.available") }}')
            .done(function(data) {
                const select = $('#selectMonth');
                select.empty().append('<option value="">-- Select Month --</option>');
                
                if (data.months && data.months.length > 0) {
                    data.months.forEach(function(month) {
                        const optionText = `${month.display} (${month.total_estimate.toLocaleString()} records)`;
                        const optionValue = JSON.stringify({year: month.year, month: month.month});
                        select.append(`<option value='${optionValue}'>${optionText}</option>`);
                    });
                    
                    addLog(`Loaded ${data.months.length} months available for cleanup`, 'info');
                } else {
                    select.append('<option value="">No months available for cleanup</option>');
                    addLog('No months available for cleanup yet', 'warning');
                }
            })
            .fail(function(xhr) {
                addLog('Failed to load available months: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
            });
    }
    
    // Month selection changed
    $('#selectMonth').on('change', function() {
        const value = $(this).val();
        if (value) {
            $('#btnPreviewMonth, #btnDeleteMonth').prop('disabled', false);
            $('#monthPreview').hide();
        } else {
            $('#btnPreviewMonth, #btnDeleteMonth').prop('disabled', true);
            $('#monthPreview').hide();
        }
    });
    
    // Preview month data
    $('#btnPreviewMonth').on('click', function() {
        const monthData = JSON.parse($('#selectMonth').val());
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Loading...');
        
        $.ajax({
            url: '{{ route("admin.system-control.months.preview") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: monthData,
            success: function(response) {
                $('#previewMonth').text(response.month_display);
                $('#previewDateRange').text(response.date_range);
                $('#previewAlarmCount').text(response.alarm_raw_count.toLocaleString());
                $('#previewGpsCount').text(response.gps_raw_count.toLocaleString());
                $('#previewTotalCount').text(response.total_count.toLocaleString());
                $('#monthPreview').show();
                
                addLog('Preview loaded for ' + response.month_display, 'success');
                
                $('#btnPreviewMonth').prop('disabled', false).html('<i class="fas fa-eye"></i> Preview Data');
            },
            error: function(xhr) {
                addLog('Failed to preview: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
                alert('ERROR: ' + (xhr.responseJSON?.message || 'Failed to preview'));
                $('#btnPreviewMonth').prop('disabled', false).html('<i class="fas fa-eye"></i> Preview Data');
            }
        });
    });
    
    // Delete month data
    $('#btnDeleteMonth').on('click', function() {
        const monthData = JSON.parse($('#selectMonth').val());
        const monthText = $('#selectMonth option:selected').text();
        
        const confirmMsg = `DELETE ALL DATA FOR ${monthText}?\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?`;
        
        if (!confirm(confirmMsg)) {
            return;
        }
        
        // Double confirmation
        if (!confirm('FINAL CONFIRMATION: Delete this month\'s data permanently?')) {
            return;
        }
        
        $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Deleting...');
        
        $.ajax({
            url: '{{ route("admin.system-control.months.cleanup") }}',
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: monthData,
            success: function(response) {
                addLog('Cleanup job dispatched for ' + monthText, 'success');
                alert('SUCCESS: ' + response.message);
                
                // Reset UI
                $('#btnDeleteMonth').prop('disabled', false).html('<i class="fas fa-trash"></i> Delete Selected Month');
                $('#selectMonth').val('').trigger('change');
                $('#monthPreview').hide();
                
                // Reload available months
                setTimeout(loadAvailableMonths, 2000);
                
                // Refresh status
                setTimeout(refreshStatus, 5000);
            },
            error: function(xhr) {
                addLog('Failed to cleanup: ' + (xhr.responseJSON?.message || 'Unknown error'), 'danger');
                alert('ERROR: ' + (xhr.responseJSON?.message || 'Failed to cleanup'));
                $('#btnDeleteMonth').prop('disabled', false).html('<i class="fas fa-trash"></i> Delete Selected Month');
            }
        });
    });
});
</script>
@endpush
