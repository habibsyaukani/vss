@extends('admin.layouts.app')

@section('title', 'Import Devices')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <h3><i class="fas fa-upload"></i> Import Devices from CSV</h3>

            <div class="card mt-3">
                <div class="card-body">
                    <div class="alert alert-info">
                        <strong><i class="fas fa-info-circle"></i> Instructions:</strong>
                        <ul class="mb-0 mt-2">
                            <li>CSV file must have header row (e.g. <b>device_name, lokasi, unit_code, series</b>)</li>
                            <li>You can include any of these columns: <b>device_id, device_name, group_name, unit_code, lokasi, series, plate_no, imei, sim_number, status</b></li>
                            <li>Existing devices will be automatically <b>UPDATED</b> if they match `device_id` or `device_name`</li>
                            <li>Maximum file size: 5MB</li>
                        </ul>
                    </div>

                    <form id="importForm" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="mb-3">
                            <label class="form-label">Select CSV File *</label>
                            <input type="file" name="file" class="form-control" accept=".csv,.txt" required>
                            <small class="form-text text-muted">Accepted formats: CSV, TXT</small>
                        </div>

                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="{{ route('admin.device.index') }}" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-cloud-upload-alt"></i> Upload & Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Sample CSV -->
            <div class="card mt-4">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-file-csv"></i> Sample CSV Format</h6>
                </div>
                <div class="card-body">
                    <pre style="background: #f5f5f5; padding: 10px; border-radius: 5px;">device_name,lokasi,unit_code,series
GPE-B-8322,Area Operasional,GPE8322,DOZER
GPE-FT-873,M.SERVICE,GPE873,FUEL TRUCK
GPE-DT-1000,UTARA,GPE1000,DT VOLVO</pre>
                    <a href="javascript:void(0)" onclick="downloadSample()" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-download"></i> Download Sample
                    </a>
                </div>
            </div>

            <!-- Import Results -->
            <div id="resultContainer" class="card mt-4" style="display: none;">
                <div class="card-header">
                    <h6 class="mb-0"><i class="fas fa-check-circle"></i> Import Results</h6>
                </div>
                <div class="card-body">
                    <div id="resultContent"></div>
                    <a href="{{ route('admin.device.index') }}" class="btn btn-primary mt-3">
                        <i class="fas fa-arrow-left"></i> Back to Devices
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(function() {
    $('#importForm').on('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);
        const $submitBtn = $('button[type="submit"]');
        $submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Uploading...');

        $.ajax({
            type: 'POST',
            url: "{{ route('admin.device.import') }}",
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                showResults(response, 'success');
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                showResults(response, 'error');
            },
            complete: function() {
                $submitBtn.prop('disabled', false).html('<i class="fas fa-cloud-upload-alt"></i> Upload & Import');
            }
        });
    });

    function showResults(response, type) {
        const resultHtml = `
            <div class="alert alert-${type === 'success' ? 'success' : 'danger'}">
                <strong>${response.message}</strong>
            </div>
            ${response.errors && response.errors.length > 0 ? `
                <div class="alert alert-warning mt-2">
                    <strong>Warnings/Errors:</strong>
                    <ul class="mb-0 mt-2">
                        ${response.errors.map(e => `<li>${e}</li>`).join('')}
                    </ul>
                </div>
            ` : ''}
            <div class="alert alert-info mt-2">
                <strong>Summary:</strong>
                <ul class="mb-0">
                    <li><strong>${response.imported || 0}</strong> devices imported/updated</li>
                </ul>
            </div>
        `;
        
        $('#resultContent').html(resultHtml);
        $('#resultContainer').show();
        $('#importForm').hide();
        $('html, body').animate({ scrollTop: $('#resultContainer').offset().top }, 500);
    }
});

function downloadSample() {
    const csv = 'device_name,lokasi,unit_code,series\nGPE-B-8322,Area Operasional,GPE8322,DOZER\nGPE-FT-873,M.SERVICE,GPE873,FUEL TRUCK\nGPE-DT-1000,UTARA,GPE1000,DT VOLVO';
    const blob = new Blob([csv], { type: 'text/csv' });
    const link = document.createElement('a');
    link.href = window.URL.createObjectURL(blob);
    link.download = 'devices-sample.csv';
    link.click();
}
</script>
@endsection
