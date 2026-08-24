@extends('frontend.layouts.app')

@section('title', 'Speed Monitoring')

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
    .search-box {
        position: relative;
    }
    .search-box i {
        position: absolute;
        left: 12px;
        top: 10px;
        color: #adb5bd;
        font-size: 14px;
    }
    .search-box input {
        padding-left: 35px;
        border-radius: 6px;
        font-size: 13px;
        border: 1px solid rgba(255,255,255,0.1);
        background-color: rgba(255,255,255,0.05);
        color: white;
    }
    .search-box input:focus {
        border-color: #3b82f6;
        background-color: rgba(255,255,255,0.1);
        color: white;
        box-shadow: none;
        outline: none;
    }
    .search-box input::placeholder {
        color: #64748b;
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
    .page-title i { color: #1963f2; font-size: 22px; }
    .page-subtitle {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 25px;
    }

    /* ===== TABLE CONTAINER ===== */
    .table-container {
        background: white;
        border-radius: 8px;
        padding: 20px;
        border: 1px solid #eaedf2;
        overflow-x: auto;
    }

    #speedTable { font-size: 11px; width: 100% !important; }
    #speedTable thead th {
        background-color: #f8fafc;
        color: #475569;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.2px;
        padding: 8px 10px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    #speedTable tbody td {
        padding: 6px 10px;
        vertical-align: middle;
        font-size: 11px;
        color: #334155;
        white-space: nowrap;
    }
    #speedTable tbody tr:hover { background-color: #f8fafc; }

    /* Speed badges */
    .speed-badge {
        display: inline-block;
        padding: 4px 12px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 13px;
    }
    .speed-normal { background-color: #d1fae5; color: #065f46; }
    .speed-warning { background-color: #fef3c7; color: #92400e; }
    .speed-danger  { background-color: #fee2e2; color: #991b1b; }
    /* Filter-mode badges */
    .speed-low  { background-color: #dbeafe; color: #1d4ed8; border: 1.5px solid #93c5fd; }
    .speed-high { background-color: #fee2e2; color: #dc2626; border: 1.5px solid #fca5a5; }

    /* Speed Filter Buttons */
    .speed-filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .speed-filter-group label {
        font-size: 12px;
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        margin: 0;
        white-space: nowrap;
    }
    .btn-speed-filter {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
        border: 2px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        background: #f1f5f9;
        color: #64748b;
    }
    .btn-speed-filter:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .btn-speed-filter.active-low {
        background: #eff6ff;
        color: #1963f2;
        border-color: #1963f2;
    }
    .btn-speed-filter.active-high {
        background: #fee2e2;
        color: #dc2626;
        border-color: #dc2626;
    }

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
        display: none !important; /* Sembunyikan tulisan 'Processing...' bawaan DataTables */
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
        border-color: #dc2626;
    }
    .speed-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
    }

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
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
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
    .date-input {
        padding: 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        color: #334155;
    }
    .date-input:focus {
        outline: none;
        border-color: #1963f2;
        box-shadow: 0 0 0 2px rgba(25,99,242,0.15);
    }
    .records-badge {
        background-color: #eff6ff;
        color: #1963f2;
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
</style>
@endsection

@section('sidebar')
<!-- Device Filter Search -->
<div class="filter-section px-4 py-3">
    <div class="filter-label">DEVICE FILTER</div>
    <div class="search-box">
        <i class="fas fa-search"></i>
        <input type="search" class="form-control" id="deviceSearch" placeholder="Search device...">
    </div>
</div>

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
                <span class="group-count">({{ $totalDevices }}|<span style="color: #4ade80; font-weight: 700;">{{ $totalActive }}</span>)</span>
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
                                <li class="tree-child" data-device-name="{{ strtolower($device->device_name) }}" data-location="{{ $device->location ?: ($device->lokasi ?? '') }}" data-series="{{ $device->series ?? '' }}">
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
    <div class="system-active-title">System Active</div>
    <p class="system-active-desc">Speed monitoring berjalan normal</p>
</div>
@endsection

@section('content')
<div class="page-title">
    <i class="fas fa-tachometer-alt"></i>
    <h1>GPS Speed Monitoring</h1>
</div>
<p class="page-subtitle">Real-time vehicle speed tracking and overspeed detection</p>

<!-- Date Filter Row -->
<div class="top-filter-row">
    <div class="filter-group-date">
        <label><i class="far fa-calendar-alt me-1"></i> TANGGAL</label>
        <input type="date" id="filterDate" class="date-input" value="{{ date('Y-m-d') }}">
    </div>

    <!-- Speed Filter -->
    <div class="speed-filter-group ms-3">
        <label><i class="fas fa-tachometer-alt me-1"></i> SPEED</label>
        <button type="button" class="btn-speed-filter active-low" id="btnLowSpeed">
            <span class="speed-dot" style="background:#1963f2;"></span>
            Low Speed &lt;15 km/h
        </button>
        <button type="button" class="btn-speed-filter" id="btnHighSpeed">
            <span class="speed-dot" style="background:#dc2626;"></span>
            High Speed &ge;41 km/h
        </button>
    </div>

    <div class="ms-auto d-flex align-items-center gap-2">
        <span class="records-badge me-2"><i class="fas fa-database me-1"></i><span id="recordCount">0</span> records</span>
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
        @for($i=0; $i<10; $i++)
            <div class="skeleton-row" style="width: {{ rand(80, 100) }}%;"></div>
        @endfor
    </div>

    <table id="speedTable" class="table table-hover" style="width:100%">
        <thead>
            <tr>
                <th style="width: 40px; text-align: center;"><input type="checkbox" id="selectAllRows"></th>
                <th>Serial No.</th>
                <th>Device Name (ID)</th>
                <th>Fleet</th>
                <th>Speed</th>
                <th>Altitude</th>
                <th>Time</th>
                <th>Location</th>
                <th>Accuracy</th>
                <th>Direction</th>
                <th>Qty of Satellites</th>
                <th>Input and Output Status</th>
                <th>Emergency Alarm</th>
                <th>Ignition</th>
            </tr>
        </thead>
    </table>
</div>
@endsection

@section('scripts')
<script>
$(function() {
    // ---- Helpers ----
    function getSelectedDeviceIds() {
        let ids = [];
        $('.device-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        return ids;
    }

    // ---- Speed Filter State — default: Low Speed aktif ----
    let activeSpeedFilter = 'low';

    // Disable annoying DataTables alert popup (e.g., when AJAX is aborted by clicking another filter quickly)
    $.fn.dataTable.ext.errMode = 'none';

    // ---- DataTables Init ----
    let table = $('#speedTable').on('error.dt', function(e, settings, techNote, message) {
        console.log('DataTables error:', message);
    }).DataTable({
        processing: true,
        serverSide: true,
        bFilter: false, // Hapus fitur search default DataTables
        scrollX: true,  // Aktifkan horizontal scroll jika kolom terlalu banyak
        ajax: {
            url: '{{ route('frontend.speed.data') }}',
            type: 'POST',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            data: function(d) {
                d.device_ids   = JSON.stringify(getSelectedDeviceIds());
                d.location     = $('#locationFilter').val();
                d.series       = $('#seriesFilter').val();
                d.start_date   = $('#filterDate').val();
                d.end_date     = $('#filterDate').val();
                d.speed_filter = activeSpeedFilter; // 'low', 'high', or ''
            }
        },
        columns: [
            { 
                data: 'checkbox', 
                name: 'checkbox', 
                orderable: false, 
                searchable: false,
                render: function(data) {
                    return data; // contains HTML checkbox generated from backend
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
                    return `${data || '-'}(${row.device_id || ''})`;
                }
            },
            { data: 'fleet_name', name: 'fleet_name', render: function(data) { return data || '-'; } },
            {
                data: 'speed',
                name: 'speed',
                render: function(data) {
                    let cls, label;
                    const spd = parseInt(data) || 0;
                    if (spd >= 100) {
                        cls   = 'speed-high';   // merah — sangat cepat
                        label = '🔴';
                    } else if (spd >= 41) {
                        cls   = 'speed-warning'; // kuning — cepat
                        label = '🟡';
                    } else if (spd >= 15) {
                        cls   = 'speed-normal';  // hijau — normal
                        label = '🟢';
                    } else if (spd >= 1) {
                        cls   = 'speed-low';     // biru — lambat
                        label = '🔵';
                    } else {
                        cls   = 'speed-normal';
                        label = '';
                    }
                    return `<span class="speed-badge ${cls}">${spd} Km/h</span>`;
                }
            },
            { data: 'altitude', name: 'altitude', render: function(data) { return data ? data : '-'; } },
            { data: 'gps_time', name: 'gps_time' },
            {
                data: 'latitude',
                name: 'latitude',
                render: function(data, type, row) {
                    if (!row.latitude || !row.longitude) return '-';
                    return `<a href="https://www.google.com/maps?q=${row.latitude},${row.longitude}" target="_blank" class="text-decoration-none">
                                <span class="text-success"><i class="fas fa-map-marker-alt"></i></span> 
                                <span class="text-secondary hover-primary">${row.latitude},${row.longitude}</span>
                            </a>`;
                }
            },
            { data: null, searchable: false, orderable: false, render: function() { return '-'; } }, // Accuracy
            { data: 'direction', name: 'direction', render: function(data) { return data || '0'; } },
            { data: 'satellites', name: 'satellites', render: function(data) { return data ? data : '-'; } },
            { data: 'input_output_status', name: 'input_output_status', render: function(data) { return data ? data : '-'; } },
            { data: 'is_emergency', name: 'is_emergency', render: function(data) { return data ? '1' : '-'; } },
            {
                data: 'is_acc_on',
                name: 'is_acc_on',
                render: function(data) {
                    return data
                        ? '<span class="text-success fs-5"><i class="fas fa-toggle-on"></i></span>'
                        : '<span class="text-secondary fs-5"><i class="fas fa-toggle-off"></i></span>';
                }
            }
        ],
        order: [[6, 'desc']],
        lengthMenu: [[50, 100, 200, 300, 500], [50, 100, 200, 300, 500]],
        pageLength: 50,
        drawCallback: function(settings) {
            $('#recordCount').text(settings.json ? (settings.json.recordsFiltered || 0) : 0);
        }
    });

    // ---- DataTables Event Handlers (Skeleton Loader) ----
    table.on('preXhr.dt', function (e, settings, data) {
        $('#tableSkeleton').css('display', 'flex');
        $('#speedTable tbody').css('opacity', '0.3');
    });

    table.on('xhr.dt', function (e, settings, json, xhr) {
        setTimeout(function() {
            $('#tableSkeleton').hide();
            $('#speedTable tbody').css('opacity', '1');
        }, 300); // Beri sedikit delay agar efek skeleton terasa smooth
    });

    // ---- Reload helper ----
    function reloadTable() {
        table.ajax.reload(null, false);
    }

    // ---- Tree View: toggle open/close ----
    $(document).on('click', '.tree-parent', function(e) {
        if (e.target.type === 'checkbox') return;
        $(this).toggleClass('open');
    });

    // ---- Group checkbox: check/uncheck all children ----
    $(document).on('change', '.group-checkbox', function() {
        let checked = $(this).prop('checked');
        let group   = $(this).data('group');

        if (group === 'all') {
            $('.tree-checkbox').prop('checked', checked);
        } else {
            $(`.device-checkbox[data-group="${group}"]`).prop('checked', checked);
            // sync ALL GPE
            let total    = $('.device-checkbox').length;
            let checkedN = $('.device-checkbox:checked').length;
            $('[data-group="all"]').prop('indeterminate', checkedN > 0 && checkedN < total);
            $('[data-group="all"]').prop('checked', checkedN === total);
        }
        // sync parent group checkbox state
        syncGroupCheckboxes();
        reloadTable();
    });

    // ---- Device checkbox: update group states ----
    $(document).on('change', '.device-checkbox', function() {
        syncGroupCheckboxes();
        reloadTable();
    });

    function syncGroupCheckboxes() {
        // sync each group
        $('.group-checkbox[data-group!="all"]').each(function() {
            let group      = $(this).data('group');
            let total      = $(`.device-checkbox[data-group="${group}"]`).length;
            let checkedN   = $(`.device-checkbox[data-group="${group}"]:checked`).length;
            $(this).prop('indeterminate', checkedN > 0 && checkedN < total);
            $(this).prop('checked', checkedN === total);
        });
        // sync ALL GPE
        let total    = $('.device-checkbox').length;
        let checkedN = $('.device-checkbox:checked').length;
        $('[data-group="all"]').prop('indeterminate', checkedN > 0 && checkedN < total);
        $('[data-group="all"]').prop('checked', checkedN === total);
    }

    // ---- Select All / Clear ----
    $('#selectAllBtn').click(function() {
        $('.tree-checkbox').prop('checked', true).prop('indeterminate', false);
        reloadTable();
    });

    $('#clearBtn').click(function() {
        $('.device-checkbox').prop('checked', false);
        syncGroupCheckboxes();
        reloadTable();
    });

    // ---- Device Search Filter ----
    $('#deviceSearch').on('input', function() {
        let searchQuery = $(this).val().toLowerCase().trim();
        if (searchQuery === '') {
            $('.tree-child').css({ 'background-color': '', 'padding': '', 'border-radius': '', 'display': '' });
            filterTreeBySeriesLocation();
            return;
        }
        
        let totalMatching = 0;
        let selectedLocation = $('#locationFilter').val();
        let selectedSeries = $('#seriesFilter').val();
        
        $('.tree-child').each(function() {
            let $device = $(this);
            let deviceName = $device.find('span').text().toLowerCase();
            let deviceLocation = $device.data('location') || '';
            let deviceSeries = $device.data('series') || '';
            
            let nameMatches = deviceName.includes(searchQuery);
            let locationMatches = !selectedLocation || deviceLocation === selectedLocation;
            let seriesMatches = true;
            
            if (selectedSeries) {
                let normalizedSelected = selectedSeries.trim().toUpperCase().replace(/\s+/g, ' ');
                let normalizedDevice = (deviceSeries || '').trim().toUpperCase().replace(/\s+/g, ' ');
                if (selectedSeries === 'VOLVO') {
                    seriesMatches = normalizedDevice === 'VOLVO';
                } else {
                    seriesMatches = normalizedDevice === normalizedSelected || normalizedDevice.includes(normalizedSelected);
                }
            }
            
            if (nameMatches && locationMatches && seriesMatches) {
                $device.attr('style', 'display: flex !important');
                $device.css({ 'background-color': '#dbeafe', 'padding': '8px 12px', 'border-radius': '6px', 'margin': '2px 0' });
                totalMatching++;
            } else {
                $device.attr('style', 'display: none !important');
            }
        });
        
        setTimeout(function() {
            let $subGroups = $('.tree-view > .tree-item > .tree-children > .tree-item');
            $subGroups.each(function() {
                let $groupItem = $(this);
                let $groupParent = $groupItem.find('> .tree-parent');
                let $groupChildren = $groupItem.find('> .tree-children');
                let $devices = $groupChildren.find('> .tree-child');
                
                let visibleInGroup = 0;
                $devices.each(function() {
                    let display = $(this).attr('style');
                    if (display && display.includes('display: flex')) visibleInGroup++;
                });
                
                if (visibleInGroup > 0) {
                    $groupItem.attr('style', 'display: list-item !important');
                    $groupParent.addClass('open');
                    $groupChildren.attr('style', 'display: block !important');
                } else {
                    $groupItem.attr('style', 'display: none !important');
                    $groupParent.removeClass('open');
                    $groupChildren.attr('style', 'display: none !important');
                }
            });
            
            let $allGpeParent = $('.tree-view > .tree-item');
            let $allGpeChildren = $allGpeParent.find('> .tree-children');
            if (totalMatching > 0) {
                $allGpeParent.attr('style', 'display: list-item !important');
                $allGpeParent.find('> .tree-parent').addClass('open');
                $allGpeChildren.attr('style', 'display: block !important');
            } else {
                $allGpeParent.attr('style', 'display: list-item !important');
                $allGpeParent.find('> .tree-parent').addClass('open');
                $allGpeChildren.attr('style', 'display: none !important');
            }
        }, 10);
    });

    $('#deviceSearch').on('search', function() {
        if ($(this).val() === '') filterTreeBySeriesLocation();
    });

    // ---- Location & Series Filter ----
    $('#locationFilter, #seriesFilter').change(function() {
        // Close all sub-groups (collapse them) when filter changes
        $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-parent').removeClass('open');
        $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-children').slideUp(200);
        
        filterTreeBySeriesLocation();
        reloadTable();
    });

    function filterTreeBySeriesLocation() {
        let selectedLocation = $('#locationFilter').val();
        let selectedSeries = $('#seriesFilter').val();
        
        // Jika kedua filter kosong → tampilkan SEMUA
        if (!selectedLocation && !selectedSeries) {
            $('.tree-child').attr('style', 'display: flex !important');
            $('.tree-item').attr('style', 'display: list-item !important');
            $('.tree-parent').attr('style', 'display: flex !important');
            
            // ✅ Reset children containers
            $('.tree-view > .tree-item > .tree-children > .tree-item > .tree-children').each(function() {
                let $children = $(this);
                let $parent = $children.prev('.tree-parent');
                if ($parent.hasClass('open')) {
                    $children.css('display', 'block');
                } else {
                    $children.css('display', '');
                }
            });
            
            // Reset semua group counter
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
        
        // Sembunyikan semua device dulu
        $('.tree-child').attr('style', 'display: none !important');
        
        // Tampilkan hanya device yang cocok dengan filter
        $('.tree-child').each(function() {
            let $treeChild = $(this);
            let deviceLocation = $treeChild.attr('data-location') || '';
            let deviceSeries = $treeChild.attr('data-series') || '';
            let shouldShow = true;
            
            // Cek filter location — case insensitive & trim
            if (selectedLocation) {
                let normLoc = selectedLocation.trim().toUpperCase();
                let normDevLoc = deviceLocation.trim().toUpperCase();
                if (normDevLoc !== normLoc && !normDevLoc.includes(normLoc)) {
                    shouldShow = false;
                }
            }
            
            // Cek filter series — case insensitive & trim
            if (selectedSeries && shouldShow) {
                let normalizedSelected = selectedSeries.trim().toUpperCase().replace(/\s+/g, ' ');
                let normalizedDevice = deviceSeries.trim().toUpperCase().replace(/\s+/g, ' ');
                if (selectedSeries === 'VOLVO') {
                    if (normalizedDevice !== 'VOLVO') shouldShow = false;
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
        
        // Update visibilitas group berdasarkan jumlah device yang terlihat
        // Gunakan pengecekan attr('style') karena :visible tidak mendeteksi !important
        let $allGroups = $('.tree-view > .tree-item > .tree-children > .tree-item');
        $allGroups.each(function() {
            let $groupItem = $(this);
            let $groupChildren = $groupItem.find('> .tree-children > .tree-child');
            let visibleCount = 0;
            
            $groupChildren.each(function() {
                let style = $(this).attr('style') || '';
                if (style.includes('display: flex')) visibleCount++;
            });
            
            if (visibleCount > 0) {
                $groupItem.attr('style', 'display: list-item !important');
                // ❌ REMOVED: Auto-expand - let user manually expand groups
                // $groupItem.find('> .tree-parent').addClass('open');
                $groupItem.find('> .tree-parent').attr('style', 'display: flex !important');
                
                // ✅ ALLOW children to be displayed IF parent is manually opened
                let $children = $groupItem.find('> .tree-children');
                if ($groupItem.find('> .tree-parent').hasClass('open')) {
                    $children.attr('style', 'display: block !important');
                } else {
                    $children.css('display', '');  // Remove inline style
                }
                
                let $counter = $groupItem.find('> .tree-parent .group-count');
                $counter.html(`(${visibleCount}|<span style="color: #16a34a; font-weight: 700;">${visibleCount}</span>)`);
            } else {
                $groupItem.attr('style', 'display: none !important');
                $groupItem.find('> .tree-parent').removeClass('open');
                $groupItem.find('> .tree-children').attr('style', 'display: none !important');
            }
        });
        
        let $masterCounter = $('.tree-view > .tree-item > .tree-parent .group-count').eq(0);
        $masterCounter.html(`(${totalMatches}|<span style="color: #16a34a; font-weight: 700;">${totalMatches}</span>)`);
    }

    // ---- Date Filter: auto reload on change ----
    $('#filterDate').change(function() {
        reloadTable();
    });

    // ---- Export Logic ----
    function triggerExport(extraData) {
        let loadingMsg = $('#exportLoading .text-muted.small');
        loadingMsg.text('Memulai proses export...');
        $('#exportLoading').css('display', 'flex');
        
        let url = "{{ route('frontend.speed.export') }}";
        
        let defaultData = {
            start_date: $('#filterDate').val(),
            end_date: $('#filterDate').val(),
            speed_filter: activeSpeedFilter,
            location: $('#locationFilter').val(),
            series: $('#seriesFilter').val(),
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        
        let selectedDevices = getSelectedDeviceIds();
        defaultData.device_ids = JSON.stringify(selectedDevices);
        
        let data = $.extend({}, defaultData, extraData);
        
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            success: function(response) {
                if (response.use_queue && response.job_id) {
                    loadingMsg.text('Data sedang diproses di background. Mohon tunggu...');
                    pollExportStatus(response.job_id);
                } else {
                    $('#exportLoading').hide();
                    alert('Gagal memulai background job.');
                }
            },
            error: function(xhr) {
                $('#exportLoading').hide();
                alert('Terjadi kesalahan saat memulai export.');
            }
        });
    }

    function pollExportStatus(jobId) {
        let statusUrl = "{{ url('/speed/export-status') }}/" + jobId;
        let loadingMsg = $('#exportLoading .text-muted.small');
        
        let pollInterval = setInterval(function() {
            $.ajax({
                url: statusUrl,
                type: 'GET',
                success: function(res) {
                    if (res.status === 'completed') {
                        clearInterval(pollInterval);
                        loadingMsg.text('Selesai! Mendownload file...');
                        setTimeout(function() {
                            $('#exportLoading').hide();
                            window.location.href = res.download_url;
                        }, 1000);
                    } else if (res.status === 'failed') {
                        clearInterval(pollInterval);
                        $('#exportLoading').hide();
                        alert('Proses export gagal. Silakan coba lagi.');
                    } else {
                        // pending or processing
                        let dots = loadingMsg.text().split('.').length - 1;
                        let nextDots = (dots % 3) + 1;
                        
                        let progressText = 'Data sedang diproses di background' + '.'.repeat(nextDots);
                        if (res.total > 0) {
                            progressText += '<br><span style="color: #16a34a; font-weight: bold; font-size: 1.1em;">Progress: ' + res.progress + '% (' + res.total.toLocaleString('id-ID') + ' baris)</span>';
                        } else if (res.progress > 0) {
                            progressText += '<br><span style="color: #1963f2; font-weight: bold; font-size: 1.1em;">Sedang mengekspor: ' + res.progress.toLocaleString('id-ID') + ' baris...</span>';
                        }
                        
                        loadingMsg.html(progressText);
                    }
                },
                error: function() {
                    clearInterval(pollInterval);
                    $('#exportLoading').hide();
                    alert('Koneksi terputus saat mengecek status.');
                }
            });
        }, 3000);
    }

    $('.btn-export-all').click(function() {
        triggerExport({});
    });

    $('.btn-export-selected').click(function() {
        let selectedRows = [];
        $('.row-checkbox:checked').each(function() {
            selectedRows.push($(this).val());
        });
        
        if (selectedRows.length === 0) {
            alert('Silakan pilih minimal 1 baris untuk diexport!');
            return;
        }
        
        triggerExport({
            selected_ids: JSON.stringify(selectedRows)
        });
    });
    
    $('#selectAllRows').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });

    // ---- Speed Filter Toggle Buttons ----
    // Salah satu tombol HARUS selalu aktif (tidak bisa keduanya off)
    $('#btnLowSpeed').click(function() {
        if (activeSpeedFilter === 'low') return; // sudah aktif, abaikan
        activeSpeedFilter = 'low';
        $(this).addClass('active-low');
        $('#btnHighSpeed').removeClass('active-high');
        reloadTable();
    });

    $('#btnHighSpeed').click(function() {
        if (activeSpeedFilter === 'high') return; // sudah aktif, abaikan
        activeSpeedFilter = 'high';
        $(this).addClass('active-high');
        $('#btnLowSpeed').removeClass('active-low');
        reloadTable();
    });
});
</script>
@endsection
