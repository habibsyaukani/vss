@extends('frontend.layouts.app')

@section('title', 'Fleet Dashboard')

@section('styles')
<style>
    /* Dashboard specific styles */
    .dashboard-container {
        padding: 10px 15px;
        background: #f8f9fc;
        min-height: 100%;
        display: flex;
        flex-direction: column;
    }
    
    /* Stat Cards (Style like reference image) */
    .stat-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        margin-bottom: 12px;
        display: flex;
        flex-direction: column;
    }
    
    .stat-card .stat-label {
        font-size: 12px;
        font-weight: 800;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 12px;
    }
    .stat-card .stat-value {
        font-size: 38px;
        font-weight: 800;
        color: #4285f4; /* Reference image uses blue for all numbers */
        margin-bottom: 8px;
        line-height: 1;
    }
    .stat-card .stat-unit {
        font-size: 14px;
        font-weight: 600;
        color: #4285f4;
        margin-left: 2px;
    }
    .stat-card .stat-desc {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .stat-card .stat-desc i {
        color: #34a853;
    }
    
    .widget-card {
        background: white;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        border: 1px solid #f1f5f9;
        height: 100%;
        margin-bottom: 12px;
        display: flex;
        flex-direction: column;
    }
    .widget-card .widget-header {
        margin-bottom: 16px;
        flex-shrink: 0;
    }
    .widget-card .widget-title {
        font-size: 13px;
        font-weight: 800;
        color: #1e293b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    /* Top Units List */
    .top-units-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .top-unit-item {
        display: flex;
        align-items: center;
        padding: 12px;
        margin-bottom: 8px;
        background: #f8fafc;
        border-radius: 8px;
        transition: all 0.2s;
    }
    .top-unit-item:hover {
        background: #f1f5f9;
        transform: translateX(4px);
    }
    .top-unit-rank {
        width: 28px;
        height: 28px;
        background: #4285f4;
        color: white;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        margin-right: 12px;
        flex-shrink: 0;
    }
    .top-unit-rank.red { background: #ea4335; }
    .top-unit-name {
        flex-grow: 1;
        font-size: 13px;
        font-weight: 600;
        color: #334155;
    }
    .top-unit-count {
        font-size: 13px;
        font-weight: 700;
        color: #4285f4;
    }
    .top-unit-count.red { color: #ea4335; }
    .top-unit-label {
        font-size: 11px;
        color: #94a3b8;
        margin-left: 4px;
    }
    
    /* Chart Container */
    .chart-container {
        position: relative;
        height: 190px;
        width: 100%;
        flex-grow: 1;
        min-height: 180px;
    }
    .chart-container.trend-chart {
        height: auto;
        min-height: 200px;
    }
    .chart-container canvas {
        max-height: 100% !important;
        width: 100% !important;
    }
    
    /* Flex Row for full height */
    .row.flex-fill {
        flex-grow: 1;
    }

    /* ===== SIDEBAR TOP LISTS ===== */
    .sidebar-top-section {
        padding: 16px;
        border-bottom: 1px solid #eaedf2;
    }
    .sidebar-top-title {
        font-size: 10px;
        font-weight: 700;
        color: #8c98a4;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .sidebar-top-title i { font-size: 11px; }
    .sidebar-top-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 8px;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: background 0.15s;
    }
    .sidebar-top-item:hover { background: #f1f5f9; }
    .sidebar-top-rank {
        width: 20px;
        height: 20px;
        border-radius: 5px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }
    .rank-blue { background: #4285f4; }
    .rank-red  { background: #ea4335; }
    .rank-gold { background: #f59e0b; }
    .rank-silver { background: #94a3b8; }
    .rank-bronze { background: #b45309; }
    .sidebar-top-name {
        flex-grow: 1;
        font-size: 11px;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 110px;
    }
    .sidebar-top-value {
        font-size: 11px;
        font-weight: 700;
        white-space: nowrap;
    }
    .sidebar-top-value.speed { color: #ea4335; }
    .sidebar-top-value.idle  { color: #4285f4; }
</style>
@endsection

@section('sidebar')
<!-- Top Speed Hari Ini -->
<div class="sidebar-top-section">
    <div class="sidebar-top-title">
        <i class="fas fa-tachometer-alt" style="color:#ea4335;"></i>
        Top Speed Hari Ini
    </div>
    @php $rankColors = ['rank-gold','rank-silver','rank-bronze','rank-blue','rank-blue']; @endphp
    @forelse($topSpeedUnits as $i => $unit)
        <div class="sidebar-top-item">
            <div class="sidebar-top-rank {{ $rankColors[$i] ?? 'rank-blue' }}">{{ $i + 1 }}</div>
            <div class="sidebar-top-name" title="{{ $unit->device_name }}">{{ $unit->device_name }}</div>
            <div class="sidebar-top-value speed">{{ $unit->max_speed }}<small style="font-weight:500;color:#94a3b8;"> km/h</small></div>
        </div>
    @empty
        <div style="font-size:12px;color:#94a3b8;text-align:center;padding:8px 0;">Belum ada data hari ini</div>
    @endforelse
</div>

<!-- Top Idle Hari Ini -->
<div class="sidebar-top-section">
    <div class="sidebar-top-title">
        <i class="fas fa-clock" style="color:#4285f4;"></i>
        Top Idle Hari Ini
    </div>
    @forelse($topIdleUnits as $i => $unit)
        <div class="sidebar-top-item">
            <div class="sidebar-top-rank {{ $rankColors[$i] ?? 'rank-blue' }}">{{ $i + 1 }}</div>
            <div class="sidebar-top-name" title="{{ $unit->device_name }}">{{ $unit->device_name }}</div>
            <div class="sidebar-top-value idle">{{ $unit->event_count }}<small style="font-weight:500;color:#94a3b8;"> alarm</small></div>
        </div>
    @empty
        <div style="font-size:12px;color:#94a3b8;text-align:center;padding:8px 0;">Belum ada data hari ini</div>
    @endforelse
</div>
@endsection

@section('content')
<div class="dashboard-container">
    
    <!-- Row 1: Stat Cards -->
    <div class="row">
        <!-- Idle Alarm Hari Ini -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">TOTAL IDLE ALARM</div>
                <div class="stat-value">{{ $stats['today_idle_count'] }}</div>
                <div class="stat-desc"><i class="fas fa-arrow-up"></i> Hari Ini ({{ date('d M Y') }})</div>
            </div>
        </div>
        
        <!-- Max Speed -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">MAX SPEED HARI INI</div>
                <div class="stat-value">
                    {{ $stats['max_speed'] }}
                </div>
                <div class="stat-desc"><i class="fas fa-arrow-up"></i> km/h (Kecepatan Tertinggi)</div>
            </div>
        </div>
        
        <!-- Avg Speed -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-label">AVG SPEED HARI INI</div>
                <div class="stat-value">
                    {{ $stats['avg_speed'] }}
                </div>
                <div class="stat-desc"><i class="fas fa-arrow-up"></i> km/h (Rata-rata Fleet)</div>
            </div>
        </div>
    </div>
    
    <!-- Row 2: Charts -->
    <div class="row">
        <!-- Idle Alarm per Fleet (Donut Chart) -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">DISTRIBUSI IDLE ALARM (PER FLEET)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="idlePerFleetChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Speed per Fleet (Donut Chart - Real Data) -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">DISTRIBUSI MAX SPEED (PER FLEET)</h3>
                </div>
                <div class="chart-container">
                    <canvas id="speedPerFleetChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Row 3: Trend Charts -->
    <div class="row flex-fill">
        <!-- Trend Idle Alarm (7 Hari) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">PERBANDINGAN IDLE ALARM (7 HARI TERAKHIR)</h3>
                </div>
                <div class="chart-container trend-chart">
                    <canvas id="trendIdleChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Trend Max Speed (7 Hari) -->
        <div class="col-lg-6 d-flex flex-column">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">PERBANDINGAN MAX SPEED (7 HARI TERAKHIR)</h3>
                </div>
                <div class="chart-container trend-chart">
                    <canvas id="trendSpeedChart"></canvas>
                </div>
            </div>
        </div>
    </div>
    
</div>
@endsection

@section('scripts')
<script>
// Plugin for perfectly centered donut text
const centerTextPlugin = {
    id: 'centerText',
    beforeDraw: function(chart) {
        if (chart.config.options.plugins.centerText) {
            const ctx = chart.ctx;
            const centerConfig = chart.config.options.plugins.centerText;
            const val = centerConfig.value;
            const lbl = centerConfig.label;
            const colorVal = centerConfig.colorValue || '#1e293b';
            
            // Get center of pie
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || meta.data.length === 0) return;
            const centerX = meta.data[0].x;
            const centerY = meta.data[0].y;
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            // Draw value
            ctx.font = 'bold 24px sans-serif';
            ctx.fillStyle = colorVal;
            ctx.fillText(val, centerX, centerY - 8);
            
            // Draw label
            ctx.font = '600 10px sans-serif';
            ctx.fillStyle = '#94a3b8';
            ctx.fillText(lbl, centerX, centerY + 12);
            
            ctx.restore();
        }
    }
};
Chart.register(centerTextPlugin);

const colors = {
    blue: '#4285f4', red: '#ea4335', green: '#34a853',
    yellow: '#fbbc04', orange: '#ff6d00', purple: '#9c27b0',
    teal: '#00bcd4', pink: '#e91e63',
};
const chartColors = [colors.blue, colors.red, colors.green, colors.yellow, colors.orange, colors.purple, colors.teal, colors.pink];
const speedColors = [colors.red, '#ef5350', '#e53935', '#c62828', '#b71c1c', '#ff8a80'];

// 1. Idle Alarm per Fleet (Donut Chart)
const idlePerFleetCtx = document.getElementById('idlePerFleetChart').getContext('2d');
new Chart(idlePerFleetCtx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode($idlePerFleet['labels']) !!},
        datasets: [{
            data: {!! json_encode($idlePerFleet['counts']) !!},
            backgroundColor: chartColors,
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            centerText: {
                value: '{{ array_sum($idlePerFleet["counts"]) }}',
                label: 'TOTAL'
            },
            legend: {
                display: true,
                position: 'bottom',
                labels: { boxWidth: 10, boxHeight: 10, padding: 15, font: { size: 11, weight: 600 }, usePointStyle: true, pointStyle: 'circle' }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)', padding: 12,
                titleFont: { size: 13, weight: 600 }, bodyFont: { size: 12 }, cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        const value = context.parsed || 0;
                        const total = context.dataset.data.reduce((a, b) => a + b, 0);
                        return ` ${context.label}: ${value} (${((value/total)*100).toFixed(1)}%)`;
                    }
                }
            }
        }
    }
});

// 2. Speed per Fleet (Donut Chart - Real Data)
const speedPerFleetCtx = document.getElementById('speedPerFleetChart').getContext('2d');
const speedLabels = {!! json_encode($speedPerFleet['labels']) !!};
const speedCounts = {!! json_encode($speedPerFleet['counts']) !!};

new Chart(speedPerFleetCtx, {
    type: 'doughnut',
    data: {
        labels: speedLabels.length > 0 ? speedLabels : ['Belum ada data'],
        datasets: [{
            data: speedCounts.length > 0 ? speedCounts : [1],
            backgroundColor: speedCounts.length > 0 ? speedColors : ['#f1f5f9'],
            borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            centerText: {
                value: speedLabels.length > 0 ? '{{ count($speedPerFleet["counts"]) > 0 ? max($speedPerFleet["counts"]) : 0 }}' : '',
                label: speedLabels.length > 0 ? 'KM/H MAX' : 'Belum ada data',
                colorValue: '#ea4335'
            },
            legend: {
                display: speedLabels.length > 0,
                position: 'bottom',
                labels: { boxWidth: 10, boxHeight: 10, padding: 15, font: { size: 11, weight: 600 }, usePointStyle: true, pointStyle: 'circle' }
            },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)', padding: 12,
                titleFont: { size: 13, weight: 600 }, bodyFont: { size: 12 }, cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        if (speedLabels.length === 0) return '';
                        return ` ${context.label}: Max ${context.parsed} km/h`;
                    }
                }
            }
        }
    }
});

// 3. Trend Idle Alarm (7 Hari) - Bar Chart
const trendIdleCtx = document.getElementById('trendIdleChart').getContext('2d');
new Chart(trendIdleCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($idlePerDay['days']) !!},
        datasets: [{
            label: 'Idle Alarm',
            data: {!! json_encode($idlePerDay['counts']) !!},
            backgroundColor: colors.blue,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)', padding: 12,
                titleFont: { size: 13, weight: 600 }, bodyFont: { size: 12 }, cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        return ` ${context.dataset.label}: ${context.parsed.y} alarms`;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: 600 }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: { font: { size: 11, weight: 600 }, color: '#64748b', stepSize: 200 }
            }
        }
    }
});

// 4. Trend Max Speed (7 Hari) - Bar Chart
const trendSpeedCtx = document.getElementById('trendSpeedChart').getContext('2d');
new Chart(trendSpeedCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($speedPerDay['days']) !!},
        datasets: [{
            label: 'Max Speed',
            data: {!! json_encode($speedPerDay['counts']) !!},
            backgroundColor: colors.red,
            borderRadius: 6,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(0,0,0,0.8)', padding: 12,
                titleFont: { size: 13, weight: 600 }, bodyFont: { size: 12 }, cornerRadius: 6,
                callbacks: {
                    label: function(context) {
                        return ` ${context.dataset.label}: ${context.parsed.y} km/h`;
                    }
                }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: 600 }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: { font: { size: 11, weight: 600 }, color: '#64748b' }
            }
        }
    }
});
</script>
@endsection
