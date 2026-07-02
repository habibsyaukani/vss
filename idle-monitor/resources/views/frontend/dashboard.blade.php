@extends('frontend.layouts.app')

@section('title', 'Fleet Dashboard')

@section('styles')
<style>
    /* Dashboard specific styles */
    .dashboard-container {
        padding: 5px;
        min-height: 100%;
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    
    /* Stat Cards - Top Row */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        gap: 20px;
        height: 100%;
    }
    .stat-icon-wrapper {
        width: 64px;
        height: 64px;
        border-radius: 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        flex-shrink: 0;
    }
    .icon-blue { background: #eff6ff; color: #3b82f6; }
    .icon-green { background: #f0fdf4; color: #22c55e; }
    .icon-orange { background: #fff7ed; color: #f97316; }
    
    .stat-content {
        flex-grow: 1;
    }
    .stat-label {
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }
    .stat-value-row {
        display: flex;
        align-items: baseline;
        gap: 4px;
        margin-bottom: 5px;
    }
    .stat-value {
        font-size: 36px;
        font-weight: 800;
        color: #3b82f6;
        line-height: 1;
    }
    .stat-unit {
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
    }
    .stat-desc {
        font-size: 11px;
        font-weight: 500;
        color: #94a3b8;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .stat-desc .trend-up { color: #22c55e; font-weight: 600; }
    
    /* Widget Cards */
    .widget-card {
        background: white;
        border-radius: 12px;
        padding: 20px 24px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
        border: 1px solid #e2e8f0;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .widget-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .widget-title {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        margin: 0;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .widget-action {
        font-size: 11px;
        color: #3b82f6;
        background: #eff6ff;
        padding: 4px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
    }
    
    /* Charts */
    .donut-container {
        display: flex;
        align-items: center;
        gap: 30px;
        height: 200px;
    }
    .donut-chart-box {
        position: relative;
        width: 180px;
        height: 180px;
        flex-shrink: 0;
    }
    .donut-legend {
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }
    .legend-left {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .legend-color {
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }
    .legend-value {
        text-align: right;
    }
    .legend-pct {
        color: #94a3b8;
        font-weight: 500;
        margin-left: 10px;
        width: 45px;
        display: inline-block;
        text-align: right;
    }

    .trend-container {
        position: relative;
        height: 220px;
        width: 100%;
    }

    /* Bottom Summary Row */
    .summary-row {
        display: flex;
        gap: 15px;
    }
    .summary-card {
        background: white;
        border-radius: 12px;
        padding: 15px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        border: 1px solid #e2e8f0;
        flex: 1;
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .summary-icon {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .summary-icon.blue { background: #eff6ff; color: #3b82f6; }
    .summary-icon.green { background: #f0fdf4; color: #22c55e; }
    .summary-icon.orange { background: #fff7ed; color: #f97316; }
    .summary-icon.purple { background: #faf5ff; color: #a855f7; }
    .summary-icon.teal { background: #f0fdfa; color: #14b8a6; }
    
    .summary-text h6 {
        font-size: 10px;
        font-weight: 700;
        color: #64748b;
        text-transform: uppercase;
        margin: 0 0 2px 0;
    }
    .summary-text .val {
        font-size: 22px;
        font-weight: 800;
        color: #1e293b;
        line-height: 1.2;
    }
    .summary-text .sub {
        font-size: 11px;
        color: #94a3b8;
        font-weight: 500;
    }
    .summary-text .sub.green { color: #22c55e; font-weight: 600; }
    .summary-text .sub.orange { color: #f97316; font-weight: 600; }

    /* ===== SIDEBAR STYLES ===== */
    .sidebar-section {
        padding: 20px 15px;
        border-bottom: 1px solid rgba(255,255,255,0.05);
    }
    .sidebar-header {
        font-size: 10px;
        font-weight: 700;
        color: #cbd5e1;
        text-transform: uppercase;
        letter-spacing: 0.8px;
        margin-bottom: 15px;
    }
    .sidebar-title {
        font-size: 11px;
        font-weight: 700;
        color: #cbd5e1;
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 12px;
    }
    .sidebar-title i { font-size: 13px; margin-right: 6px; }
    .sidebar-title a { color: #3b82f6; font-size: 10px; text-decoration: none; font-weight: 600; }
    
    .sidebar-list {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .sidebar-item {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .sidebar-rank {
        width: 24px;
        height: 24px;
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 700;
        color: white;
        flex-shrink: 0;
    }
    .rank-gold { background: #f59e0b; }
    .rank-silver { background: #94a3b8; }
    .rank-bronze { background: #b45309; }
    .rank-blue { background: #3b82f6; }
    
    .sidebar-name {
        flex-grow: 1;
        font-size: 12px;
        font-weight: 600;
        color: #f8fafc;
    }
    .sidebar-value {
        font-size: 12px;
        font-weight: 700;
    }
    .sidebar-value.red { color: #ef4444; }
    .sidebar-value.blue { color: #60a5fa; }
    .sidebar-value span {
        font-size: 10px;
        color: #94a3b8;
        font-weight: 500;
        margin-left: 2px;
    }
</style>
@endsection

@section('sidebar')
<div class="sidebar-section">
    <div class="sidebar-header">RINGKASAN HARI INI</div>
    
    <!-- Top Speed -->
    <div class="sidebar-title">
        <div><i class="fas fa-trophy text-orange-400" style="color:#f97316"></i> TOP SPEED HARI INI</div>
        <a href="#">Lihat semua</a>
    </div>
    <div class="sidebar-list mb-4">
        @php $rankColors = ['rank-gold','rank-silver','rank-bronze','rank-blue','rank-blue']; @endphp
        @forelse($topSpeedUnits as $i => $unit)
            <div class="sidebar-item">
                <div class="sidebar-rank {{ $rankColors[$i] ?? 'rank-blue' }}">{{ $i + 1 }}</div>
                <div class="sidebar-name" title="{{ $unit->device_name }}">{{ $unit->device_name }}</div>
                <div class="sidebar-value red">{{ $unit->max_speed }}<span>km/h</span></div>
            </div>
        @empty
            <div style="font-size:12px;color:#94a3b8;text-align:center;">Belum ada data</div>
        @endforelse
    </div>

    <!-- Top Idle -->
    <div class="sidebar-title">
        <div><i class="far fa-clock text-blue-400" style="color:#60a5fa"></i> TOP IDLE HARI INI</div>
        <a href="#">Lihat semua</a>
    </div>
    <div class="sidebar-list">
        @forelse($topIdleUnits as $i => $unit)
            <div class="sidebar-item">
                <div class="sidebar-rank {{ $rankColors[$i] ?? 'rank-blue' }}">{{ $i + 1 }}</div>
                <div class="sidebar-name" title="{{ $unit->device_name }}">{{ $unit->device_name }}</div>
                <div class="sidebar-value blue">{{ $unit->event_count }}<span>alarm</span></div>
            </div>
        @empty
            <div style="font-size:12px;color:#94a3b8;text-align:center;">Belum ada data</div>
        @endforelse
    </div>
</div>
@endsection

@section('content')
<div class="dashboard-container">
    
    <!-- Row 1: Stat Cards -->
    <div class="row g-3">
        <!-- Idle Alarm -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-blue">
                    <i class="far fa-bell"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">TOTAL IDLE ALARM</div>
                    <div class="stat-value-row">
                        <div class="stat-value">{{ $stats['today_idle_count'] }}</div>
                    </div>
                    <div class="stat-desc">
                        <span class="trend-up"><i class="fas fa-arrow-up"></i> 12%</span> dari {{ date('d M Y') }}
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Max Speed -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-green">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">MAX SPEED HARI INI</div>
                    <div class="stat-value-row">
                        <div class="stat-value" style="color:#22c55e;">{{ $stats['max_speed'] }}</div>
                        <div class="stat-unit">km/h</div>
                    </div>
                    <div class="stat-desc">
                        <span class="trend-up"><i class="fas fa-arrow-up"></i></span> Kecepatan Tertinggi
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Avg Speed -->
        <div class="col-lg-4">
            <div class="stat-card">
                <div class="stat-icon-wrapper icon-orange">
                    <i class="fas fa-tachometer-alt"></i>
                </div>
                <div class="stat-content">
                    <div class="stat-label">AVG SPEED HARI INI</div>
                    <div class="stat-value-row">
                        <div class="stat-value" style="color:#f97316;">{{ $stats['avg_speed'] }}</div>
                        <div class="stat-unit">km/h</div>
                    </div>
                    <div class="stat-desc">
                        <span class="trend-up"><i class="fas fa-arrow-up"></i></span> Rata-rata Fleet
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Row 2: Charts -->
    <div class="row g-3">
        <!-- Distribusi Idle -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">DISTRIBUSI IDLE ALARM (PER FLEET)</h3>
                </div>
                <div class="donut-container">
                    <div class="donut-chart-box">
                        <canvas id="idleDonutChart"></canvas>
                    </div>
                    <div class="donut-legend" id="idleLegend">
                        <!-- Legend generated via JS -->
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Distribusi Speed -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">DISTRIBUSI MAX SPEED (PER FLEET)</h3>
                </div>
                <div class="donut-container">
                    <div class="donut-chart-box">
                        <canvas id="speedDonutChart"></canvas>
                    </div>
                    <div class="donut-legend" id="speedLegend">
                        <!-- Legend generated via JS -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Row 3: Trend Charts -->
    <div class="row g-3">
        <!-- Trend Idle -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">PERBANDINGAN IDLE ALARM (7 HARI TERAKHIR)</h3>
                </div>
                <div class="trend-container">
                    <canvas id="idleBarChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Trend Speed -->
        <div class="col-lg-6">
            <div class="widget-card">
                <div class="widget-header">
                    <h3 class="widget-title">PERBANDINGAN MAX SPEED (7 HARI TERAKHIR)</h3>
                </div>
                <div class="trend-container">
                    <canvas id="speedBarChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Row 4: Summary Stats (Bottom) -->
    <div class="summary-row mt-1">
        <div class="summary-card">
            <div class="summary-icon blue"><i class="fas fa-truck-moving"></i></div>
            <div class="summary-text">
                <h6>TOTAL FLEET</h6>
                <div class="val">128</div>
                <div class="sub">Unit Aktif</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon green"><i class="far fa-check-circle"></i></div>
            <div class="summary-text">
                <h6>FLEET AKTIF</h6>
                <div class="val">116</div>
                <div class="sub green">90.6%</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon orange"><i class="fas fa-exclamation-triangle"></i></div>
            <div class="summary-text">
                <h6>FLEET IDLE</h6>
                <div class="val">12</div>
                <div class="sub orange">9.4%</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon purple"><i class="fas fa-tachometer-alt"></i></div>
            <div class="summary-text">
                <h6>RATA-RATA IDLE</h6>
                <div class="val">32<span style="font-size:14px;color:#94a3b8">m</span></div>
                <div class="sub">Durasi Idle</div>
            </div>
        </div>
        <div class="summary-card">
            <div class="summary-icon teal"><i class="fas fa-tachometer-alt"></i></div>
            <div class="summary-text">
                <h6>RATA-RATA SPEED</h6>
                <div class="val">16.4<span style="font-size:14px;color:#94a3b8">km/h</span></div>
                <div class="sub">Seluruh Fleet</div>
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
            
            const meta = chart.getDatasetMeta(0);
            if (!meta || !meta.data || meta.data.length === 0) return;
            const centerX = meta.data[0].x;
            const centerY = meta.data[0].y;
            
            ctx.save();
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            
            ctx.font = '800 28px "Inter", sans-serif';
            ctx.fillStyle = colorVal;
            ctx.fillText(val, centerX, centerY - 5);
            
            ctx.font = '600 10px "Inter", sans-serif';
            ctx.fillStyle = '#94a3b8';
            ctx.fillText(lbl, centerX, centerY + 18);
            
            ctx.restore();
        }
    }
};
Chart.register(centerTextPlugin);

const chartColors = ['#3b82f6', '#f59e0b', '#22c55e', '#a855f7', '#64748b', '#ef4444'];
const speedChartColors = ['#ef4444']; // Main donut color for max speed

// Function to generate custom HTML legend
function generateLegend(labels, data, colors, containerId, isPercent = true, valueSuffix = '') {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    let total = data.reduce((a, b) => a + b, 0);
    if (total === 0) total = 1;

    let html = '';
    for (let i = 0; i < Math.min(labels.length, 6); i++) {
        let val = data[i];
        let pct = ((val / total) * 100).toFixed(1);
        let color = colors[i % colors.length];

        html += `
            <div class="legend-item">
                <div class="legend-left">
                    <div class="legend-color" style="background-color: ${color}"></div>
                    <span>${labels[i]}</span>
                </div>
                <div class="legend-value">
                    ${val} ${valueSuffix}
                    ${isPercent ? `<span class="legend-pct">(${pct}%)</span>` : ''}
                </div>
            </div>
        `;
    }
    container.innerHTML = html;
}

// 1. Idle Alarm per Fleet (Donut Chart)
const idleLabels = {!! json_encode($idlePerFleet['labels']) !!};
const idleCounts = {!! json_encode($idlePerFleet['counts']) !!};
const idleCtx = document.getElementById('idleDonutChart').getContext('2d');
new Chart(idleCtx, {
    type: 'doughnut',
    data: {
        labels: idleLabels,
        datasets: [{
            data: idleCounts,
            backgroundColor: chartColors,
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: {
            legend: { display: false },
            centerText: {
                value: '{{ array_sum($idlePerFleet["counts"]) }}',
                label: 'TOTAL'
            }
        }
    }
});
generateLegend(idleLabels, idleCounts, chartColors, 'idleLegend', true, '');

// 2. Speed per Fleet (Donut Chart) - MULTI COLOR
const speedLabels = {!! json_encode($speedPerFleet['labels']) !!};
const speedCounts = {!! json_encode($speedPerFleet['counts']) !!};
const maxSpeedOverall = speedCounts.length > 0 ? Math.max(...speedCounts) : 0;

// Define unique colors for each fleet (6 different colors)
const speedColors = [
    '#ef4444',  // Red - B-GPE
    '#f97316',  // Orange - DT-GPE
    '#f59e0b',  // Amber - FT-GPE
    '#8b5cf6',  // Purple - HD-GPE
    '#3b82f6',  // Blue - WT-GPE
    '#10b981'   // Green - LV-GPE
];

const speedCtx = document.getElementById('speedDonutChart').getContext('2d');
new Chart(speedCtx, {
    type: 'doughnut',
    data: {
        labels: speedLabels,
        datasets: [{
            data: speedCounts.length > 0 ? speedCounts : [1],
            backgroundColor: speedCounts.length > 0 ? speedColors.slice(0, speedCounts.length) : ['#e2e8f0'],
            borderWidth: 0,
            hoverOffset: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '75%',
        plugins: {
            legend: { display: false },
            centerText: {
                value: maxSpeedOverall,
                label: 'KM/H MAX',
                colorValue: '#ef4444'
            }
        }
    }
});
generateLegend(speedLabels, speedCounts, speedColors, 'speedLegend', false, 'km/h');


// 3. Trend Idle Alarm - Bar Chart
const idleBarCtx = document.getElementById('idleBarChart').getContext('2d');
new Chart(idleBarCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($idlePerDay['days']) !!},
        datasets: [{
            label: 'Idle Alarm',
            data: {!! json_encode($idlePerDay['counts']) !!},
            backgroundColor: '#3b82f6',
            borderRadius: 4,
            barThickness: 35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: function(c) { return c.parsed.y + ' alarms'; } }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: 600, family: 'Inter' }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: { font: { size: 11, weight: 600, family: 'Inter' }, color: '#64748b' },
                border: { display: false }
            }
        }
    }
});

// 4. Trend Max Speed - Bar Chart
const speedBarCtx = document.getElementById('speedBarChart').getContext('2d');
new Chart(speedBarCtx, {
    type: 'bar',
    data: {
        labels: {!! json_encode($speedPerDay['days']) !!},
        datasets: [{
            label: 'Max Speed',
            data: {!! json_encode($speedPerDay['counts']) !!},
            backgroundColor: '#ef4444',
            borderRadius: 4,
            barThickness: 35
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: { label: function(c) { return c.parsed.y + ' km/h'; } }
            }
        },
        scales: {
            x: {
                grid: { display: false },
                ticks: { font: { size: 11, weight: 600, family: 'Inter' }, color: '#64748b' }
            },
            y: {
                beginAtZero: true,
                grid: { color: '#f1f5f9', drawBorder: false },
                ticks: { font: { size: 11, weight: 600, family: 'Inter' }, color: '#64748b' },
                border: { display: false }
            }
        }
    }
});
</script>
@endsection
