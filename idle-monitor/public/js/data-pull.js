// Data Pull JavaScript
// This file handles all data pull functionality

// CSRF Token setup
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // Refresh CSRF token setiap 30 menit agar tidak expired
    setInterval(function() {
        $.get('/csrf-refresh').done(function(data) {
            if (data && data.token) {
                $('meta[name="csrf-token"]').attr('content', data.token);
                $('[name="_token"]').val(data.token);
                $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                console.log('🔄 CSRF token refreshed');
            }
        });
    }, 30 * 60 * 1000); // tiap 30 menit
});

// Debug: Verify script is loading
console.log('✅ Data Pull JavaScript loaded successfully!');
console.log('jQuery version:', typeof $ !== 'undefined' ? $.fn.jquery : 'jQuery NOT LOADED');

$(document).ready(function() {
    console.log('✅ Document ready fired!');
    
    // Set default dates
    const today = new Date().toISOString().split('T')[0];
    $('#to_date').val(today);
    
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    $('#from_date').val(yesterday.toISOString().split('T')[0]);
    
    console.log('✅ Default dates set:', {from: $('#from_date').val(), to: $('#to_date').val()});

    // Form submission
    $('#dataPullForm').on('submit', function(e) {
        e.preventDefault();
        console.log('🚀 Form submitted, calling executePull()...');
        executePull();
    });

    // Refresh statistics every 30 seconds
    setInterval(refreshStatistics, 30000);
    
    console.log('✅ Form event listener attached!');
});

function executePull() {
    console.log('🔵 executePull() function called!');
    
    const form = $('#dataPullForm');
    const button = $('#pullButton');
    const logContainer = $('#logContainer');
    const progressContainer = $('#progressContainer');
    const progressBar = $('#progressBar');
    const progressPercentage = $('#progressPercentage');
    const progressStatusText = $('#progressStatusText');
    const progressDetails = $('#progressDetails');
    const realtimeStats = $('#realtimeStats');
    
    console.log('🔵 Elements found:', {
        form: form.length,
        button: button.length,
        progressContainer: progressContainer.length
    });
    
    // Calculate estimated time
    const fromDate = new Date($('#from_date').val());
    const toDate = new Date($('#to_date').val());
    const daysDiff = Math.ceil((toDate - fromDate) / (1000 * 60 * 60 * 24)) + 1;
    const estimatedMinutes = daysDiff * 1.5; // ~1.5 minutes per day average
    const estimatedTime = estimatedMinutes < 2 ? '1-2 menit' : 
                          estimatedMinutes < 5 ? '2-5 menit' : 
                          estimatedMinutes < 10 ? '5-10 menit' : 
                          '10-15 menit';
    
    console.log(`🔵 Estimated time for ${daysDiff} days: ${estimatedTime}`);

    // Disable button
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Sedang Menarik Data...');
    console.log('🔵 Button disabled, progress starting...');
    
    // Show progress
    progressContainer.show();
    realtimeStats.hide();
    
    // Reset progress
    progressBar.css('width', '0%').removeClass('bg-danger').addClass('bg-success');
    progressPercentage.text('0%');
    progressStatusText.html('<i class="fas fa-spinner fa-spin"></i> Memulai penarikan data...');
    progressDetails.text(`Estimasi waktu: ${estimatedTime}. Mohon bersabar...`);

    // Clear log
    logContainer.html(`
        <div class="alert alert-info mb-2" style="background: white;">
            <i class="fas fa-spinner fa-spin"></i> <strong>Penarikan Data Dimulai</strong>
            <p class="mb-0 mt-2 small">Memproses <strong>${daysDiff} hari</strong>. Estimasi: <strong>${estimatedTime}</strong>. Jangan tutup halaman ini!</p>
        </div>
    `);

    // Animate progress to 20%
    setTimeout(() => {
        progressBar.css('width', '20%');
        progressPercentage.text('20%');
        progressDetails.text('Mengambil data dari server...');
    }, 500);

    // Get form data
    const formData = form.serialize();
    const fromDateStr = $('#from_date').val();
    const toDateStr = $('#to_date').val();

    // Add info log
    logContainer.append(`
        <div class="alert alert-secondary mb-2" style="background: white; border-left: 4px solid #17a2b8;">
            <small>
                <i class="fas fa-calendar"></i> <strong>Range:</strong> ${fromDateStr} s/d ${toDateStr}<br>
                <i class="fas fa-clock"></i> <strong>Waktu Mulai:</strong> ${new Date().toLocaleString('id-ID')}
            </small>
        </div>
    `);

    // Animate progress to 40%
    setTimeout(() => {
        progressBar.css('width', '40%');
        progressPercentage.text('40%');
        progressDetails.text('Menarik data dari API...');
    }, 1500);

    // Get execute URL from data attribute
    const executeUrl = $('#dataPullForm').data('execute-url');
    
    // Send AJAX request
    console.log('🔵 Sending AJAX request to:', executeUrl);
    console.log('🔵 Form data:', formData);
    
    $.ajax({
        url: executeUrl,
        method: 'POST',
        data: formData,
        timeout: 900000, // 15 minutes timeout
        xhr: function() {
            const xhr = new window.XMLHttpRequest();
            // Progress simulation during request
            let progress = 40;
            const progressInterval = setInterval(() => {
                if (progress < 90) {
                    progress += 5;
                    progressBar.css('width', progress + '%');
                    progressPercentage.text(progress + '%');
                    
                    if (progress >= 50 && progress < 70) {
                        progressDetails.text('Memproses data...');
                    } else if (progress >= 70) {
                        progressDetails.text('Validasi dan penyimpanan data...');
                    }
                }
            }, 2000);
            
            xhr.addEventListener('load', function() {
                clearInterval(progressInterval);
            });
            
            return xhr;
        },
        success: function(response) {
            console.log('✅ AJAX Success!', response);
            
            // Complete progress to 100%
            progressBar.css('width', '100%');
            progressPercentage.text('100%');
            progressStatusText.html('<i class="fas fa-check-circle"></i> Penarikan Data Selesai!');
            progressDetails.text('Data berhasil ditarik dan diproses.');

            if (response.success) {
                // Show realtime stats
                realtimeStats.show();
                
                // Update statistics
                $('#stat-mei').text(parseInt(response.stats.total_mei).toLocaleString('id-ID'));
                $('#stat-juni').text(parseInt(response.stats.total_juni).toLocaleString('id-ID'));
                $('#stat-total').text(parseInt(response.stats.total_all).toLocaleString('id-ID'));

                // Extract numbers from output
                const outputText = response.output + response.process_output;
                const recordsMatch = outputText.match(/Fetched (\d+) records/);
                const idleMatch = outputText.match(/(\d+) idle alarms/);
                
                if (recordsMatch) {
                    $('#recordsFetched').text(parseInt(recordsMatch[1]).toLocaleString('id-ID'));
                }
                if (idleMatch) {
                    $('#idleAlarmsProcessed').text(parseInt(idleMatch[1]).toLocaleString('id-ID'));
                }

                // Show success log with beautiful formatting
                let logHtml = `
                    <div class="alert alert-success mb-3" style="background: #d4edda; border-left: 4px solid #28a745;">
                        <h6><i class="fas fa-check-circle"></i> <strong>Pull Data Berhasil!</strong></h6>
                        <hr>
                        <div class="row">
                            <div class="col-4 text-center">
                                <small class="text-muted d-block">Mei 2026</small>
                                <h5 class="mb-0 text-success">${parseInt(response.stats.total_mei).toLocaleString('id-ID')}</h5>
                            </div>
                            <div class="col-4 text-center border-start border-end">
                                <small class="text-muted d-block">Juni 2026</small>
                                <h5 class="mb-0 text-success">${parseInt(response.stats.total_juni).toLocaleString('id-ID')}</h5>
                            </div>
                            <div class="col-4 text-center">
                                <small class="text-muted d-block">Total</small>
                                <h5 class="mb-0 text-success">${parseInt(response.stats.total_all).toLocaleString('id-ID')}</h5>
                            </div>
                        </div>
                        <hr>
                        <small><i class="fas fa-clock"></i> Selesai: ${new Date().toLocaleString('id-ID')}</small>
                    </div>
                `;

                // Add detailed output (collapsed by default)
                logHtml += `
                    <div class="card mb-2">
                        <div class="card-header p-2 bg-light">
                            <a class="text-decoration-none text-dark d-block" data-bs-toggle="collapse" href="#outputDetail">
                                <small><i class="fas fa-chevron-down"></i> <strong>Detail Output</strong> (klik untuk lihat)</small>
                            </a>
                        </div>
                        <div id="outputDetail" class="collapse">
                            <div class="card-body p-2">
                                <pre style="max-height: 200px; overflow-y: auto; font-size: 10px; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 0;">${response.output}</pre>
                            </div>
                        </div>
                    </div>
                    <div class="card mb-2">
                        <div class="card-header p-2 bg-light">
                            <a class="text-decoration-none text-dark d-block" data-bs-toggle="collapse" href="#processDetail">
                                <small><i class="fas fa-chevron-down"></i> <strong>Processing Output</strong> (klik untuk lihat)</small>
                            </a>
                        </div>
                        <div id="processDetail" class="collapse">
                            <div class="card-body p-2">
                                <pre style="max-height: 200px; overflow-y: auto; font-size: 10px; background: #f8f9fa; padding: 10px; border-radius: 5px; margin: 0;">${response.process_output}</pre>
                            </div>
                        </div>
                    </div>
                `;
                
                logContainer.html(logHtml);

                // Auto-hide progress after 3 seconds
                setTimeout(() => {
                    progressContainer.fadeOut();
                }, 3000);

                // Enable button
                button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Sekarang');

                // Show success notification
                showNotification('✅ Data berhasil ditarik dan diproses!', 'success');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ AJAX Error!', {xhr, status, error});

            // Handle CSRF Token mismatch (419) - refresh token dan coba lagi
            if (xhr.status === 419) {
                console.warn('🔑 CSRF Token expired, refreshing...');
                $.get('/csrf-refresh').done(function(data) {
                    if (data && data.token) {
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        $('[name="_token"]').val(data.token);
                        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                        logContainer.html(`
                            <div class="alert alert-warning">
                                <i class="fas fa-sync"></i> <strong>Token expired, mencoba lagi...</strong>
                                <p class="mb-0 mt-2 small">CSRF token telah diperbarui. Klik "Tarik Data Sekarang" lagi.</p>
                            </div>
                        `);
                        button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Sekarang');
                        showNotification('🔄 Token diperbarui. Silakan klik Tarik Data lagi.', 'warning');
                    }
                });
                return;
            }
            
            // Set progress to error state
            progressBar.css('width', '100%').removeClass('bg-success').addClass('bg-danger');
            progressPercentage.text('ERROR');
            progressStatusText.html('<i class="fas fa-exclamation-circle"></i> Terjadi Kesalahan');
            
            let errorMsg = 'Unknown error';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (status === 'timeout') {
                errorMsg = 'Request timeout. Proses mungkin masih berjalan di background. Cek database untuk memastikan.';
                progressDetails.text('Timeout - cek database untuk konfirmasi');
            } else {
                errorMsg = error;
            }

            progressDetails.text(errorMsg);

            logContainer.html(`
                <div class="alert alert-danger" style="background: #f8d7da; border-left: 4px solid #dc3545;">
                    <h6><i class="fas fa-exclamation-circle"></i> <strong>Error!</strong></h6>
                    <p class="mb-2">${errorMsg}</p>
                    <hr>
                    <small><i class="fas fa-clock"></i> ${new Date().toLocaleString('id-ID')}</small>
                </div>
            `);

            // Enable button
            button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Sekarang');
            
            showNotification('❌ Terjadi kesalahan: ' + errorMsg, 'error');
        }
    });
}

function showNotification(message, type) {
    // Simple notification
    const bgColor = type === 'success' ? '#28a745' : '#dc3545';
    const notification = $(`
        <div style="position: fixed; top: 20px; right: 20px; background: ${bgColor}; color: white; padding: 15px 25px; border-radius: 8px; z-index: 9999; box-shadow: 0 4px 12px rgba(0,0,0,0.3);">
            ${message}
        </div>
    `);
    $('body').append(notification);
    setTimeout(() => notification.fadeOut(() => notification.remove()), 4000);
}

function quickPull(type) {
    console.log('🔵 quickPull() called with type:', type);
    
    const today = new Date();
    let fromDate, toDate;

    switch(type) {
        case 'today':
            fromDate = toDate = today.toISOString().split('T')[0];
            break;
        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(yesterday.getDate() - 1);
            fromDate = toDate = yesterday.toISOString().split('T')[0];
            break;
        case 'last_7_days':
            const weekAgo = new Date(today);
            weekAgo.setDate(weekAgo.getDate() - 7);
            fromDate = weekAgo.toISOString().split('T')[0];
            toDate = today.toISOString().split('T')[0];
            break;
        case 'this_month':
            fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
            toDate = today.toISOString().split('T')[0];
            break;
    }

    $('#from_date').val(fromDate);
    $('#to_date').val(toDate);
    
    console.log('🔵 Quick pull dates set:', {fromDate, toDate});
    
    // Auto submit
    executePull();
}

function refreshStatistics() {
    const statsUrl = $('#dataPullForm').data('stats-url');
    $.ajax({
        url: statsUrl,
        method: 'GET',
        success: function(response) {
            $('#stat-mei').text(parseInt(response.total_mei).toLocaleString());
            $('#stat-juni').text(parseInt(response.total_juni).toLocaleString());
            $('#stat-total').text(parseInt(response.total_all).toLocaleString());
            if (response.last_pull) {
                $('#stat-last-pull').text(new Date(response.last_pull).toLocaleString());
            }
        }
    });
}
