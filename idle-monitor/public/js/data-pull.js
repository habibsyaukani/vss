// Data Pull JavaScript - BATCH VERSION
// Handles batch auto-split & progress tracking

// CSRF Token setup
$(document).ready(function() {
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
});

console.log('✅ Data Pull Batch JavaScript loaded!');

let progressInterval = null;
let currentSessionId = null;
let startTime = null;

$(document).ready(function() {
    console.log('✅ Document ready!');
    
    // Set default date to today
    const today = new Date().toISOString().split('T')[0];
    $('#date').val(today);
    
    // Form submission
    $('#dataPullForm').on('submit', function(e) {
        e.preventDefault();
        console.log('🚀 Form submitted, starting batch pull...');
        executeBatchPull();
    });
});

/**
 * Execute batch pull - dispatch orchestrator
 */
function executeBatchPull() {
    const form = $('#dataPullForm');
    const button = $('#pullButton');
    const date = $('#date').val();
    
    if (!date) {
        alert('Pilih tanggal terlebih dahulu!');
        return;
    }
    
    console.log('🔵 Starting batch pull for date:', date);
    
    // Disable button
    button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memulai...');
    
    // Get execute URL
    const executeUrl = form.data('execute-url');
    
    // Send request to start batch pull
    $.ajax({
        url: executeUrl,
        method: 'POST',
        data: { date: date },
        success: function(response) {
            console.log('✅ Batch pull initiated!', response);
            
            if (response.success && response.session_id) {
                currentSessionId = response.session_id;
                startTime = new Date();
                
                // Show progress container
                $('#initialState').hide();
                $('#progressContainer').show();
                
                // Set session info
                $('#sessionDate').text(date);
                $('#sessionIdDisplay').text(response.session_id.substring(0, 16) + '...');
                
                // Start polling progress
                startProgressPolling();
                
                // Re-enable button with "Pull Lagi" text
                button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Lagi');
            } else {
                alert('Error: ' + (response.message || 'Unknown error'));
                button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Sekarang');
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Failed to start batch pull', {xhr, status, error});
            
            let errorMsg = 'Unknown error';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMsg = xhr.responseJSON.message;
            } else if (xhr.status) {
                errorMsg = `HTTP ${xhr.status}: ${xhr.statusText}`;
            }
            
            alert('Error: ' + errorMsg);
            button.prop('disabled', false).html('<i class="fas fa-download"></i> Tarik Data Sekarang');
        }
    });
}

/**
 * Start polling progress every 3 seconds
 */
function startProgressPolling() {
    if (progressInterval) {
        clearInterval(progressInterval);
    }
    
    console.log('🔵 Starting progress polling for session:', currentSessionId);
    
    // Poll immediately
    pollProgress();
    
    // Then poll every 3 seconds
    progressInterval = setInterval(pollProgress, 3000);
}

/**
 * Poll progress for current session
 */
function pollProgress() {
    if (!currentSessionId) {
        console.warn('⚠️ No current session ID, stopping poll');
        stopProgressPolling();
        return;
    }
    
    const progressUrl = $('#dataPullForm').data('progress-url').replace(':sessionId', currentSessionId);
    
    $.ajax({
        url: progressUrl,
        method: 'GET',
        success: function(data) {
            console.log('📊 Progress data:', data);
            updateProgressDisplay(data);
            
            // Stop polling if completed
            if (data.is_completed) {
                console.log('✅ All batches completed!');
                setTimeout(stopProgressPolling, 5000); // Stop after 5 seconds
            }
        },
        error: function(xhr, status, error) {
            console.error('❌ Failed to poll progress', {xhr, status, error});
            // Don't stop polling on error, might be temporary
        }
    });
}

/**
 * Stop polling progress
 */
function stopProgressPolling() {
    if (progressInterval) {
        clearInterval(progressInterval);
        progressInterval = null;
        console.log('🛑 Progress polling stopped');
    }
}

/**
 * Update progress display with fetched data
 */
function updateProgressDisplay(data) {
    // Update overall progress bar
    const percentage = data.progress_percentage || 0;
    $('#overallProgressBar').css('width', percentage + '%');
    $('#overallPercentage').text(percentage + '%');
    $('#overallProgress').text(`${data.completed} / ${data.total_batches} batch`);
    
    // Update stats
    $('#totalRecords').text((data.total_records || 0).toLocaleString('id-ID'));
    
    // Calculate elapsed time
    if (startTime) {
        const elapsed = Math.floor((new Date() - startTime) / 1000);
        $('#elapsedTime').text(formatDuration(elapsed));
    }
    
    // Update ETA
    if (data.eta_formatted) {
        $('#etaTime').text(data.eta_formatted);
    } else {
        $('#etaTime').text('-');
    }
    
    // Update batch list
    renderBatchList(data.batches || []);
    
    // Change progress bar color based on status
    const progressBar = $('#overallProgressBar');
    if (data.failed > 0) {
        progressBar.removeClass('bg-success bg-warning').addClass('bg-danger');
    } else if (data.processing > 0) {
        progressBar.removeClass('bg-danger bg-success').addClass('bg-warning');
    } else if (data.is_completed) {
        progressBar.removeClass('bg-warning bg-danger').addClass('bg-success');
        progressBar.removeClass('progress-bar-animated');
    }
}

/**
 * Render batch list
 */
function renderBatchList(batches) {
    const container = $('#batchItems');
    container.empty();
    
    if (batches.length === 0) {
        container.html('<p class="text-muted text-center">Memuat batch...</p>');
        return;
    }
    
    batches.forEach(function(batch) {
        const batchHtml = createBatchItem(batch);
        container.append(batchHtml);
    });
}

/**
 * Create HTML for single batch item
 */
function createBatchItem(batch) {
    let statusBadge, statusIcon, statusClass, statusText;
    
    switch(batch.status) {
        case 'completed':
            statusBadge = '<span class="badge bg-success">✔</span>';
            statusIcon = '✅';
            statusClass = 'border-success';
            statusText = `<span class="text-success">${batch.total_records} records</span>`;
            if (batch.duration) {
                statusText += ` <small class="text-muted">(${batch.duration})</small>`;
            }
            break;
        case 'processing':
            statusBadge = '<span class="badge bg-warning">⏳</span>';
            statusIcon = '⏳';
            statusClass = 'border-warning';
            statusText = '<span class="text-warning"><span class="spinner-border spinner-border-sm"></span> Sedang proses...</span>';
            break;
        case 'failed':
            statusBadge = '<span class="badge bg-danger">❌</span>';
            statusIcon = '❌';
            statusClass = 'border-danger';
            statusText = '<span class="text-danger">Failed</span>';
            if (batch.error_message) {
                statusText += `<br><small class="text-muted">${batch.error_message}</small>`;
            }
            break;
        case 'pending':
        default:
            statusBadge = '<span class="badge bg-secondary">⏸</span>';
            statusIcon = '⬜';
            statusClass = 'border-secondary';
            statusText = '<span class="text-muted">Pending</span>';
            break;
    }
    
    return `
        <div class="card mb-2 ${statusClass}" style="border-left-width: 4px;">
            <div class="card-body p-2">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        ${statusBadge}
                        <strong>Batch ${batch.batch_number}:</strong> 
                        <span class="text-muted">${batch.time_range}</span>
                    </div>
                    <div class="text-end">
                        ${statusText}
                    </div>
                </div>
            </div>
        </div>
    `;
}

/**
 * Format duration in human readable format
 */
function formatDuration(seconds) {
    if (seconds < 60) {
        return seconds + 's';
    } else if (seconds < 3600) {
        const mins = Math.floor(seconds / 60);
        const secs = seconds % 60;
        return mins + 'm ' + secs + 's';
    } else {
        const hours = Math.floor(seconds / 3600);
        const mins = Math.floor((seconds % 3600) / 60);
        return hours + 'h ' + mins + 'm';
    }
}

// Cleanup on page unload
$(window).on('beforeunload', function() {
    stopProgressPolling();
});
