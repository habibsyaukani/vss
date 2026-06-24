<?php $__env->startSection('title', 'Idle Monitor'); ?>



<?php $__env->startSection('styles'); ?>
<style>
    /* ========================================
       OVERRIDE MAIN CONTENT OVERFLOW
       Prevent horizontal scroll on main-content
       ======================================== */
    .main-content {
        overflow-x: hidden !important; /* Prevent horizontal scroll on main container */
        overflow-y: auto; /* Keep vertical scroll */
    }

    /* Sidebar Specific Styles */
    .filter-section {
        border-bottom: 1px solid #eaedf2;
    }
    .filter-section:first-child {
        padding-top: 35px !important; /* Push down from the top navbar */
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
        border: 1px solid #eaedf2;
    }
    .search-box input:focus {
        border-color: #1963f2;
        box-shadow: 0 0 0 0.2rem rgba(25, 99, 242, 0.15);
    }
    .form-select-sm {
        font-size: 13px;
        border-color: #eaedf2;
        border-radius: 6px;
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
    .btn-clear i {
        margin-right: 4px;
    }
    
    /* Tree View Styles */
    .tree-view {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .tree-item {
        margin-bottom: 2px;
    }
    .tree-parent {
        display: flex;
        align-items: center;
        padding: 8px 10px;
        cursor: pointer;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
        transition: background 0.2s;
    }
    .tree-parent:hover {
        background-color: #f8f9fa;
    }
    .tree-parent i.toggle-icon {
        width: 15px;
        color: #94a3b8;
        font-size: 12px;
        transition: transform 0.2s;
    }
    .tree-parent.open i.toggle-icon {
        transform: rotate(90deg);
    }
    .tree-checkbox {
        margin-right: 10px;
        width: 16px;
        height: 16px;
        cursor: pointer;
    }
    .group-icon {
        color: #1963f2;
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
    .tree-parent.open ~ .tree-children {
        display: block !important;
    }
    .tree-child {
        display: flex;
        align-items: center;
        padding: 6px 10px;
        font-size: 13px;
        color: #475569;
        border-radius: 6px;
    }
    .tree-child:hover {
        background-color: #f8f9fa;
    }
    .tree-child input[type="checkbox"] {
        margin-right: 10px;
    }
    
    /* System Active Box */
    .system-active-box {
        margin: 20px;
        padding: 15px;
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        border-radius: 8px;
    }
    .system-active-title {
        color: #16a34a;
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
        background-color: #16a34a;
        border-radius: 50%;
    }
    .system-active-desc {
        color: #475569;
        font-size: 12px;
        margin-top: 5px;
        margin-bottom: 0;
    }

    /* Main Content Styles */
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
    .page-title i {
        color: #1963f2;
        font-size: 22px;
    }
    .page-subtitle {
        color: #64748b;
        font-size: 13px;
        margin-bottom: 25px;
    }

    /* Top Filters Row - NO FREEZE (scroll with content) */
    .top-filter-row {
        /* ❌ REMOVED: position: sticky - filter row now scrolls naturally */
        /* position: sticky; */
        /* top: 0; */
        left: 0;
        right: 0;
        /* z-index removed - not needed without sticky */
        display: flex;
        align-items: center;
        gap: 15px;
        background: white;
        padding: 15px 20px;
        border-radius: 8px;
        border: 1px solid #eaedf2;
        margin-bottom: 20px;
        margin-left: 0 !important;
        margin-right: 0 !important;
        width: 100%;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1); /* Shadow to indicate floating */
    }
    .filter-group {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .filter-group label {
        font-size: 13px;
        font-weight: 600;
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
    .duration-select {
        padding: 6px 30px 6px 12px;
        border: 1px solid #e2e8f0;
        border-radius: 6px;
        font-size: 13px;
        color: #334155;
    }
    .records-badge {
        background-color: #eff6ff;
        color: #1963f2;
        padding: 6px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
    .btn-export-selected {
        background-color: #fff7ed;
        color: #ea580c;
        border: 1px solid #fed7aa;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 16px;
        border-radius: 6px;
    }
    .btn-export-all {
        background-color: #10b981;
        color: white;
        border: none;
        font-size: 13px;
        font-weight: 600;
        padding: 6px 16px;
        border-radius: 6px;
    }
    .btn-export-all i { margin-right: 5px; }
    .btn-export-selected i { margin-right: 5px; }

    /* DataTable Customization */
    #alarmTable {
        width: 100% !important;
    }
    #alarmTable th, #alarmTable td {
        white-space: nowrap;
        overflow: visible;
        position: relative;
    }
    
    /* Allow horizontal scroll */
    .table-responsive {
        overflow-x: auto !important;
    }
    
    /* Custom resize handle */
    .col-resizer {
        position: absolute;
        right: 0;
        top: 0;
        height: 100%;
        width: 6px;
        cursor: col-resize;
        user-select: none;
        z-index: 1;
        background: transparent;
    }
    .col-resizer:hover, .col-resizer.resizing {
        background: rgba(25, 99, 242, 0.35);
        border-right: 2px solid #1963f2;
    }
    .dataTables_wrapper .dataTables_length select {
        padding: 4px 30px 4px 10px;
        border-radius: 6px;
        border-color: #eaedf2;
    }
    .dataTables_wrapper .dataTables_info {
        color: #64748b;
        font-size: 13px;
        padding-top: 15px;
    }

    /* ========================================
       STICKY/FROZEN COLUMNS (LEFT SIDE)
       ✅ KEEP: Freeze first 5 columns when scrolling horizontally
       ❌ NO vertical freeze for header
       ======================================== */
    
    /* Table container - Enable horizontal scrolling */
    .table-container {
        background: white;
        border-radius: 8px;
        border: 1px solid #eaedf2;
        padding: 20px;
        position: relative; /* Establish positioning context */
        overflow-x: auto !important; /* Enable horizontal scroll */
        overflow-y: visible;
    }
    
    /* Enable horizontal scrolling for DataTables wrapper */
    .dataTables_scrollBody {
        overflow-x: auto !important;
    }
    
    /* ========================================
       COLUMN 1: CHECKBOX (left: 0px)
       ======================================== */
    #alarmTable thead th:nth-child(1) {
        position: sticky !important;
        left: 0px !important;
        z-index: 15 !important;
        background-color: #f8fafc !important;
        min-width: 50px;
    }
    
    #alarmTable tbody td:nth-child(1) {
        position: sticky !important;
        left: 0px !important;
        z-index: 10 !important;
        background-color: white !important;
        min-width: 50px;
    }
    
    #alarmTable tbody tr:hover td:nth-child(1) {
        background-color: #f8fafc !important;
    }
    
    /* ========================================
       COLUMN 2: DEVICE ID (left: 50px)
       ======================================== */
    #alarmTable thead th:nth-child(2) {
        position: sticky !important;
        left: 50px !important;
        z-index: 15 !important;
        background-color: #f8fafc !important;
        min-width: 120px;
    }
    
    #alarmTable tbody td:nth-child(2) {
        position: sticky !important;
        left: 50px !important;
        z-index: 10 !important;
        background-color: white !important;
        min-width: 120px;
    }
    
    #alarmTable tbody tr:hover td:nth-child(2) {
        background-color: #f8fafc !important;
    }
    
    /* ========================================
       COLUMN 3: DEVICE NAME (left: 170px)
       ======================================== */
    #alarmTable thead th:nth-child(3) {
        position: sticky !important;
        left: 170px !important;
        z-index: 15 !important;
        background-color: #f8fafc !important;
        min-width: 180px;
    }
    
    #alarmTable tbody td:nth-child(3) {
        position: sticky !important;
        left: 170px !important;
        z-index: 10 !important;
        background-color: white !important;
        min-width: 180px;
    }
    
    #alarmTable tbody tr:hover td:nth-child(3) {
        background-color: #f8fafc !important;
    }
    
    /* ========================================
       COLUMN 4: ALARM TYPE (left: 350px)
       ======================================== */
    #alarmTable thead th:nth-child(4) {
        position: sticky !important;
        left: 350px !important;
        z-index: 15 !important;
        background-color: #f8fafc !important;
        min-width: 120px;
    }
    
    #alarmTable tbody td:nth-child(4) {
        position: sticky !important;
        left: 350px !important;
        z-index: 10 !important;
        background-color: white !important;
        min-width: 120px;
    }
    
    #alarmTable tbody tr:hover td:nth-child(4) {
        background-color: #f8fafc !important;
    }
    
    /* ========================================
       COLUMN 5: ALARM STATUS (left: 470px) + SHADOW
       ======================================== */
    #alarmTable thead th:nth-child(5) {
        position: sticky !important;
        left: 470px !important;
        z-index: 15 !important;
        background-color: #f8fafc !important;
        min-width: 130px;
        box-shadow: 3px 0 5px -2px rgba(0,0,0,0.15) !important; /* Shadow to indicate frozen edge */
    }
    
    #alarmTable tbody td:nth-child(5) {
        position: sticky !important;
        left: 470px !important;
        z-index: 10 !important;
        background-color: white !important;
        min-width: 130px;
        box-shadow: 3px 0 5px -2px rgba(0,0,0,0.15) !important; /* Shadow to indicate frozen edge */
    }
    
    #alarmTable tbody tr:hover td:nth-child(5) {
        background-color: #f8fafc !important;
    }
    
    /* ========================================
       BORDERS FOR FROZEN COLUMNS
       ======================================== */
    #alarmTable thead th:nth-child(1),
    #alarmTable thead th:nth-child(2),
    #alarmTable thead th:nth-child(3),
    #alarmTable thead th:nth-child(4),
    #alarmTable thead th:nth-child(5),
    #alarmTable tbody td:nth-child(1),
    #alarmTable tbody td:nth-child(2),
    #alarmTable tbody td:nth-child(3),
    #alarmTable tbody td:nth-child(4),
    #alarmTable tbody td:nth-child(5) {
        border-right: 1px solid #e2e8f0 !important;
    }
    .table > thead {
        background-color: #f8fafc;
    }
    .table > thead > tr > th {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid #eaedf2;
        padding: 12px 10px;
        white-space: nowrap;
    }
    .table > tbody > tr > td {
        font-size: 13px;
        color: #334155;
        vertical-align: middle;
        padding: 12px 10px;
        border-bottom: 1px solid #f1f5f9;
    }
    .table > tbody > tr:hover {
        background-color: #f8fafc;
    }
    
    .alarm-badge-idle {
        background-color: #eff6ff;
        color: #1963f2;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }
    .alarm-badge-end {
        background-color: #f0fdf4;
        color: #16a34a;
        padding: 4px 10px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
        border: 1px solid #bbf7d0;
    }
    .location-link {
        color: #1963f2;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .location-link i { color: #ef4444; }
    .location-link:hover { text-decoration: underline; }
    
    .device-info {
        display: flex;
        flex-direction: column;
    }
    .device-name { font-weight: 600; color: #1e293b; }
    .device-driver { font-size: 12px; color: #64748b; }
    
    .detail-info {
        font-size: 12px;
        color: #64748b;
    }

    /* Skeleton Loader */
    .skeleton-box {
        display: inline-block;
        height: 1em;
        position: relative;
        overflow: hidden;
        background-color: #e2e8f0;
        border-radius: 4px;
        width: 100%;
    }
    .skeleton-box::after {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
        transform: translateX(-100%);
        background-image: linear-gradient(
            90deg,
            rgba(255, 255, 255, 0) 0,
            rgba(255, 255, 255, 0.2) 20%,
            rgba(255, 255, 255, 0.5) 60%,
            rgba(255, 255, 255, 0)
        );
        animation: shimmer 2s infinite;
        content: '';
    }
    @keyframes shimmer {
        100% {
            transform: translateX(100%);
        }
    }
    .dataTables_processing {
        background: white !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0 !important;
        border-radius: 8px;
        padding: 20px !important;
        margin-top: 10px;
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

    /* COMPACT TABLE STYLES */
    #alarmTable {
        font-size: 11.5px;
    }
    #alarmTable th, #alarmTable td {
        padding: 4px 8px !important;
        vertical-align: middle;
    }
    .dataTables_wrapper .dataTables_length, 
    .dataTables_wrapper .dataTables_info, 
    .dataTables_wrapper .dataTables_paginate {
        font-size: 12px;
        margin-top: 5px;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('sidebar'); ?>
<!-- Filter Search -->
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
        <option value="UTARA">Lokasi Utara</option>
        <option value="JO SELATAN">JO Selatan</option>
        <option value="SELATAN">Selatan</option>
        <option value="M.SERVICE">M Service</option>
    </select>
</div>

<!-- Series Filter -->
<div class="filter-section px-4 py-3">
    <div class="filter-label">SERIES</div>
    <select class="form-select form-select-sm" id="seriesFilter">
        <option value="">Semua</option>
        <option value="HD 465">HD 465</option>
        <option value="HD 785">HD 785</option>
        <option value="OHT 773">OHT 773</option>
        <option value="VOLVO">Volvo</option>
    </select>
</div>

<!-- Actions -->
<div class="px-4 py-3 d-flex justify-content-between align-items-center border-bottom border-light">
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
                <i class="fas fa-car-side group-icon" style="color: #0f766e;"></i>
                <span style="font-weight: 700; color: #1e293b;">ALL GPE</span>
                <span class="group-count">(<?php echo e($totalDevices); ?>|<span style="color: #16a34a; font-weight: 700;"><?php echo e($totalActive); ?></span>)</span>
            </div>
            <ul class="tree-children">
                <?php $__currentLoopData = $deviceGroups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupName => $groupData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <li class="tree-item">
                        <div class="tree-parent">
                            <i class="fas fa-chevron-right toggle-icon me-2"></i>
                            <input type="checkbox" class="tree-checkbox group-checkbox" data-group="<?php echo e(Str::slug($groupName)); ?>" checked>
                            <?php
                                $icon = 'fa-car';
                                if(str_contains($groupName, 'BUS')) $icon = 'fa-bus';
                                elseif(str_contains($groupName, 'DT')) $icon = 'fa-truck-moving';
                                elseif(str_contains($groupName, 'FT') || str_contains($groupName, 'WT')) $icon = 'fa-truck';
                                elseif(str_contains($groupName, 'HD')) $icon = 'fa-truck-front';
                            ?>
                            <i class="fas <?php echo e($icon); ?> group-icon" style="color: #0f766e;"></i>
                            <span style="font-weight: 700; color: #334155;"><?php echo e($groupName); ?></span>
                            <span class="group-count">(<?php echo e($groupData['total']); ?>|<span style="color: #16a34a; font-weight: 700;"><?php echo e($groupData['active']); ?></span>)</span>
                        </div>
                        <ul class="tree-children">
                            <?php $__currentLoopData = $groupData['devices']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $device): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li class="tree-child" data-location="<?php echo e($device->location ?? ''); ?>" data-series="<?php echo e($device->series ?? ''); ?>">
                                    <input type="checkbox" class="tree-checkbox device-checkbox" value="<?php echo e($device->device_id); ?>" checked data-group="<?php echo e(Str::slug($groupName)); ?>">
                                    <?php
                                        $deviceIcon = 'fa-car';
                                        if(str_contains($device->device_name, 'BUS') || str_contains($device->device_name, '-B-')) $deviceIcon = 'fa-bus';
                                        elseif(str_contains($device->device_name, '-DT-')) $deviceIcon = 'fa-truck-moving';
                                        elseif(str_contains($device->device_name, '-FT-') || str_contains($device->device_name, '-WT-') || str_contains($device->device_name, '-GFTH-')) $deviceIcon = 'fa-truck';
                                        elseif(str_contains($device->device_name, '-HD-')) $deviceIcon = 'fa-truck-front';
                                        
                                        $iconColor = $device->status === 'active' ? '#22c55e' : '#cbd5e1';
                                    ?>
                                    <i class="fas <?php echo e($deviceIcon); ?> group-icon me-2" style="color: <?php echo e($iconColor); ?>; font-size: 11px;"></i>
                                    <span><?php echo e($device->device_name); ?></span>
                                </li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                    </li>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </ul>
        </li>
    </ul>
</div>

<!-- System Active -->
<div class="system-active-box mt-auto">
    <div class="system-active-title">System Active</div>
    <p class="system-active-desc">Monitoring berjalan normal</p>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<div id="exportLoading">
    <div class="spinner-border mb-3" role="status"></div>
    <h5 style="color:#1e293b; font-weight:600;">Memproses Export Data...</h5>
    <p style="color:#64748b;">Mohon tunggu sebentar, file CSV sedang disiapkan di latar belakang.</p>
</div>

<div class="page-title">
    <i class="fas fa-wave-square"></i>
    <h1>Idle Monitor</h1>
</div>
<p class="page-subtitle">Monitoring durasi mesin menyala saat kendaraan berhenti</p>

<div class="top-filter-row">
    <div class="filter-group" style="flex-direction: column; align-items: flex-start; gap: 4px;">
        <label>FILTER TANGGAL</label>
        <div style="display: flex; align-items: center; gap: 8px;">
            <input type="date" id="startDate" class="date-input" value="<?php echo e(date('Y-m-d')); ?>">
            <span style="color: #cbd5e1;">-</span>
            <input type="date" id="endDate" class="date-input" value="<?php echo e(date('Y-m-d')); ?>">
        </div>
        <div id="dateError" style="display:none; font-size: 11px; color: #ef4444; font-weight: 600; padding: 4px 8px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 6px;">
            ⚠️ Tanggal akhir tidak boleh sebelum tanggal awal!
        </div>
    </div>
    
    <div class="filter-group ms-3">
        <select id="durationFilter" class="duration-select">
            <option value="">Semua Durasi</option>
            <option value="lt5">&lt; 5 Menit (0:00-4:59)</option>
            <option value="5to15">5 - 15 Menit (5:00-14:59)</option>
            <option value="15to30">15 - 30 Menit (15:00-29:59)</option>
            <option value="gt30">&gt; 30 Menit (30:00+)</option>
        </select>
    </div>

    <div class="records-badge ms-2"><span id="recordCount">0</span> records</div>

    <div class="ms-auto d-flex gap-2 align-items-center">
        <!-- Duration Color Legend -->
        <div style="display: flex; gap: 10px; margin-right: 15px; font-size: 11px; align-items: center;">
            <span style="font-weight: 600; color: #64748b;">Duration:</span>
            <span style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="width: 12px; height: 12px; background: #10b981; border-radius: 2px;"></span>
                <span>&lt;5m</span>
            </span>
            <span style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="width: 12px; height: 12px; background: #fbbf24; border-radius: 2px;"></span>
                <span>5-15m</span>
            </span>
            <span style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="width: 12px; height: 12px; background: #f97316; border-radius: 2px;"></span>
                <span>15-30m</span>
            </span>
            <span style="display: inline-flex; align-items: center; gap: 4px;">
                <span style="width: 12px; height: 12px; background: #ef4444; border-radius: 2px;"></span>
                <span>&gt;30m</span>
            </span>
        </div>
        
        <button class="btn-export-selected"><i class="fas fa-file-export"></i> Export Selected</button>
        <button class="btn-export-all"><i class="fas fa-file-excel"></i> Export All Excel</button>
    </div>
</div>

<div class="table-container" style="overflow-x: auto;">
    <table id="alarmTable" class="table table-sm table-hover" style="width:100%">
        <thead>
            <tr>
                <th style="width: 2%;"><input type="checkbox" id="selectAllRows"></th>
                <th style="min-width: 80px;">DEVICE ID</th>
                <th style="min-width: 120px;">DEVICE NAME</th>
                <th style="min-width: 80px;">ALARM TYPE</th>
                <th style="min-width: 100px;">ALARM STATUS</th>
                <th style="min-width: 150px;">STARTING TIME</th>
                <th style="min-width: 150px;">STARTING LOCATION</th>
                <th style="min-width: 150px;">ENDING TIME</th>
                <th style="min-width: 150px;">ENDING LOCATION</th>
                <th style="min-width: 250px;">START DETAIL</th>
                <th style="min-width: 250px;">END DETAIL</th>
                <th style="min-width: 80px;">START SPEED</th>
                <th style="min-width: 80px;">END SPEED</th>
                <th style="min-width: 150px;">REPORT TIME</th>
                <th style="min-width: 100px;">DUR (SEC)</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('scripts'); ?>

<script>
$(document).ready(function() {
    // ---- Tree View Interaction ----
    $('.tree-parent').click(function(e) {
        // Don't toggle if clicking on checkbox
        if(e.target.type === 'checkbox') {
            return;
        }
        
        // Toggle the open class
        $(this).toggleClass('open');
        
        // Show/hide the tree-children using jQuery for reliability
        let $li = $(this).closest('li');
        let $treeChildren = $li.find('> .tree-children');
        
        if ($(this).hasClass('open')) {
            $treeChildren.slideDown(200).css('display', 'block');
        } else {
            $treeChildren.slideUp(200);
        }
    });

    // Handle Checkbox Hierarchy
    $('.group-checkbox').change(function() {
        let isChecked = $(this).prop('checked');
        $(this).closest('li').find('.tree-children input[type="checkbox"]').prop('checked', isChecked);
        reloadTable();
    });

    $('.device-checkbox').change(function() {
        let ul = $(this).closest('.tree-children');
        let parentCheckbox = ul.prev('.tree-parent').find('.group-checkbox');
        let allChecked = ul.find('.device-checkbox:checked').length === ul.find('.device-checkbox').length;
        parentCheckbox.prop('checked', allChecked);
        
        // Also update the 'ALL GPE' master checkbox
        let masterChecked = $('.device-checkbox:checked').length === $('.device-checkbox').length;
        $('input[data-group="all"]').prop('checked', masterChecked);
        
        reloadTable();
    });

    $('#selectAllBtn').click(function() {
        $('.tree-checkbox').prop('checked', true);
        reloadTable();
    });

    $('#clearBtn').click(function() {
        $('.tree-checkbox').prop('checked', false);
        reloadTable();
    });

    // ---- Device Search Filter ----
    // Device Search Filter - filters tree by device name in real-time
    $('#deviceSearch').on('input', function() {
        let searchQuery = $(this).val().toLowerCase().trim();
        
        console.log('🔍 [SEARCH START] Query:', searchQuery);
        
        if (searchQuery === '') {
            // Empty search - restore to location/series filter state
            console.log('🔄 [SEARCH CLEAR] Restoring filter state');
            // Remove all search highlights
            $('.tree-child').css({
                'background-color': '',
                'padding': '',
                'border-radius': '',
                'display': ''
            });
            filterTreeBySeriesLocation();
            return;
        }
        
        // Search mode - filter devices by name
        let totalMatching = 0;
        let selectedLocation = $('#locationFilter').val();
        let selectedSeries = $('#seriesFilter').val();
        
        console.log('🎯 [SEARCH FILTERS]', {
            search: searchQuery,
            location: selectedLocation,
            series: selectedSeries
        });
        
        // First pass: filter devices by search + respect location/series filters + add highlight
        let totalDevices = $('.tree-child').length;
        console.log('📊 [SEARCH] Total devices in DOM:', totalDevices);
        
        $('.tree-child').each(function(index) {
            let $device = $(this);
            let deviceName = $device.find('span').text().toLowerCase();
            let deviceLocation = $device.data('location') || '';
            let deviceSeries = $device.data('series') || '';
            
            // Check if device name matches search
            let nameMatches = deviceName.includes(searchQuery);
            
            // Check location filter
            let locationMatches = !selectedLocation || deviceLocation === selectedLocation;
            
            // Check series filter
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
            
            // Debug first 5 devices
            if (index < 5) {
                console.log(`  [Device ${index}]`, {
                    name: deviceName,
                    nameMatches: nameMatches,
                    locationMatches: locationMatches,
                    seriesMatches: seriesMatches
                });
            }
            
            // Show device only if ALL conditions match
            if (nameMatches && locationMatches && seriesMatches) {
                // CRITICAL FIX: Set display first, then styling with !important
                $device.attr('style', 'display: flex !important');
                // Add visual highlight for matching device
                $device.css({
                    'background-color': '#dbeafe',
                    'padding': '8px 12px',
                    'border-radius': '6px',
                    'margin': '2px 0'
                });
                totalMatching++;
                console.log(`  ✅ [MATCH] Device ${index}: ${deviceName}`);
            } else {
                $device.attr('style', 'display: none !important');
            }
        });
        
        console.log(`📊 [SEARCH RESULT] ${totalMatching} matching devices out of ${totalDevices}`);
        
        // CRITICAL: Small delay to ensure DOM is updated before checking visibility
        setTimeout(function() {
            // Second pass: handle sub-group visibility (DT - GPE, HD - GPE, etc)
            let $subGroups = $('.tree-view > .tree-item > .tree-children > .tree-item');
            console.log(`📦 [GROUPS] Total sub-groups found:`, $subGroups.length);
            
            let visibleGroupCount = 0;
            
            $subGroups.each(function(groupIndex) {
                let $groupItem = $(this);
                let $groupParent = $groupItem.find('> .tree-parent');
                let $groupChildren = $groupItem.find('> .tree-children');
                let $devices = $groupChildren.find('> .tree-child');
                let groupName = $groupParent.find('span').first().text();
                
                // Count visible devices in this group - check both :visible and display property
                let visibleInGroup = 0;
                $devices.each(function() {
                    let display = $(this).attr('style');
                    if (display && display.includes('display: flex')) {
                        visibleInGroup++;
                    }
                });
                
                console.log(`  [Group ${groupIndex}] "${groupName}" - ${visibleInGroup} visible devices`);
                
                if (visibleInGroup > 0) {
                    // Show group, expand parent, and FORCE show children container
                    $groupItem.attr('style', 'display: list-item !important');
                    $groupParent.addClass('open');
                    $groupChildren.attr('style', 'display: block !important'); // CRITICAL: Force show children container
                    visibleGroupCount++;
                    console.log(`  ✅ [SHOW GROUP] "${groupName}" with ${visibleInGroup} devices`);
                } else {
                    // Hide empty group
                    $groupItem.attr('style', 'display: none !important');
                    $groupParent.removeClass('open');
                    $groupChildren.attr('style', 'display: none !important');
                    console.log(`  ❌ [HIDE GROUP] "${groupName}"`);
                }
            });
            
            // Third pass: ensure "ALL GPE" parent is visible and expanded
            let $allGpeParent = $('.tree-view > .tree-item');
            let $allGpeChildren = $allGpeParent.find('> .tree-children');
            console.log(`🌲 [ROOT] ALL GPE parent found:`, $allGpeParent.length);
            
            if (totalMatching > 0) {
                $allGpeParent.attr('style', 'display: list-item !important');
                $allGpeParent.find('> .tree-parent').addClass('open');
                $allGpeChildren.attr('style', 'display: block !important'); // CRITICAL: Force show ALL GPE children
                console.log(`✅ [ROOT] ALL GPE visible and expanded`);
            } else {
                // No matches - keep ALL GPE visible but hide children
                $allGpeParent.attr('style', 'display: list-item !important');
                $allGpeParent.find('> .tree-parent').addClass('open');
                $allGpeChildren.attr('style', 'display: none !important');
                console.log(`⚠️ [ROOT] ALL GPE visible but no matches`);
            }
            
            console.log(`✅ [SEARCH COMPLETE]`, {
                matchingDevices: totalMatching,
                visibleGroups: visibleGroupCount
            });
            
            // Show "no results" message if needed
            if (totalMatching === 0) {
                console.log('❌ [NO RESULTS] No devices found matching:', searchQuery);
            }
        }, 10); // 10ms delay to ensure DOM updates
    });
    
    // Clear search when clicking X in search input (HTML5 search type)
    $('#deviceSearch').on('search', function() {
        if ($(this).val() === '') {
            console.log('🔄 Search X clicked - restoring filters');
            filterTreeBySeriesLocation();
        }
    });

    // ---- Date Validation ----
    function validateAndReload() {
        let startVal = $('#startDate').val();
        let endVal   = $('#endDate').val();

        if (startVal && endVal && endVal < startVal) {
            // Show error, set end date border red, block reload
            $('#dateError').fadeIn(200);
            $('#endDate').css({'border-color': '#ef4444', 'box-shadow': '0 0 0 2px rgba(239,68,68,0.2)'});
            return; // do NOT reload
        }

        // Valid — clear error and reload
        $('#dateError').fadeOut(150);
        $('#endDate').css({'border-color': '', 'box-shadow': ''});
        reloadTable();
    }

    // When start date changes, update end date min attribute and revalidate
    $('#startDate').change(function() {
        $('#endDate').attr('min', $(this).val());
        validateAndReload();
    });

    // When end date changes, just validate
    $('#endDate').change(function() {
        validateAndReload();
    });

    // Location & Series filters - update tree visibility + reload table
    $('#locationFilter, #seriesFilter').change(function() {
        console.log('🔥 Location/Series filter CHANGED!', {
            location: $('#locationFilter').val(),
            series: $('#seriesFilter').val(),
            triggerElement: this.id
        });
        filterTreeBySeriesLocation(); // Hide/show tree groups based on filter
        reloadTable();
    });
    
    // Duration filter - only reload table (preserve user device selections)
    $('#durationFilter').change(function() {
        console.log('⏱️ Duration filter CHANGED:', $('#durationFilter').val());
        reloadTable();
    });
    
    console.log('✅ Filter change handlers REGISTERED');
    
    // Filter tree to show only matching devices/groups
    function filterTreeBySeriesLocation() {
        console.log('🎯 filterTreeBySeriesLocation() STARTED');
        
        let selectedLocation = $('#locationFilter').val();
        let selectedSeries = $('#seriesFilter').val();
        
        console.log('Selected filters:', { location: selectedLocation, series: selectedSeries });
        
        // If no filter, show all (but preserve user checkbox selections)
        if (!selectedLocation && !selectedSeries) {
            console.log('✅ No filter - showing all');
            $('.tree-child').each(function() {
                $(this).show().css('display', 'flex');
            });
            $('.tree-item').each(function() {
                $(this).show().css('display', 'list-item');
            });
            $('.tree-parent').each(function() {
                $(this).show().css('display', 'flex');
            });
            // REMOVED: $('.device-checkbox').prop('checked', true);
            // REMOVED: $('.group-checkbox').prop('checked', true);
            // ✅ User checkbox selections are preserved
            return;
        }
        
        let totalMatches = 0;
        
        console.log('⏱️ Starting device filter loop...');
        console.log('Total tree-child elements:', $('.tree-child').length);
        
        // First, hide all devices (but preserve checkbox state)
        $('.tree-child').each(function() {
            $(this).hide().css('display', 'none');
            // REMOVED: $(this).find('.device-checkbox').prop('checked', false);
            // ✅ Checkbox state preserved - only visibility changed
        });
        
        // Show only devices that match filter (but preserve checkbox state)
        $('.tree-child').each(function(index) {
            let $treeChild = $(this);
            let deviceLocation = $treeChild.data('location') || '';
            let deviceSeries = $treeChild.data('series') || '';
            let shouldShow = true;
            
            // Check location match
            if (selectedLocation && deviceLocation !== selectedLocation) {
                shouldShow = false;
            }
            
            // Check series match
            if (selectedSeries && shouldShow) {
                let normalizedSelected = selectedSeries.trim().toUpperCase().replace(/\s+/g, ' ');
                let normalizedDevice = (deviceSeries || '').trim().toUpperCase().replace(/\s+/g, ' ');
                
                if (selectedSeries === 'VOLVO') {
                    // Only show devices that have VOLVO series (exactly 8 devices)
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
                $treeChild.show().css('display', 'flex');
                // REMOVED: $treeChild.find('.device-checkbox').prop('checked', true);
                // ✅ Checkbox state preserved - user's manual selection respected
                totalMatches++;
            }
            
            // Debug first 3
            if (index < 3) {
                console.log(`Device ${index}:`, { location: deviceLocation, series: deviceSeries, shouldShow: shouldShow });
            }
        });
        
        console.log('📊 Device loop complete. Total matches:', totalMatches);
        console.log('⏱️ Starting group hide/show...');
        
        // Hide groups that have NO visible devices
        let hiddenGroups = 0;
        let visibleGroups = 0;
        
        // Find all group items (direct children of main tree-children)
        let $allGroups = $('.tree-view > .tree-item > .tree-children > .tree-item');
        console.log('Total groups found:', $allGroups.length);
        
        $allGroups.each(function(groupIndex) {
            let $groupItem = $(this);
            
            // Count visible devices in this group's children
            let $groupChildren = $groupItem.find('> .tree-children > .tree-child');
            let visibleCount = 0;
            let groupName = $groupItem.find('> .tree-parent span').eq(0).text();
            
            console.log(`Checking group ${groupIndex} "${groupName}" - found ${$groupChildren.length} direct children`);
            
            $groupChildren.each(function(i) {
                if ($(this).is(':visible') || $(this).css('display') !== 'none') {
                    visibleCount++;
                }
            });
            
            console.log(`Group "${groupName}" - visible devices: ${visibleCount}`);
            
            if (visibleCount > 0) {
                // Show group (but preserve checkbox state)
                $groupItem.show().css('display', 'list-item');
                
                // Auto-expand only if was collapsed - but respect user's choice
                let wasCollapsed = !$groupItem.find('> .tree-parent').hasClass('open');
                if (wasCollapsed && visibleCount > 0) {
                    $groupItem.find('> .tree-parent').addClass('open');
                }
                
                $groupItem.find('> .tree-parent').show().css('display', 'flex');
                $groupItem.find('> .tree-children').show().css('display', 'block');
                // REMOVED: $groupItem.find('> .tree-parent .group-checkbox').prop('checked', true);
                // ✅ Group checkbox state preserved - user's manual selection respected
                
                // Update group counter
                let $counter = $groupItem.find('> .tree-parent .group-count');
                $counter.html(`(${visibleCount}|<span style="color: #16a34a; font-weight: 700;">${visibleCount}</span>)`);
                
                console.log(`✅ Group "${groupName}": VISIBLE with ${visibleCount} devices`);
                visibleGroups++;
            } else {
                // Hide group (but preserve checkbox state)
                $groupItem.hide().css('display', 'none');
                $groupItem.find('> .tree-parent').hide().css('display', 'none').removeClass('open');
                $groupItem.find('> .tree-children').hide().css('display', 'none');
                // REMOVED: $groupItem.find('> .tree-parent .group-checkbox').prop('checked', false);
                // ✅ Group checkbox state preserved - user's manual selection respected
                
                console.log(`❌ Group "${groupName}": HIDDEN (0 visible devices)`);
                hiddenGroups++;
            }
        });
        
        // Update master counter
        let $masterCounter = $('.tree-view > .tree-item > .tree-parent .group-count').eq(0);
        $masterCounter.html(`(${totalMatches}|<span style="color: #16a34a; font-weight: 700;">${totalMatches}</span>)`);
        
        // REMOVED: Update master checkbox
        // REMOVED: $('input[data-group="all"]').prop('checked', totalMatches > 0);
        // ✅ Master checkbox state preserved - user's manual selection respected
        
        console.log('✅ Filter complete!', { totalMatches, visibleGroups, hiddenGroups });
    }

    // Set min on load so the calendar picker also blocks invalid dates
    $('#endDate').attr('min', $('#startDate').val());

    // ---- DataTable Initialization ----
    let table = $('#alarmTable').DataTable({
        processing: true,
        serverSide: true,
        bFilter: false,
        lengthChange: true,
        lengthMenu: [50, 100, 200, 500],
        pageLength: 50,
        scrollY: 'calc(100vh - 270px)', // Make table body fill remaining height
        scrollCollapse: true,
        scrollX: true, // MUST be true for header/body sync when table is wide
        autoWidth: false,
        columnDefs: [
            { 
                targets: [9, 10], // START DETAIL and END DETAIL columns
                className: 'text-nowrap',
                createdCell: function(td, cellData, rowData, row, col) {
                    $(td).css({
                        'white-space': 'nowrap',
                        'max-width': 'none',
                        'overflow': 'visible'
                    });
                }
            }
        ],
        language: {
            processing: `
                <div class="d-flex flex-column gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></div>
                        <div class="skeleton-box" style="width: 150px;"></div>
                        <div class="skeleton-box" style="width: 80px;"></div>
                        <div class="skeleton-box" style="width: 100px;"></div>
                        <div class="skeleton-box" style="width: 120px;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></div>
                        <div class="skeleton-box" style="width: 180px;"></div>
                        <div class="skeleton-box" style="width: 80px;"></div>
                        <div class="skeleton-box" style="width: 90px;"></div>
                        <div class="skeleton-box" style="width: 140px;"></div>
                    </div>
                    <div class="d-flex align-items-center gap-3">
                        <div class="skeleton-box" style="width: 20px; height: 20px; border-radius: 4px;"></div>
                        <div class="skeleton-box" style="width: 140px;"></div>
                        <div class="skeleton-box" style="width: 80px;"></div>
                        <div class="skeleton-box" style="width: 110px;"></div>
                        <div class="skeleton-box" style="width: 130px;"></div>
                    </div>
                    <div class="text-center mt-2" style="font-size: 12px; color: #64748b; font-weight: 500;">
                        <i class="fas fa-circle-notch fa-spin me-2"></i> Loading data from fleet...
                    </div>
                </div>
            `
        },
        ajax: {
            url: "<?php echo e(route('frontend.idle-alarm.data')); ?>",
            data: function(d) {
                d.start_date = $('#startDate').val();
                d.end_date = $('#endDate').val();
                d.duration_range = $('#durationFilter').val();
                d.location = $('#locationFilter').val();
                d.series = $('#seriesFilter').val();
                
                // Collect selected device IDs
                let selectedDevices = [];
                $('.device-checkbox:checked').each(function() {
                    selectedDevices.push($(this).val());
                });
                d.device_ids = selectedDevices;
            }
        },
        columns: [
            { 
                data: null,
                orderable: false,
                render: function(data) {
                    return '<input type="checkbox" class="row-checkbox" value="' + data.id + '">';
                }
            },
            { data: 'device_id', name: 'device_id' },
            { data: 'device_name', name: 'device_name' },
            { data: 'alarm_type', name: 'alarm_type', render: function(data) { return data || '-'; } },
            { 
                data: 'alarm_status', 
                name: 'alarm_status', 
                render: function(data, type, row) {
                    if (!data) return '-';
                    
                    // Gunakan duration_seconds_calc (dihitung dari starting_time → ending_time) untuk color coding
                    let totalSeconds = parseInt(row.duration_seconds_calc || row.duration_seconds || 0);
                    let bgColor, textColor;
                    
                    if (totalSeconds <= 0) {
                        bgColor = '#9ca3af'; textColor = '#ffffff';
                    } else if (totalSeconds < 300) {
                        // Hijau: 0–4 menit 59 detik
                        bgColor = '#10b981'; textColor = '#ffffff';
                    } else if (totalSeconds < 900) {
                        // Kuning: 5–14 menit 59 detik
                        bgColor = '#fbbf24'; textColor = '#000000';
                    } else if (totalSeconds < 1800) {
                        // Oranye: 15–29 menit 59 detik
                        bgColor = '#f97316'; textColor = '#ffffff';
                    } else {
                        // Merah: 30 menit ke atas
                        bgColor = '#ef4444'; textColor = '#ffffff';
                    }
                    
                    return `<span style="display: inline-block; padding: 6px 12px; background: ${bgColor}; color: ${textColor}; border-radius: 6px; font-weight: 600; font-size: 11px; text-transform: uppercase;">${data}</span>`;
                } 
            },
            { 
                data: 'starting_time', 
                name: 'starting_time', 
                render: function(data) { 
                    return data || '-'; 
                } 
            },
            { 
                data: 'starting_location', 
                name: 'starting_location', 
                render: function(data) { 
                    if(!data || data === '-') return '-'; 
                    let parts = data.split(',');
                    if (parts.length === 2) {
                        let long = parts[0].trim();
                        let lat = parts[1].trim();
                        return `<a href="https://www.google.com/maps?q=${lat},${long}" target="_blank" style="color: #1963f2; text-decoration: underline;" title="View on Google Maps"><i class="fas fa-map-marker-alt me-1"></i>${data}</a>`;
                    }
                    return data; 
                } 
            },
            { 
                data: 'ending_time', 
                name: 'ending_time', 
                render: function(data) { 
                    return data || '-'; 
                } 
            },
            { 
                data: 'ending_location', 
                name: 'ending_location', 
                render: function(data) { 
                    if(!data || data === '-') return '-'; 
                    let parts = data.split(',');
                    if (parts.length === 2) {
                        let long = parts[0].trim();
                        let lat = parts[1].trim();
                        return `<a href="https://www.google.com/maps?q=${lat},${long}" target="_blank" style="color: #1963f2; text-decoration: underline;" title="View on Google Maps"><i class="fas fa-map-marker-alt me-1"></i>${data}</a>`;
                    }
                    return data; 
                } 
            },
            { data: 'start_detail', name: 'start_detail', render: function(data) { return data || '-'; } },
            { data: 'end_detail', name: 'end_detail', render: function(data) { return data || '-'; } },
            { data: 'start_speed', name: 'start_speed' },
            { data: 'end_speed', name: 'end_speed' },
            { 
                data: 'report_time', 
                name: 'report_time', 
                render: function(data) { 
                    return data || '-'; 
                } 
            },
            { 
                data: 'duration_formatted', 
                name: 'duration_seconds', 
                orderable: true, 
                searchable: false,
                render: function(data, type, row) {
                    if (!data || data === '-') return '<span style="color:#9ca3af;">-</span>';
                    
                    // Warna badge berdasarkan durasi
                    let secs = parseInt(row.duration_seconds_calc || row.duration_seconds || 0);
                    let color = '#9ca3af';
                    if (secs > 0 && secs < 300)        color = '#10b981';
                    else if (secs < 900)               color = '#d97706';
                    else if (secs < 1800)              color = '#f97316';
                    else if (secs >= 1800)             color = '#ef4444';
                    
                    return `<span style="font-weight:600; color:${color};">${data}</span>`;
                }
            }
        ],
        order: [[5, 'desc']], // Sort by STARTING TIME (column 5) descending - newest first
        drawCallback: function(settings) {
            if(settings.json && settings.json.recordsTotal !== undefined) {
                $('#recordCount').text(settings.json.recordsTotal);
            }
            
            // ✅ REMOVED: Duration filter sidebar sync logic
            // Duration filter should ONLY affect table data display
            // Sidebar device visibility is controlled by Location/Series filters only
            // User checkbox selections are always preserved (TASK 7 fix)
            
            console.log('✅ Table reloaded - record count:', settings.json.recordsTotal);
        }
    });

    // Handle AJAX errors
    $('#alarmTable').on('xhr.dt', function(e, settings, json, xhr) {
        if(xhr && xhr.status !== 200) {
            console.error('DataTable AJAX error:', xhr.status, xhr.responseText);
        }
    });

    function reloadTable() {
        table.ajax.reload();
    }

    function triggerExport(data) {
        $('#exportLoading').css('display', 'flex'); // Show overlay

        $.ajax({
            url: "<?php echo e(route('frontend.idle-alarm.export')); ?>",
            type: 'POST',
            data: Object.assign({}, data, { _token: $('meta[name="csrf-token"]').attr('content') }),
            success: function(res) {
                if (res.use_queue) {
                    // Polling
                    let pollInterval = setInterval(function() {
                        $.get("<?php echo e(url('/idle-alarm/export-status')); ?>/" + res.job_id, function(statusRes) {
                            if (statusRes.status === 'completed') {
                                clearInterval(pollInterval);
                                $('#exportLoading').hide();
                                window.location.href = statusRes.download_url;
                            } else if (statusRes.status === 'failed') {
                                clearInterval(pollInterval);
                                $('#exportLoading').hide();
                                alert('Background export failed. Silakan coba lagi.');
                            }
                        });
                    }, 3000);
                } else {
                    // Direct download
                    $('#exportLoading').hide();
                    let form = $('<form>', { action: "<?php echo e(route('frontend.idle-alarm.export')); ?>", method: 'POST' })
                        .append($('<input>', { name: '_token', value: $('meta[name="csrf-token"]').attr('content'), type: 'hidden' }));
                    
                    $.each(data, function(key, value) {
                        if (Array.isArray(value)) {
                            $.each(value, function(i, v) {
                                form.append($('<input>', { name: key + '[]', value: v, type: 'hidden' }));
                            });
                        } else if (value !== null && value !== undefined) {
                            form.append($('<input>', { name: key, value: value, type: 'hidden' }));
                        }
                    });
                    
                    $('body').append(form);
                    form.submit();
                    form.remove();
                }
            },
            error: function(xhr) {
                $('#exportLoading').hide();
                alert('Terjadi kesalahan saat memulai export.');
            }
        });
    }

    // Export All Excel
    $('.btn-export-all').click(function() {
        let startDate = $('#startDate').val();
        let endDate = $('#endDate').val();
        let duration_range = $('#durationFilter').val();
        let location = $('#locationFilter').val();
        let series = $('#seriesFilter').val();
        
        let selectedDevices = [];
        $('.device-checkbox:checked').each(function() {
            selectedDevices.push($(this).val());
        });
        
        triggerExport({
            start_date: startDate,
            end_date: endDate,
            duration_range: duration_range,
            location: location,
            series: series,
            device_ids: selectedDevices
        });
    });

    // Export Selected
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
            selected_ids: selectedRows
        });
    });
    
    // Select All Rows in Table
    $('#selectAllRows').change(function() {
        $('.row-checkbox').prop('checked', $(this).prop('checked'));
    });
});
</script>

<script>
// ---- Custom Column Resizer ----
// Adds draggable handles to each th; on drag, column width (th + all td) updates live
(function initColResize() {
    function addResizers() {
        const table = document.getElementById('alarmTable');
        if (!table) return;

        const ths = table.querySelectorAll('thead th');
        ths.forEach(function(th, colIdx) {
            // Remove old resizer if any
            const old = th.querySelector('.col-resizer');
            if (old) old.remove();

            const resizer = document.createElement('div');
            resizer.className = 'col-resizer';
            th.style.position = 'relative';
            th.appendChild(resizer);

            let startX, startW;

            resizer.addEventListener('mousedown', function(e) {
                e.stopPropagation();
                e.preventDefault();
                startX = e.pageX;
                startW = th.offsetWidth;
                resizer.classList.add('resizing');
                document.body.style.cursor = 'col-resize';
                document.body.style.userSelect = 'none';

                function onMouseMove(ev) {
                    const delta = ev.pageX - startX;
                    const newW = Math.max(40, startW + delta);
                    const pct = (newW / table.offsetWidth * 100).toFixed(2) + '%';

                    // Update th
                    th.style.width = pct;

                    // Update every td in this column
                    const rows = table.querySelectorAll('tbody tr');
                    rows.forEach(function(row) {
                        const tds = row.querySelectorAll('td');
                        if (tds[colIdx]) {
                            tds[colIdx].style.width = pct;
                        }
                    });
                }

                function onMouseUp() {
                    resizer.classList.remove('resizing');
                    document.body.style.cursor = '';
                    document.body.style.userSelect = '';
                    document.removeEventListener('mousemove', onMouseMove);
                    document.removeEventListener('mouseup', onMouseUp);
                }

                document.addEventListener('mousemove', onMouseMove);
                document.addEventListener('mouseup', onMouseUp);
            });
        });
    }

    // Run after DataTable draws (tbody is re-rendered each draw)
    $('#alarmTable').on('draw.dt', function() {
        addResizers();
    });

    // Also run once on initial load
    setTimeout(addResizers, 300);
})();
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('frontend.layouts.app', \Illuminate\Support\Arr::except(get_defined_vars(), ['__data', '__path']))->render(); ?><?php /**PATH G:\project\vss\idle-monitor\resources\views/frontend/idle-alarm/index.blade.php ENDPATH**/ ?>