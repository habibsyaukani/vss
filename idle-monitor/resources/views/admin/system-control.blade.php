@extends('admin.layouts.app')

@section('title', 'System Control Center')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-cogs"></i> System Control Center
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> 
                        Control background processes and automatic cleanup. Status auto-refreshes every 10 seconds.
                    </div>

                    <!-- Cleanup Control Section -->
                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-trash-alt"></i> Automatic Cleanup Control
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Status Badge -->
                            <div class="mb-3">
                                <strong>Status:</strong>
                                <span id="cleanup-status-badge" class="badge badge-{{ $settings['cleanup_enabled'] ? 'success' : 'danger' }}">
                                    {{ $settings['cleanup_enabled'] ? 'ENABLED' : 'DISABLED' }}
                                </span>
                            </div>

                            <!-- Last Run -->
                            <div class="mb-3">
                                <strong>Last Run:</strong>
                                <span id="cleanup-last-run">
                                    {{ $settings['cleanup_last_run'] ?? 'Never' }}
                                </span>
                            </div>

                            <!-- Settings Form -->
                            <form id="cleanup-settings-form">
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Enable Automatic Cleanup</label>
                                            <select name="cleanup_enabled" class="form-control">
                                                <option value="1" {{ $settings['cleanup_enabled'] ? 'selected' : '' }}>Enabled</option>
                                                <option value="0" {{ !$settings['cleanup_enabled'] ? 'selected' : '' }}>Disabled</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                Enable or disable automatic cleanup
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Retention Period (Days)</label>
                                            <input type="number" name="cleanup_retention_days" class="form-control" 
                                                   value="{{ $settings['cleanup_retention_days'] }}" min="7" max="365">
                                            <small class="form-text text-muted">
                                                Keep data for this many days (7-365)
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>Schedule</label>
                                            <select name="cleanup_schedule" class="form-control">
                                                <option value="daily" {{ $settings['cleanup_schedule'] === 'daily' ? 'selected' : '' }}>Daily</option>
                                                <option value="weekly" {{ $settings['cleanup_schedule'] === 'weekly' ? 'selected' : '' }}>Weekly</option>
                                                <option value="monthly" {{ $settings['cleanup_schedule'] === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                            <small class="form-text text-muted">
                                                How often to run cleanup
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row mt-3">
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Save Settings
                                        </button>
                                        <button type="button" id="btn-run-cleanup" class="btn btn-warning">
                                            <i class="fas fa-play"></i> Run Cleanup Now
                                        </button>
                                    </div>
                                </div>
                            </form>

                            <!-- Statistics -->
                            <div class="mt-4">
                                <h6>Cleanup Preview</h6>
                                <p class="text-muted">
                                    Data older than: <strong id="cutoff-date">{{ $stats['cutoff_date'] }}</strong>
                                </p>
                                
                                <table class="table table-sm table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Table</th>
                                            <th>Total Records</th>
                                            <th>Old Records (Will Delete)</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody id="cleanup-stats">
                                        <tr>
                                            <td><strong>alarm_raw</strong></td>
                                            <td id="alarm-raw-total">{{ number_format($stats['alarm_raw']['total']) }}</td>
                                            <td id="alarm-raw-old" class="text-danger">
                                                <strong>{{ number_format($stats['alarm_raw']['old']) }}</strong>
                                            </td>
                                            <td id="alarm-raw-pct">
                                                @if($stats['alarm_raw']['total'] > 0)
                                                    {{ number_format(($stats['alarm_raw']['old'] / $stats['alarm_raw']['total']) * 100, 1) }}%
                                                @else
                                                    0%
                                                @endif
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>gps_tracks_raw</strong></td>
                                            <td id="gps-raw-total">{{ number_format($stats['gps_raw']['total']) }}</td>
                                            <td id="gps-raw-old" class="text-danger">
                                                <strong>{{ number_format($stats['gps_raw']['old']) }}</strong>
                                            </td>
                                            <td id="gps-raw-pct">
                                                @if($stats['gps_raw']['total'] > 0)
                                                    {{ number_format(($stats['gps_raw']['old'] / $stats['gps_raw']['total']) * 100, 1) }}%
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

                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
$(document).ready(function() {
    // Save cleanup settings
    $('#cleanup-settings-form').on('submit', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: '{{ route("admin.system-control.update-cleanup") }}',
            method: 'POST',
            data: $(this).serialize(),
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: response.message,
                    timer: 2000
                });
                refreshStatus();
            },
            error: function(xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON?.message || 'Failed to update settings'
                });
            }
        });
    });

    // Run cleanup manually
    $('#btn-run-cleanup').on('click', function() {
        Swal.fire({
            title: 'Run Cleanup Now?',
            text: 'This will delete old raw data according to your retention settings.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, run cleanup',
            cancelButtonText: 'Cancel'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: '{{ route("admin.system-control.run-cleanup") }}',
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    success: function(response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Cleanup Started',
                            text: response.message
                        });
                        setTimeout(refreshStatus, 5000);
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: xhr.responseJSON?.message || 'Failed to run cleanup'
                        });
                    }
                });
            }
        });
    });

    // Auto-refresh status every 10 seconds
    function refreshStatus() {
        $.get('{{ route("admin.system-control.status") }}', function(data) {
            // Update status badge
            const enabled = data.settings.cleanup_enabled;
            $('#cleanup-status-badge')
                .removeClass('badge-success badge-danger')
                .addClass(enabled ? 'badge-success' : 'badge-danger')
                .text(enabled ? 'ENABLED' : 'DISABLED');
            
            // Update last run
            $('#cleanup-last-run').text(data.settings.cleanup_last_run || 'Never');
            
            // Update stats
            $('#alarm-raw-total').text(data.stats.alarm_raw.total.toLocaleString());
            $('#alarm-raw-old').html('<strong>' + data.stats.alarm_raw.old.toLocaleString() + '</strong>');
            $('#gps-raw-total').text(data.stats.gps_raw.total.toLocaleString());
            $('#gps-raw-old').html('<strong>' + data.stats.gps_raw.old.toLocaleString() + '</strong>');
            
            // Update percentages
            if (data.stats.alarm_raw.total > 0) {
                const pct = (data.stats.alarm_raw.old / data.stats.alarm_raw.total * 100).toFixed(1);
                $('#alarm-raw-pct').text(pct + '%');
            }
            if (data.stats.gps_raw.total > 0) {
                const pct = (data.stats.gps_raw.old / data.stats.gps_raw.total * 100).toFixed(1);
                $('#gps-raw-pct').text(pct + '%');
            }
            
            $('#cutoff-date').text(data.stats.cutoff_date);
        });
    }

    // Start auto-refresh
    setInterval(refreshStatus, 10000);
});
</script>
@endpush
@endsection
