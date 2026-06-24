// GPS Track Pull JavaScript
// Handles form submission and real-time progress for GPS data pulling

(function() {
    'use strict';

    const form = document.getElementById('gpsTrackPullForm');
    const pullButton = document.getElementById('pullButton');
    const progressContainer = document.getElementById('progressContainer');
    const progressBar = document.getElementById('progressBar');
    const progressPercentage = document.getElementById('progressPercentage');
    const progressStatusText = document.getElementById('progressStatusText');
    const progressDetails = document.getElementById('progressDetails');
    const realtimeStats = document.getElementById('realtimeStats');
    const logContainer = document.getElementById('logContainer');

    // Stats elements
    const devicesProcessed = document.getElementById('devicesProcessed');
    const devicesWithData = document.getElementById('devicesWithData');
    const recordsSaved = document.getElementById('recordsSaved');

    // Stats refresh elements
    const statJuni = document.getElementById('stat-juni');
    const statDevices = document.getElementById('stat-devices');
    const statTotal = document.getElementById('stat-total');
    const statLastPull = document.getElementById('stat-last-pull');

    if (!form) {
        console.error('GPS Track Pull form not found');
        return;
    }

    // Form submission handler
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const executeUrl = form.dataset.executeUrl;
        // Construct the devices url from executeUrl (by replacing /execute with /devices)
        const devicesUrl = executeUrl.replace('/execute', '/devices');
        const formData = new FormData(form);

        // Disable button
        pullButton.disabled = true;
        pullButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';

        // Show progress
        progressContainer.style.display = 'block';
        realtimeStats.style.display = 'flex';
        logContainer.innerHTML = '';

        const filterDate = formData.get('date');
        const deviceFilter = formData.get('device_filter') || 'all';
        const limitStr = formData.get('limit') || '0';
        const limit = parseInt(limitStr, 10);

        // Log start
        addLog('info', 'GPS Track Pull dimulai...');
        addLog('info', 'Tanggal: ' + filterDate);
        addLog('info', 'Device filter: ' + deviceFilter);
        addLog('info', 'Limit: ' + (limit === 0 ? 'all (397 devices)' : limit));

        updateProgress(5, 'Mengambil daftar device...', 'Fetching devices...');

        try {
            // Step 1: Fetch Devices
            let targetDevices = [];
            
            if (deviceFilter !== 'all') {
                // If specific devices are provided, parse them
                const specificIds = deviceFilter.split(',').map(id => id.trim()).filter(id => id);
                targetDevices = specificIds.map(id => ({ device_id: id, device_name: `Device ${id}` }));
                
                if (limit > 0 && targetDevices.length > limit) {
                    targetDevices = targetDevices.slice(0, limit);
                }
            } else {
                // Fetch active devices from server
                const devicesResponse = await fetch(devicesUrl);
                const devicesResult = await devicesResponse.json();
                
                if (!devicesResult.success || !devicesResult.devices) {
                    throw new Error('Gagal mengambil daftar device aktif');
                }
                
                targetDevices = devicesResult.devices;
                
                if (limit > 0 && targetDevices.length > limit) {
                    targetDevices = targetDevices.slice(0, limit);
                }
            }

            if (targetDevices.length === 0) {
                throw new Error('Tidak ada device untuk diproses');
            }

            addLog('info', `✅ Total target device: ${targetDevices.length}`);
            
            // Stats accumulators
            let totalProcessed = 0;
            let totalWithData = 0;
            let totalRecords = 0;

            // Update stats UI immediately
            if (devicesProcessed) devicesProcessed.textContent = 0;
            if (devicesWithData) devicesWithData.textContent = 0;
            if (recordsSaved) recordsSaved.textContent = 0;

            // Step 2: Loop devices in parallel batches (Concurrency: 20)
            const concurrencyLimit = 20;
            let currentIndex = 0;

            const processDevice = async (device, index) => {
                // Update progress text
                const currentPercent = 5 + Math.floor((totalProcessed / targetDevices.length) * 95);
                updateProgress(currentPercent, `Memproses paralel... (${totalProcessed}/${targetDevices.length})`, `Berjalan secara paralel (${concurrencyLimit} alat sekaligus)`);
                
                // Prepare request for this specific device
                const deviceFormData = new FormData();
                deviceFormData.append('_token', formData.get('_token'));
                deviceFormData.append('date', filterDate);
                deviceFormData.append('device_filter', device.device_id);
                deviceFormData.append('limit', '0'); // Limit applies to overall, not per device
                
                // addLog('detail', `Mengambil data ${device.device_name}...`); // Dihilangkan agar log tidak terlalu penuh
                
                try {
                    const response = await fetch(executeUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                        },
                        body: deviceFormData,
                    });
                    
                    const result = await response.json();
                    
                    if (result.success) {
                        const recSaved = parseInt(result.records_saved) || 0;
                        
                        totalProcessed++;
                        if (recSaved > 0) {
                            totalWithData++;
                            totalRecords += recSaved;
                            addLog('success', `✅ [${device.device_name}] Data ditarik: ${recSaved} records`);
                        }
                        
                        // Update stats UI incrementally
                        if (devicesProcessed) devicesProcessed.textContent = totalProcessed;
                        if (devicesWithData) devicesWithData.textContent = totalWithData;
                        if (recordsSaved) recordsSaved.textContent = formatNumber(totalRecords);
                        
                    } else {
                        totalProcessed++;
                        addLog('error', `❌ [${device.device_name}] Error: ${result.message}`);
                    }
                } catch (devError) {
                    totalProcessed++;
                    addLog('error', `❌ [${device.device_name}] Network Error: ${devError.message}`);
                }
            };

            const worker = async () => {
                while (currentIndex < targetDevices.length) {
                    const idx = currentIndex++;
                    const device = targetDevices[idx];
                    await processDevice(device, idx);
                }
            };

            const workers = [];
            for (let i = 0; i < Math.min(concurrencyLimit, targetDevices.length); i++) {
                workers.push(worker());
            }

            await Promise.all(workers);

            // Step 3: Finish
            updateProgress(100, 'Pull selesai!', 'Completed successfully');

            // Log success summary
            addLog('success', '✅ SEMUA GPS Track Pull selesai!');
            addLog('success', `📊 Total devices diproses: ${totalProcessed}`);
            addLog('success', `✅ Total devices ada data: ${totalWithData}`);
            addLog('success', `💾 Total records tersimpan: ${formatNumber(totalRecords)}`);

            // Update statistics cards
            refreshStatistics();

            // Success notification
            setTimeout(() => {
                alert('✅ GPS Track Pull completed!\n\n' +
                      `Devices: ${totalProcessed}\n` +
                      `With Data: ${totalWithData}\n` +
                      `Records: ${formatNumber(totalRecords)}`);
            }, 500);

        } catch (error) {
            updateProgress(0, 'Error!', 'Error occurred');
            addLog('error', '❌ Error: ' + error.message);
            alert('❌ GPS Track Pull failed!\n\n' + error.message);
        } finally {
            // Re-enable button
            pullButton.disabled = false;
            pullButton.innerHTML = '<i class="fas fa-download"></i> Tarik Data GPS Sekarang';
        }
    });

    // Helper: Update progress bar
    function updateProgress(percent, statusText, detailsText) {
        progressBar.style.width = percent + '%';
        progressBar.setAttribute('aria-valuenow', percent);
        progressPercentage.textContent = percent + '%';
        
        if (statusText) progressStatusText.textContent = statusText;
        if (detailsText) progressDetails.textContent = detailsText;

        // Change color based on progress
        progressBar.classList.remove('bg-success', 'bg-warning', 'bg-danger');
        if (percent === 100) {
            progressBar.classList.add('bg-success');
        } else if (percent === 0) {
            progressBar.classList.add('bg-danger');
        } else {
            progressBar.classList.add('bg-warning');
        }
    }

    // Helper: Add log entry
    function addLog(type, message) {
        const logEntry = document.createElement('div');
        
        let icon, borderColor, bgColor;
        switch(type) {
            case 'success':
                icon = '✅';
                borderColor = 'border-success';
                bgColor = 'bg-success bg-opacity-10';
                break;
            case 'error':
                icon = '❌';
                borderColor = 'border-danger';
                bgColor = 'bg-danger bg-opacity-10';
                break;
            case 'info':
                icon = 'ℹ️';
                borderColor = 'border-info';
                bgColor = 'bg-info bg-opacity-10';
                break;
            case 'detail':
                icon = '▸';
                borderColor = 'border-secondary';
                bgColor = 'bg-light';
                break;
            default:
                icon = '•';
                borderColor = 'border-secondary';
                bgColor = 'bg-light';
        }

        logEntry.className = `log-entry mb-2 p-2 border-start border-3 ${borderColor} ${bgColor}`;
        logEntry.innerHTML = `<small><strong>${icon}</strong> ${escapeHtml(message)}</small>`;
        
        logContainer.appendChild(logEntry);
        logContainer.scrollTop = logContainer.scrollHeight;
    }

    // Helper: Format number with thousands separator
    function formatNumber(num) {
        return new Intl.NumberFormat('id-ID').format(num);
    }

    // Helper: Escape HTML
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper: Refresh statistics from server
    async function refreshStatistics() {
        const statsUrl = form.dataset.statsUrl;
        
        try {
            const response = await fetch(statsUrl);
            const stats = await response.json();

            if (statJuni) statJuni.textContent = formatNumber(stats.total_juni || 0);
            if (statDevices) statDevices.textContent = formatNumber(stats.total_devices || 0);
            if (statTotal) statTotal.textContent = formatNumber(stats.total_all || 0);
            if (statLastPull && stats.last_pull) statLastPull.textContent = stats.last_pull;

        } catch (error) {
            console.error('Failed to refresh statistics:', error);
        }
    }

    // Auto-refresh statistics every 30 seconds
    setInterval(refreshStatistics, 30000);

})();

// Quick pull functions (global scope)
function quickPullGps(action) {
    const dateInput = document.getElementById('date');
    const limitInput = document.getElementById('limit');
    const deviceFilterInput = document.getElementById('device_filter');
    
    const today = new Date();
    let targetDate;

    switch(action) {
        case 'today':
            targetDate = today;
            limitInput.value = 0;
            deviceFilterInput.value = 'all';
            break;
        case 'yesterday':
            targetDate = new Date(today);
            targetDate.setDate(targetDate.getDate() - 1);
            limitInput.value = 0;
            deviceFilterInput.value = 'all';
            break;
        case 'june_9':
            targetDate = new Date('2026-06-09');
            limitInput.value = 0;
            deviceFilterInput.value = 'all';
            break;
        case 'june_11':
            targetDate = new Date('2026-06-11');
            limitInput.value = 0;
            deviceFilterInput.value = 'all';
            break;
        case 'test_10':
            targetDate = new Date(today);
            targetDate.setDate(targetDate.getDate() - 1);
            limitInput.value = 10;
            deviceFilterInput.value = 'all';
            break;
        default:
            return;
    }

    // Format date as YYYY-MM-DD
    const year = targetDate.getFullYear();
    const month = String(targetDate.getMonth() + 1).padStart(2, '0');
    const day = String(targetDate.getDate()).padStart(2, '0');
    dateInput.value = `${year}-${month}-${day}`;

    // Auto-submit
    if (confirm(`Pull GPS data untuk ${dateInput.value}?\n\nDevices: ${limitInput.value === '0' ? 'All (397)' : limitInput.value}\nEstimated time: ${limitInput.value === '0' ? '2-3 minutes' : '~30 seconds'}`)) {
        document.getElementById('gpsTrackPullForm').dispatchEvent(new Event('submit'));
    }
}
