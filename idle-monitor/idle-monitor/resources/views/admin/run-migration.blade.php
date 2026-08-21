@extends('admin.layouts.app')

@section('title', 'Run Migration - Batch Pull')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="mb-0"><i class="fas fa-database"></i> Run Migration - Batch Data Pull</h2>
            <p class="text-muted">One-time setup untuk membuat table data_pull_batches</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-cog"></i> Migration Status</h5>
                </div>
                <div class="card-body">
                    @if($table_exists)
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> Migration Already Completed!</h5>
                            <p class="mb-0">Table <code>data_pull_batches</code> sudah ada di database.</p>
                            <hr>
                            <p class="mb-0">
                                <strong>Next Step:</strong> 
                                <a href="{{ route('admin.data-pull.index') }}" class="btn btn-primary btn-sm">
                                    <i class="fas fa-arrow-right"></i> Go to Data Pull Page
                                </a>
                            </p>
                        </div>
                    @else
                        <div class="alert alert-warning">
                            <h5><i class="fas fa-exclamation-triangle"></i> Migration Needed</h5>
                            <p class="mb-0">Table <code>data_pull_batches</code> belum ada di database.</p>
                        </div>

                        <div class="card bg-light mb-3">
                            <div class="card-body">
                                <h6>What will be created:</h6>
                                <ul>
                                    <li>Table: <code>data_pull_batches</code></li>
                                    <li>Columns: id, session_id, batch_number, date, time_start, time_end, status, total_records, error_message, started_at, completed_at, timestamps</li>
                                    <li>Indexes: session_id, status, composite indexes</li>
                                </ul>
                                
                                <div class="alert alert-info mb-0">
                                    <small>
                                        <i class="fas fa-shield-alt"></i> <strong>100% AMAN:</strong> 
                                        Hanya CREATE table baru, tidak mengubah atau menghapus data existing.
                                    </small>
                                </div>
                            </div>
                        </div>

                        <button type="button" id="runMigrationBtn" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-play"></i> Run Migration Now
                        </button>

                        <div id="migrationResult" style="display: none;" class="mt-3"></div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@if(!$table_exists)
<script>
$(document).ready(function() {
    $('#runMigrationBtn').on('click', function() {
        const btn = $(this);
        const resultDiv = $('#migrationResult');
        
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Running Migration...');
        resultDiv.hide();
        
        $.ajax({
            url: '{{ route("admin.run-migration.execute") }}',
            method: 'POST',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    resultDiv.html(`
                        <div class="alert alert-success">
                            <h5><i class="fas fa-check-circle"></i> ${response.message}</h5>
                            <pre class="bg-dark text-light p-3 mt-2" style="max-height: 200px; overflow-y: auto;">${response.output}</pre>
                            <hr>
                            <p class="mb-0">
                                <a href="{{ route('admin.data-pull.index') }}" class="btn btn-primary">
                                    <i class="fas fa-arrow-right"></i> Go to Data Pull Page
                                </a>
                            </p>
                        </div>
                    `).show();
                    btn.remove();
                } else {
                    resultDiv.html(`
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-times-circle"></i> Migration Failed</h5>
                            <p>${response.message}</p>
                            <pre class="bg-dark text-light p-3 mt-2" style="max-height: 200px; overflow-y: auto;">${response.output}</pre>
                        </div>
                    `).show();
                    btn.prop('disabled', false).html('<i class="fas fa-play"></i> Try Again');
                }
            },
            error: function(xhr) {
                const errorMsg = xhr.responseJSON?.message || xhr.statusText;
                resultDiv.html(`
                    <div class="alert alert-danger">
                        <h5><i class="fas fa-times-circle"></i> Error</h5>
                        <p>${errorMsg}</p>
                    </div>
                `).show();
                btn.prop('disabled', false).html('<i class="fas fa-play"></i> Try Again');
            }
        });
    });
});
</script>
@endif
@endsection
