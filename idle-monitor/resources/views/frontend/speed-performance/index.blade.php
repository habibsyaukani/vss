@extends('frontend.layouts.app')

@section('title', 'Speed Performance')

@section('styles')
<style>
    /* ===== MAIN CONTENT ===== */
    .main-content {
        overflow-x: hidden !important;
        overflow-y: auto;
    }

    /* ===== SIDEBAR STYLES ===== */
    .filter-section {
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .filter-section:first-child {
        padding-top: 35px !important;
    }
    .filter-label {
        font-size: 11px;
        font-weight: 600;
        color: #8c98a4;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
        display: block;
    }
    .form-select-sm {
        font-size: 13px;
        border-color: rgba(255,255,255,0.1);
        border-radius: 6px;
        background-color: rgba(255,255,255,0.05);
        color: white;
    }
    .form-select-sm option {
        background-color: #0b1a30;
        color: white;
    }
    .btn-select-all {
        color: #1963f2;
        background: rgba(25, 99, 242, 0.1);
        border: none;
        font-size: 13px;
        font-weight: 500;
        border-radius: 6px;
        padding: 6px 12px;
    }
    .btn-clear {
        color: #dc3545;
        background: transparent;
        border: none;
        font-size: 13px;
        font-weight: 500;
    }
    .btn-clear i { margin-right: 4px; }

    /* Tree View */
    .tree-view {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .tree-item { margin-bottom: 2px; }
    .tree-parent {
        display: flex;
        align-items: center;
        padding: 8px 10px;
        cursor: pointer;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #cbd5e1;
        transition: background 0.2s;
    }
    .tree-parent:hover { background-color: rgba(255,255,255,0.05); }
    .tree-parent i.toggle-icon {
        width: 15px;
        color: #94a3b8;
        font-size: 12px;
        transition: transform 0.2s;
    }
    .tree-parent.open i.toggle-icon { transform: rotate(90deg); }
    .tree-checkbox {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .group-icon {
        color: #3b82f6;
        margin-right: 8px;
        font-size: 14px;
    }
    .group-count {
        color: #1963f2;
        margin-left: 5px;
    }
    .tree-children {
        display: none;
        list-style: none;
        padding-left: 25px;
        margin-top: 5px;
    }
    .tree-parent.open ~ .tree-children { display: block !important; }
    .tree-child {
        display: flex;
        align-items: center;
        padding: 6px 10px;
        font-size: 13px;
        color: #94a3b8;
        border-radius: 6px;
    }
    .tree-child:hover { background-color: rgba(255,255,255,0.05); color: white; }
    .tree-child input[type="checkbox"] { margin-right: 10px; }

    /* System Active Box */
    .system-active-box {
        margin: 20px;
        padding: 15px;
        background-color: rgba(34, 197, 94, 0.1);
        border: 1px solid rgba(34, 197, 94, 0.2);
        border-radius: 8px;
    }
    .system-active-title {
        color: #4ade80;
        font-weight: 600;
        font-size: 13px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .system-active-title::before {
        content: '';
        display: inline-block;
        width: 8px;
        height: 8px;
        background-color: #4ade80;
        border-radius: 50%;
    }
    .system-active-desc {
        color: #94a3b8;
        font-size: 12px;
        margin-top: 5px;
        margin-bottom: 0;
    }

    /* ===== PAGE HEADER ===== */
    .page-title {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 5px;
    }
    .page-title h1 {
        font-size: 24px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
    }
    .page-title i { color: #dc2626; font-size: 22px; }
    .page-subtitle {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 25px;
    }

    /* Summary Cards */
    .summary-card {
        background: white;
        border: 1px solid #eaedf2;
        border-radius: 8px;
        padding: 20px;
        height: 100%;
    }
    .summary-title {
        font-size: 11px;
        font-weight: 700;
        color: #8c98a4;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    .summary-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        margin-bottom: 4px;
    }
    .summary-value.text-primary { color: #1963f2 !important; }
    .summary-value.text-danger { color: #dc2626 !important; }
    .summary-desc {
        font-size: 12px;
        color: #94a3b8;
    }

    /* ===== TABLE CONTAINER ===== */
    .table-container {
        background: white;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #eaedf2;
        overflow-x: auto;
        margin-top: 20px;
    }

    #performanceTable { font-size: 12px; width: 100% !important; }
    #performanceTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 700;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.5px;
        padding: 12px 10px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    #performanceTable tbody td {
        padding: 12px 10px;
        vertical-align: middle;
        font-size: 13px;
        color: #334155;
        white-space: nowrap;
        font-weight: 500;
    }
    #performanceTable tbody tr:hover { background-color: #f8fafc; }

    /* Date Filter Row */
    .top-filter-row {
        display: flex;
        align-items: center;
        gap: 15px;
        background: white;
        padding: 14px 20px;
        border-radius: 8px;
        border: 1px solid #eaedf2;
        margin-bottom: 20px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.02);
        flex-wrap: wrap;
    }
    .filter-group-date {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .filter-group-date label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        margin: 0;
        white-space: nowrap;
    }
    .date-input, .shift-select {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        color: #334155;
        font-weight: 500;
    }
    .date-input:focus, .shift-select:focus {
        outline: none;
        border-color: #1963f2;
        box-shadow: 0 0 0 2px rgba(25,99,242,0.15);
    }
    
    .text-red { color: #dc2626; font-weight: 700; }
    .text-orange { color: #ea580c; font-weight: 700; }

    /* Skeleton Loading Overlay */
    .skeleton-overlay {
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        background: rgba(255, 255, 255, 0.85);
        z-index: 10;
        display: none;
        flex-direction: column;
        padding: 20px;
        backdrop-filter: blur(2px);
    }
    .skeleton-row {
        height: 25px;
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loadingSkeleton 1.5s infinite;
        margin-bottom: 12px;
        border-radius: 4px;
        opacity: 0.7;
    }
    @keyframes loadingSkeleton {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .dataTables_processing {
        display: none !important;
    }
    
    #exportLoading {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(255,255,255,0.8);
        z-index: 9999;
        display: none;
        flex-direction: column;
        align-items: center;
        justify-content: center;
    }
    #exportLoading .spinner-border {
        width: 3rem; height: 3rem; color: #1963f2;
    }
</style>
@endsection

@section('sidebar')
<!-- Device Filter Search -->
<!-- Location Filter -->
<div class="filter-section px-4 py-3">
    <div class="filter-label">LOCATION</div>
    <select class="form-select form-select-sm" id="locationFilter">
        <option value="">Semua</option>
        @foreach($locations as $loc)
            <option value="{{ $loc }}">{{ $loc }}</option>
        @endforeach
    </select>
</div>

<!-- Series Filter -->
<div class="filter-section px-4 py-3">
    <div class="filter-label">SERIES</div>
    <select class="form-select form-select-sm" id="seriesFilter">
        <option value="">Semua</option>
        @foreach($seriesList as $s)
            <option value="{{ $s }}">{{ $s }}</option>
        @endforeach
    </select>
</div>

<!-- Actions -->
<div class="px-4 py-3 d-flex justify-content-between align-items-center border-bottom" style="border-color: rgba(255,255,255,0.05) !important;">
    <button class="btn-select-all" id="selectAllBtn"><i class="far fa-check-square me-1"></i> Select All</button>
    <button class="btn-clear" id="clearBtn"><i class="fas fa-times"></i> Clear</button>
</div>

<!-- Tree View -->
<div class="filter-section px-4 py-3 border-0">
    <ul class="tree-view">
        <li class="tree-item">
            <div class="tree-parent open">
                <i class="fas fa-chevron-right toggle-icon me-2"></i>
                <input type="checkbox" class="tree-checkbox group-checkbox" data-group="all" checked>
                <i class="fas fa-car-side group-icon" style="color: #0d9488;"></i>
                <span style="font-weight: 700; color: #f8fafc;">ALL GPE</span>
                <span class="group-count" id="totalDeviceCount">({{ $totalDevices }}|<span style="color: #4ade80; font-weight: 700;">{{ $totalActive }}</span>)</span>
            </div>
            <ul class="tree-children">
                @foreach($deviceGroups as $groupName => $groupData)
                    <li class="tree-item">
                        <div class="tree-parent">
                            <i class="fas fa-chevron-right toggle-icon me-2"></i>
                            <input type="checkbox" class="tree-checkbox group-checkbox" data-group="{{ Str::slug($groupName) }}" checked>
                            @php
                                $icon = 'fa-car';
                                if(str_contains($groupName, 'BUS')) $icon = 'fa-bus';
                                elseif(str_contains($groupName, 'DT')) $icon = 'fa-truck-moving';
                                elseif(str_contains($groupName, 'FT') || str_contains($groupName, 'WT')) $icon = 'fa-truck';
                                elseif(str_contains($groupName, 'HD')) $icon = 'fa-truck-front';
                            @endphp
                            <i class="fas {{ $icon }} group-icon" style="color: #0d9488;"></i>
                            <span style="font-weight: 700; color: #cbd5e1;">{{ $groupName }}</span>
                            <span class="group-count">({{ $groupData['total'] }}|<span style="color: #4ade80; font-weight: 700;">{{ $groupData['active'] }}</span>)</span>
                        </div>
                        <ul class="tree-children">
                            @foreach($groupData['devices'] as $device)
                                <li class="tree-child" data-device-name="{{ strtolower($device->device_name) }}" data-location="{{ $device->lokasi ?? '' }}" data-series="{{ $device->series ?? '' }}">
                                    <input type="checkbox" class="tree-checkbox device-checkbox" value="{{ $device->device_id }}" checked data-group="{{ Str::slug($groupName) }}">
                                    @php
                                        $dIcon = 'fa-car';
                                        if(str_contains($device->device_name, 'BUS') || str_contains($device->device_name, '-B-')) $dIcon = 'fa-bus';
                                        elseif(str_contains($device->device_name, '-DT-')) $dIcon = 'fa-truck-moving';
                                        elseif(str_contains($device->device_name, '-FT-') || str_contains($device->device_name, '-WT-')) $dIcon = 'fa-truck';
                                        elseif(str_contains($device->device_name, '-HD-')) $dIcon = 'fa-truck-front';
                                        $iconColor = $device->status === 'active' ? '#22c55e' : '#cbd5e1';
                                    @endphp
                                    <i class="fas {{ $dIcon }} group-icon me-2" style="color: {{ $iconColor }}; font-size: 11px;"></i>
                                    <span>{{ $device->device_name }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endforeach
            </ul>
        </li>
    </ul>
</div>

<!-- System Active -->
<div class="system-active-box mt-auto">
    <div class="system-active-title">Siap</div>
</div>
@endsection

@section('content')
<div class="page-title">
    <i class="fas fa-truck-fast"></i>
    <h1>Fleet Speed Performance</h1>
</div>
<p class="page-subtitle">Rata-rata & kecepatan maksimum per unit per hari</p>

<!-- Summary Cards -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-title">TOTAL RECORDS</div>
            <div class="summary-value" id="cardTotalRecords">0</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-title">AVG SPEED</div>
            <div class="summary-value text-primary" id="cardAvgSpeed">0.0 <span style="font-size: 16px;">km/h</span></div>
            <div class="summary-desc">Rata-rata semua unit</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-title">MAX SPEED</div>
            <div class="summary-value text-danger" id="cardMaxSpeed">0.0 <span style="font-size: 16px;">km/h</span></div>
            <div class="summary-desc">Kecepatan tertinggi</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="summary-card">
            <div class="summary-title">DEVICES</div>
            <div class="summary-value" id="cardDevices">0</div>
            <div class="summary-desc">Kendaraan aktif</div>
        </div>
    </div>
</div>

<!-- Date Filter Row -->
<div class="top-filter-row d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-4">
        <div class="filter-group-date">
            <label>TANGGAL</label>
            <input type="date" id="filterDate" class="date-input" value="{{ date('Y-m-d') }}">
        </div>
        <div class="filter-group-date">
            <label>WAKTU</label>
            <select id="filterShift" class="shift-select" style="min-width: 180px;">
                <option value="shift1">Shift 1 (07:00 - 19:00)</option>
                <option value="shift2">Shift 2 (19:00 - 07:00)</option>
                <option value="op_malam">Operasional Malam (18:00 - 23:59)</option>
                <option value="op_dini_hari">Operasional Dini Hari (00:00 - 07:00)</option>
                <option value="op_pagi">Operasional Pagi (07:00 - 12:00)</option>
                <option value="op_siang">Operasional Siang (12:00 - 18:00)</option>
            </select>
        </div>
        <div class="filter-group-date">
            <label>PER JAM</label>
            <select id="filterHour" class="shift-select" style="min-width: 120px;">
                <option value="">Semua Jam</option>
            </select>
        </div>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="records-badge me-2" id="unitCountBadge"><i class="fas fa-database me-1"></i><span>0 unit</span></span>
        <button type="button" class="btn btn-outline-success btn-sm btn-export-selected">
            <i class="fas fa-file-excel me-1"></i> Export Selected
        </button>
        <button type="button" class="btn btn-success btn-sm btn-export-all">
            <i class="fas fa-file-excel me-1"></i> Export All
        </button>
    </div>
</div>

<!-- Loading Overlay -->
<div id="exportLoading">
    <div class="spinner-border" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    <div class="mt-3 fw-bold" style="color: #1e293b;">Generating File...</div>
    <div class="text-muted small mt-1">Please wait, this may take a moment.</div>
</div>

<!-- Data Table -->
<div class="table-container" style="position: relative;">
    <!-- SKELETON LOADER OVERLAY -->
    <div class="skeleton-overlay" id="tableSkeleton">
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
        <div class="skeleton-row"></div>
    </div>

    <table id="performanceTable" class="table table-borderless">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllRows"></th>
                <th style="width: 50px;">#</th>
                <th>DEVICE NAME</th>
                <th>WAKTU</th>
                <th>AVG SPEED</th>
                <th>MAX SPEED</th>
            </tr>
        </thead>
        <tbody>
        </tbody>
    </table>
</div>

@endsection

@section('scripts')
<script>
$(document).ready(function() {
    let table;
    let debounceTimer;

    function getSelectedDevices() {
        let selected = [];
        $('.device-checkbox:checked').each(function() {
            selected.push($(this).val());
        });
        return selected;
    }

    function initTable() {
        $('#tableSkeleton').css('display', 'flex');
        
        if(table) { table.destroy(); }
        
        table = $('#performanceTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('frontend.speed-performance.data') }}",
                type: 'POST',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                data: function(d) {
                    d.device_ids = getSelectedDevices();
                    d.location = $('#locationFilter').val();
                    d.series = $('#seriesFilter').val();
                    d.date = $('#filterDate').val();
                    d.shift = $('#filterShift').val();
                    d.hour = $('#filterHour').val();
                },
                dataSrc: function(json) {
                    // Update summary cards
                    if(json.summaryAvg !== undefined) {
                        $('#cardAvgSpeed').html(json.summaryAvg + ' <span style="font-size: 16px;">km/h</span>');
                    }
                    if(json.summaryMax !== undefined) {
                        $('#cardMaxSpeed').html(json.summaryMax + ' <span style="font-size: 16px;">km/h</span>');
                    }
                    if(json.totalRecords !== undefined) {
                        $('#cardTotalRecords').text(json.totalRecords.toLocaleString());
                        $('#cardDevices').text(json.data.length.toLocaleString());
                        $('#unitCountBadge').text(json.data.length + ' unit');
                    }
                    $('#tableSkeleton').hide();
                    return json.data;
                },
                error: function() {
                    $('#tableSkeleton').hide();
                }
            },
            columns: [
                {
                    data: 'checkbox',
                    name: 'checkbox',
                    orderable: false, 
                    searchable: false,
                    render: function(data) {
                        return data;
                    }
                },
                {
                    data: null,
                    searchable: false,
                    orderable: false,
                    render: function(data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                {
                    data: 'device_name',
                    name: 'device_name',
                    render: function(data, type, row) {
                        return `<div class="fw-bold" style="color:#1e293b; font-size:13px;">${data || '-'}</div>
                                <div style="color:#94a3b8; font-size:11px;">${row.device_id || ''}</div>`;
                    }
                },
                { 
                    data: 'time_label', 
                    name: 'time_label', 
                    orderable: false,
                    render: function(data) {
                        if (!data) return '-';
                        let parts = data.split('\n');
                        if (parts.length >= 3) {
                            return `<div style="color:#1e293b; font-size:13px; font-weight:600;">${parts[0]}</div>
                                    <div style="color:#64748b; font-size:12px;">${parts[1]}</div>
                                    <div style="color:#94a3b8; font-size:11px;">${parts[2]}</div>`;
                        }
                        return data;
                    } 
                },
                {
                    data: 'avg_speed',
                    name: 'avg_speed',
                    render: function(data) {
                        return `<span style="color:#ea580c; font-weight:700; font-size:14px;">${data}</span>`;
                    }
                },
                {
                    data: 'max_speed',
                    name: 'max_speed',
                    render: function(data) {
                        return `<span style="color:#dc2626; font-weight:700; font-size:14px;">${data}</span>`;
                    }
                }
            ],
            order: [[3, 'desc']],
            pageLength: 25,
            lengthMenu: [10, 25, 50, 100],
            language: {
                emptyTable: "No data available in table",
                info: "Showing _START_ to _END_ of _TOTAL_ entries",
                infoEmpty: "Showing 0 to 0 of 0 entries",
            }
        });
    }

    function triggerReload() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(function() {
            if(table) {
                $('#tableSkeleton').css('display', 'flex');
                table.ajax.reload(null, false);
            }
        }, 300);
    }

    // Initialize Table
    initTable();

    // ---- Sidebar Logic ----
    $('.tree-parent').on('click', function(e) {
        if ($(e.target).is('input[type="checkbox"]')) return;
        $(this).toggleClass('open');
    });

    $('.group-checkbox').on('change', function() {
        let isChecked = $(this).is(':checked');
        let $parentLi = $(this).closest('.tree-item');
        $parentLi.find('.tree-checkbox').prop('checked', isChecked);
        triggerReload();
        updateSelectedCounter();
    });

    $('.device-checkbox').on('change', function() {
        triggerReload();
        updateSelectedCounter();
    });

    $('#selectAllBtn').click(function() {
        $('.tree-checkbox').prop('checked', true);
        triggerReload();
        updateSelectedCounter();
    });

    $('#clearBtn').click(function() {
        $('.tree-checkbox').prop('checked', false);
        triggerReload();
        updateSelectedCounter();
    });

    // ---- Sidebar Filter Logic (Location & Series only) ----
    function filterTree() {
        let location = $('#locationFilter').val();
        let series = $('#seriesFilter').val();

        // Jika kedua filter kosong → tampilkan SEMUA
        if (!location && !series) {
            $('.tree-child').attr('style', 'display: flex !important');
            $('.tree-item').attr('style', 'display: list-item !important');
            $('.tree-parent').attr('style', 'display: flex !important');
            
            // Reset children containers
            $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-children').each(function() {
                let $children = $(this);
                let $parent = $children.prev('.tree-parent');
                if ($parent.hasClass('open')) {
                    $children.css('display', 'block');
                } else {
                    $children.css('display', '');
                }
            });
            
            // Reset group counters
            $('.tree-view > .tree-item > .tree-children > .tree-item').each(function() {
                let $groupItem = $(this);
                let total = $groupItem.find('> .tree-children > .tree-child').length;
                let $counter = $groupItem.find('> .tree-parent .group-count');
                $counter.html(`(${total}|<span style="color: #16a34a; font-weight: 700;">${total}</span>)`);
            });
            
            // Update master counter
            let totalDevices = $('.tree-child').length;
            let $masterCounter = $('.tree-view > .tree-item > .tree-parent .group-count').eq(0);
            $masterCounter.html(`(${totalDevices}|<span style="color: #16a34a; font-weight: 700;">${totalDevices}</span>)`);
            
            return;
        }

        let totalMatches = 0;
        
        // First, hide all devices
        $('.tree-child').each(function() {
            $(this).attr('style', 'display: none !important');
        });
        
        // Show only devices that match filter
        $('.tree-child').each(function() {
            let $treeChild = $(this);
            let deviceLocation = $treeChild.data('location') || '';
            let deviceSeries = $treeChild.data('series') || '';
            let shouldShow = true;
            
            // Check location match
            if (location && deviceLocation !== location) {
                shouldShow = false;
            }
            
            // Check series match  
            if (series && shouldShow) {
                let normalizedSelected = series.trim().toUpperCase().replace(/\s+/g, ' ');
                let normalizedDevice = (deviceSeries || '').trim().toUpperCase().replace(/\s+/g, ' ');
                
                if (series.toUpperCase() === 'VOLVO') {
                    if (normalizedDevice !== 'VOLVO') {
                        shouldShow = false;
                    }
                } else {
                    if (normalizedDevice !== normalizedSelected && !normalizedDevice.includes(normalizedSelected)) {
                        shouldShow = false;
                    }
                }
            }
            
            if (shouldShow) {
                $treeChild.attr('style', 'display: flex !important');
                totalMatches++;
            }
        });
        
        // Hide/show groups based on visible devices
        let visibleGroups = 0;
        $('.tree-view > .tree-item > .tree-children > .tree-item').each(function() {
            let $groupItem = $(this);
            let $groupChildren = $groupItem.find('> .tree-children > .tree-child');
            let visibleCount = 0;
            
            $groupChildren.each(function() {
                let style = $(this).attr('style') || '';
                if (style.includes('display: flex')) {
                    visibleCount++;
                }
            });
            
            if (visibleCount > 0) {
                $groupItem.attr('style', 'display: list-item !important');
                $groupItem.find('> .tree-parent').attr('style', 'display: flex !important');
                
                // Allow children to be displayed IF parent is manually opened
                let $children = $groupItem.find('> .tree-children');
                if ($groupItem.find('> .tree-parent').hasClass('open')) {
                    $children.attr('style', 'display: block !important');
                } else {
                    $children.css('display', '');
                }
                
                // Update group counter
                let $counter = $groupItem.find('> .tree-parent .group-count');
                $counter.html(`(${visibleCount}|<span style="color: #16a34a; font-weight: 700;">${visibleCount}</span>)`);
                
                visibleGroups++;
            } else {
                $groupItem.attr('style', 'display: none !important');
                $groupItem.find('> .tree-parent').removeClass('open');
                $groupItem.find('> .tree-children').attr('style', 'display: none !important');
            }
        });
        
        // Update master ALL GPE counter
        let $masterCounter = $('.tree-view > .tree-item > .tree-parent .group-count').eq(0);
        $masterCounter.html(`(${totalMatches}|<span style="color: #16a34a; font-weight: 700;">${totalMatches}</span>)`);
    }

    $('#locationFilter, #seriesFilter').on('change', function() {
        // Close all sub-groups (collapse them) when filter changes
        $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-parent').removeClass('open');
        $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-children').slideUp(200);
        
        filterTree();
        triggerReload(); // trigger table reload for server-side
    });

    function updateSelectedCounter() {
        // Simple visual update for total devices count
        let total = $('.device-checkbox').length;
        let active = $('.device-checkbox:checked').length;
        $('#totalDeviceCount').html(`(${total}|<span style="color: #16a34a; font-weight: 700;">${active}</span>)`);
    }

    // ---- Date / Shift / Hour Filter ----
    const shiftHours = {
        'full': [0,1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23],
        'shift1': [7,8,9,10,11,12,13,14,15,16,17,18],
        'shift2': [19,20,21,22,23,0,1,2,3,4,5,6],
        'op_malam': [18,19,20,21,22,23],
        'op_dini_hari': [0,1,2,3,4,5,6],
        'op_pagi': [7,8,9,10,11],
        'op_siang': [12,13,14,15,16,17]
    };

    function updateHourDropdown() {
        let shift = $('#filterShift').val();
        let currentHour = $('#filterHour').val(); // Preserve selected hour if valid
        let hours = shiftHours[shift] || shiftHours['full'];
        let $hourSelect = $('#filterHour');
        
        $hourSelect.empty();
        $hourSelect.append('<option value="">Semua Jam</option>');
        
        hours.forEach(h => {
            let nextH = (h + 1) % 24;
            let formattedStart = h.toString().padStart(2, '0') + ':00';
            let formattedEnd = nextH.toString().padStart(2, '0') + ':00';
            let formatted = formattedStart + ' - ' + formattedEnd;
            let isSelected = (currentHour !== '' && parseInt(currentHour) === h) ? 'selected' : '';
            $hourSelect.append(`<option value="${h}" ${isSelected}>${formatted}</option>`);
        });
        
        // If the previously selected hour is no longer in the list, reset it
        if (currentHour !== '' && !hours.includes(parseInt(currentHour))) {
            $hourSelect.val('');
        }
    }

    $('#filterShift').on('change', function() {
        updateHourDropdown();
        triggerReload();
    });

    $('#filterDate, #filterHour').on('change', function() {
        triggerReload();
    });

    updateHourDropdown();

    // ---- Table Checkbox Logic ----
    $('#selectAllRows').on('change', function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });

    $('#performanceTable tbody').on('change', '.row-checkbox', function() {
        if (!$(this).prop('checked')) {
            $('#selectAllRows').prop('checked', false);
        } else if ($('.row-checkbox:checked').length === $('.row-checkbox').length) {
            $('#selectAllRows').prop('checked', true);
        }
    });

    table.on('draw', function() {
        $('#selectAllRows').prop('checked', false);
    });

    // ---- Export ----
    function doExport(exportType) {
        $('#exportLoading').css('display', 'flex');
        
        let formData = {
            _token: '{{ csrf_token() }}',
            export_type: exportType,
            location: $('#locationFilter').val(),
            series: $('#seriesFilter').val(),
            date: $('#filterDate').val(),
            shift: $('#filterShift').val(),
            hour: $('#filterHour').val()
        };

        if (exportType === 'selected') {
            let selectedRows = [];
            $('.row-checkbox:checked').each(function() {
                selectedRows.push($(this).val());
            });
            
            if (selectedRows.length === 0) {
                alert('Please select at least one row from the table to export.');
                $('#exportLoading').hide();
                return;
            }
            formData.row_ids = selectedRows.join(',');
        } else {
            let selectedDevices = getSelectedDevices();
            if (selectedDevices.length === 0) {
                alert('Please select at least one device from the sidebar.');
                $('#exportLoading').hide();
                return;
            }
            formData.device_ids = selectedDevices.join(',');
        }

        let form = $('<form>', {
            action: "{{ route('frontend.speed-performance.export') }}",
            method: 'POST'
        });

        $.each(formData, function(key, value) {
            $('<input>').attr({ type: 'hidden', name: key, value: value }).appendTo(form);
        });

        form.appendTo('body').submit();
        
        setTimeout(() => {
            $('#exportLoading').hide();
            form.remove();
        }, 2000);
    }

    $('.btn-export-selected').click(function() { doExport('selected'); });
    $('.btn-export-all').click(function() { doExport('all'); });
});
</script>
@endsection
