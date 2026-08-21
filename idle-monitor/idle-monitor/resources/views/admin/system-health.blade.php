@extends('admin.layouts.app')

@section('title', 'System Health Check')

@push('styles')
<style>
    body {
        background: #f8f9fa !important;
    }
    .container-fluid {
        padding: 24px 32px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 28px;
    }
    .page-title {
        display: flex;
        align-items: center;
        font-size: 22px;
        font-weight: 700;
        color: #1a1a1a;
    }
    .page-title i {
        color: #6366f1;
        margin-right: 10px;
        font-size: 24px;
    }
    .page-subtitle {
        color: #6b7280;
        font-size: 13px;
        margin-top: 4px;
    }

    /* Refresh Button */
    .refresh-btn {
        background: #6366f1;
        color: white;
        border: none;
        padding: 10px 20px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .refresh-btn:hover {
        background: #4f46e5;
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(99, 102, 241, 0.3);
    }

    /* Top Stats Row */
    .top-stats-row {
        display: grid;
        grid-template-columns: 280px repeat(3, 1fr);
        gap: 20px;
        margin-bottom: 24px;
    }

    /* Health Score Card */
    .health-score-card {
        background: white;
        border-radius: 12px;
        padding: 28px 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .health-circle {
        position: relative;
        width: 140px;
        height: 140px;
        margin-bottom: 16px;
    }
    .health-circle canvas {
        transform: rotate(-90deg);
    }
    .health-score-text {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        text-align: center;
    }
    .health-score-number {
        font-size: 46px;
        font-weight: 700;
        line-height: 1;
        color: #1a1a1a;
    }
    .health-score-label {
        font-size: 12px;
        color: #9ca3af;
        margin-top: 6px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .health-status-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 14px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        margin-bottom: 12px;
    }
    .status-healthy {
        background: #d1fae5;
        color: #065f46;
    }
    .status-warning {
        background: #fed7aa;
        color: #92400e;
    }
    .status-critical {
        background: #fee2e2;
        color: #991b1b;
    }
    .last-checked-text {
        font-size: 11px;
        color: #9ca3af;
    }

    /* Stat Card */
    .stat-card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        position: relative;
        overflow: hidden;
    }
    .stat-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 4px;
        height: 100%;
    }
    .stat-card.success::before { background: #10b981; }
    .stat-card.failed::before { background: #ef4444; }
    .stat-card.rate::before { background: #6366f1; }
    
    .stat-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        margin-bottom: 16px;
    }
    .stat-card.success .stat-icon {
        background: #d1fae5;
        color: #059669;
    }
    .stat-card.failed .stat-icon {
        background: #fee2e2;
        color: #dc2626;
    }
    .stat-card.rate .stat-icon {
        background: #e0e7ff;
        color: #6366f1;
    }
    .stat-value {
        font-size: 36px;
        font-weight: 700;
        margin-bottom: 4px;
        color: #1a1a1a;
    }
    .stat-label {
        font-size: 11px;
        color: #6b7280;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
    }
    .stat-sublabel {
        font-size: 13px;
        color: #9ca3af;
        margin-top: 2px;
    }

    /* System Checks Grid */
    .checks-grid {
        display: grid;
        grid-template-columns: repeat(5, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .check-card {
        background: white;
        border-radius: 12px;
        padding: 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        display: flex;
        align-items: flex-start;
        gap: 14px;
        transition: all 0.2s;
        border: 1px solid transparent;
    }
    .check-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        border-color: #e5e7eb;
    }
    .check-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        flex-shrink: 0;
    }
    .check-icon.ok {
        background: #d1fae5;
        color: #059669;
    }
    .check-icon.warning {
        background: #fed7aa;
        color: #d97706;
    }
    .check-icon.error {
        background: #fee2e2;
        color: #dc2626;
    }
    .check-content {
        flex: 1;
        min-width: 0;
    }
    .check-title {
        font-size: 13px;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 4px;
    }
    .check-message {
        font-size: 11px;
        color: #6b7280;
        margin-bottom: 6px;
    }
    .check-details {
        font-size: 10px;
        color: #9ca3af;
    }

    /* Collapsible Sections */
    .collapsible-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.06);
        margin-bottom: 16px;
        overflow: hidden;
    }
    .collapsible-header {
        padding: 20px 24px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #f3f4f6;
        transition: background 0.2s;
    }
    .collapsible-header:hover {
        background: #f9fafb;
    }
    .collapsible-title {
        display: flex;
        align-items: center;
        gap: 12px;
        font-size: 18px;
        font-weight: 700;
        color: #111827;
    }
    .collapsible-badge {
        background: #ef4444;
        color: white;
        border-radius: 12px;
        padding: 3px 10px;
        font-size: 12px;
        font-weight: 600;
    }
    .collapsible-badge.warning {
        background: #f59e0b;
    }
    .collapsible-badge.success {
        background: #10b981;
    }
    .collapsible-icon {
        font-size: 14px;
        color: #9ca3af;
        transition: transform 0.3s;
    }
    .collapsible-header.active .collapsible-icon {
        transform: rotate(180deg);
    }
    .collapsible-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .collapsible-content.active {
        max-height: 2000px;
        transition: max-height 0.5s ease-in;
    }
    .collapsible-body {
        padding: 20px 24px;
    }

    /* Issues/Warnings - Clean List */
    .issues-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .issue-card {
        background: #fef2f2;
        border-radius: 10px;
        padding: 18px;
        border-left: 4px solid #dc2626;
    }
    .issue-card.warning {
        background: #fffbeb;
        border-left-color: #f59e0b;
    }
    .issue-header {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        margin-bottom: 12px;
    }
    .issue-icon {
        font-size: 20px;
        flex-shrink: 0;
    }
    .issue-content {
        flex: 1;
    }
    .issue-title {
        font-size: 15px;
        font-weight: 700;
        color: #111827;
        margin-bottom: 6px;
    }
    .issue-message {
        font-size: 13px;
        color: #6b7280;
        margin-bottom: 12px;
    }
    .issue-actions {
        display: flex;
        gap: 8px;
        margin-bottom: 12px;
    }
    .heal-btn {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
        border: none;
        padding: 8px 14px;
        border-radius: 7px;
        font-size: 12px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .heal-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
    }
    .troubleshooting-box {
        background: white;
        border-radius: 8px;
        padding: 14px;
        border-left: 3px solid #5a5ced;
    }
    .troubleshooting-title {
        font-size: 12px;
        font-weight: 700;
        color: #374151;
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .troubleshooting-steps {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .troubleshooting-steps li {
        font-size: 12px;
        color: #4b5563;
        padding: 5px 0;
        padding-left: 20px;
        position: relative;
    }
    .troubleshooting-steps li:before {
        content: "→";
        position: absolute;
        left: 0;
        color: #5a5ced;
        font-weight: 700;
    }

    /* History Timeline - Clean */
    .history-timeline {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    .history-item {
        display: flex;
        gap: 14px;
        padding: 14px;
        background: #f9fafb;
        border-radius: 10px;
        transition: transform 0.2s;
    }
    .history-item:hover {
        transform: translateX(4px);
        background: #f3f4f6;
    }
    .history-item-icon {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 16px;
        flex-shrink: 0;
    }
    .history-item-icon.success {
        background: #ecfdf5;
        color: #059669;
    }
    .history-item-icon.failed {
        background: #fef2f2;
        color: #dc2626;
    }
    .history-item-icon.attempted {
        background: #fffbeb;
        color: #f59e0b;
    }
    .history-item-content {
        flex: 1;
    }
    .history-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 6px;
    }
    .history-item-title {
        font-size: 13px;
        font-weight: 600;
        color: #111827;
    }
    .history-item-time {
        font-size: 11px;
        color: #9ca3af;
        white-space: nowrap;
    }
    .history-item-problem {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .history-item-result {
        font-size: 11px;
        color: #9ca3af;
    }
    .history-item-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 8px;
    }
    .history-item-badge.success {
        background: #ecfdf5;
        color: #059669;
    }
    .history-item-badge.failed {
        background: #fef2f2;
        color: #dc2626;
    }

    /* Refresh Button */
    .refresh-btn {
        background: linear-gradient(135deg, #5a5ced 0%, #7c3aed 100%);
        color: white;
        border: none;
        padding: 10px 24px;
        border-radius: 8px;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(90, 92, 237, 0.3);
    }
    .refresh-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 6px 16px rgba(90, 92, 237, 0.4);
    }

    /* Loading State */
    .loading-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.4);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
    }
    .loading-overlay.show {
        display: flex;
    }
    .loading-spinner {
        background: white;
        padding: 30px 50px;
        border-radius: 12px;
        text-align: center;
    }
    .spinner {
        border: 4px solid #f3f4f6;
        border-top: 4px solid #5a5ced;
        border-radius: 50%;
        width: 40px;
        height: 40px;
        animation: spin 1s linear infinite;
        margin: 0 auto 16px;
    }
    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }

    /* Collapsible Sections */
    .collapsible-section {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 16px;
        overflow: hidden;
        border: 1px solid #f3f4f6;
    }
    .collapsible-header {
        padding: 18px 24px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        transition: background 0.2s;
        border-bottom: 1px solid transparent;
    }
    .collapsible-header:hover {
        background: #f9fafb;
    }
    .collapsible-header.active {
        border-bottom-color: #f3f4f6;
    }
    .collapsible-title {
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 700;
        color: #1a1a1a;
    }
    .collapsible-title i {
        font-size: 16px;
    }
    .collapsible-badge {
        background: #ef4444;
        color: white;
        border-radius: 20px;
        padding: 4px 10px;
        font-size: 11px;
        font-weight: 700;
        min-width: 24px;
        text-align: center;
    }
    .collapsible-badge.warning {
        background: #f59e0b;
    }
    .collapsible-badge.success {
        background: #10b981;
    }
    .collapsible-icon {
        font-size: 12px;
        color: #9ca3af;
        transition: transform 0.3s;
    }
    .collapsible-header.active .collapsible-icon {
        transform: rotate(180deg);
    }
    .collapsible-content {
        max-height: 0;
        overflow: hidden;
        transition: max-height 0.3s ease-out;
    }
    .collapsible-content.active {
        max-height: 3000px;
        transition: max-height 0.5s ease-in;
    }
    .collapsible-body {
        padding: 20px 24px 24px;
    }

    /* Issues Cards */
    .issues-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
    }
    .issue-card {
        background: #fffbeb;
        border-radius: 10px;
        padding: 18px;
        border-left: 4px solid #f59e0b;
    }
    .issue-card.critical {
        background: #fef2f2;
        border-left-color: #ef4444;
    }

    /* History Timeline */
    .history-timeline {
        display: flex;
        flex-direction: column;
        gap: 0;
    }
    .history-item {
        display: flex;
        gap: 14px;
        padding: 14px 0;
        border-bottom: 1px solid #f3f4f6;
    }
    .history-item:last-child {
        border-bottom: none;
    }
    .history-item-icon {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 14px;
        flex-shrink: 0;
    }
    .history-item-icon.success {
        background: #d1fae5;
        color: #059669;
    }
    .history-item-icon.failed {
        background: #fee2e2;
        color: #dc2626;
    }
    .history-item-content {
        flex: 1;
    }
    .history-item-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 4px;
    }
    .history-item-title {
        font-size: 13px;
        font-weight: 600;
        color: #1a1a1a;
    }
    .history-item-time {
        font-size: 11px;
        color: #9ca3af;
        white-space: nowrap;
    }
    .history-item-problem {
        font-size: 12px;
        color: #6b7280;
        margin-bottom: 4px;
    }
    .history-item-result {
        font-size: 11px;
        color: #9ca3af;
    }
    .history-item-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
        margin-left: 8px;
    }
    .history-item-badge.success {
        background: #d1fae5;
        color: #059669;
    }
    .history-item-badge.failed {
        background: #fee2e2;
        color: #dc2626;
    }

    /* No Issues State */
    .no-issues {
        background: white;
        border-radius: 12px;
        padding: 40px;
        text-align: center;
    }
    .no-issues-icon {
        font-size: 64px;
        color: #10b981;
        margin-bottom: 16px;
    }
    .no-issues-text {
        font-size: 16px;
        color: #6b7280;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner">
            <div class="spinner"></div>
            <div style="font-size: 14px; color: #6b7280;">Running health checks...</div>
        </div>
    </div>

    <!-- Migration Alert (if table doesn't exist) -->
    @if(isset($table_exists) && !$table_exists)
    <div class="alert alert-warning" style="background: #fffbeb; border: 1px solid #fcd34d; border-radius: 12px; padding: 18px; margin-bottom: 20px;">
        <div style="display: flex; align-items: flex-start; gap: 14px;">
            <div style="font-size: 28px; color: #f59e0b;">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div style="flex: 1;">
                <h4 style="font-size: 15px; font-weight: 700; color: #92400e; margin-bottom: 6px;">
                    Healing Logs Table Not Found
                </h4>
                <p style="font-size: 13px; color: #78350f; margin-bottom: 12px;">
                    The <code>healing_logs</code> table is required for auto-healing functionality.
                </p>
                <button onclick="runMigration()" class="btn btn-warning btn-sm" style="background: #f59e0b; border: none; color: white; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 12px;">
                    <i class="fas fa-database"></i> Run Migration Now
                </button>
            </div>
        </div>
    </div>
    @endif

    <!-- Page Header -->
    <div class="page-header">
        <div>
            <div class="page-title">
                <i class="fas fa-heartbeat"></i> System Health Check
            </div>
            <div class="page-subtitle">Monitor system status, detect issues, and get troubleshooting recommendations</div>
        </div>
        <button class="refresh-btn" onclick="refreshHealth()">
            <i class="fas fa-sync-alt"></i> Refresh Check
        </button>
    </div>

    <!-- Top Stats Row -->
    <div class="top-stats-row">
        <!-- Health Score -->
        <div class="health-score-card">
            <div class="health-status-badge status-{{ $status }}">
                @if($status === 'healthy')
                    <i class="fas fa-check-circle"></i> Good
                @elseif($status === 'warning')
                    <i class="fas fa-exclamation-triangle"></i> Attention
                @else
                    <i class="fas fa-times-circle"></i> Critical
                @endif
            </div>
            
            <div class="health-circle">
                <canvas id="healthCanvas" width="140" height="140"></canvas>
                <div class="health-score-text">
                    <div class="health-score-number" id="healthScoreNumber">
                        {{ $health_score }}
                    </div>
                    <div class="health-score-label">Health Score</div>
                </div>
            </div>
            
            <div class="last-checked-text">
                Last: <span id="lastChecked">{{ now()->format('H:i:s') }}</span>
            </div>
        </div>

        <!-- Healing Stats -->
        @if($table_exists)
        <div class="stat-card success">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-value">{{ $healing_stats['successful'] }}</div>
            <div class="stat-label">SUCCESSFUL HEALS</div>
            <div class="stat-sublabel">Total automated fixes</div>
        </div>
        
        <div class="stat-card failed">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-value">{{ $healing_stats['failed'] }}</div>
            <div class="stat-label">FAILED HEALS</div>
            <div class="stat-sublabel">Requires manual intervention</div>
        </div>
        
        <div class="stat-card rate">
            <div class="stat-icon">
                <i class="fas fa-chart-line"></i>
            </div>
            <div class="stat-value">{{ $healing_stats['success_rate'] }}%</div>
            <div class="stat-label">SUCCESS RATE</div>
            <div class="stat-sublabel">Successful heal percentage</div>
        </div>
        @endif
    </div>

    <!-- System Checks Grid -->
    <div class="checks-grid">
        @foreach($checks as $checkName => $check)
        <div class="check-card">
            <div class="check-icon {{ strtolower($check['status']) === 'ok' ? 'ok' : (strtolower($check['status']) === 'warning' ? 'warning' : 'error') }}">
                @if(strtolower($check['status']) === 'ok')
                    <i class="fas fa-check"></i>
                @elseif(strtolower($check['status']) === 'warning')
                    <i class="fas fa-exclamation-triangle"></i>
                @else
                    <i class="fas fa-times"></i>
                @endif
            </div>
            <div class="check-content">
                <div class="check-title">{{ ucwords(str_replace('_', ' ', $checkName)) }}</div>
                <div class="check-message">{{ $check['message'] }}</div>
                <div class="check-details">{{ $check['details'] }}</div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Critical Issues - Collapsible -->
    @if(count($issues) > 0)
    <div class="collapsible-section">
        <div class="collapsible-header active" onclick="toggleCollapsible(this)">
            <div class="collapsible-title">
                <i class="fas fa-exclamation-circle" style="color: #dc2626;"></i>
                Critical Issues
                <span class="collapsible-badge">{{ count($issues) }}</span>
            </div>
            <i class="fas fa-chevron-down collapsible-icon"></i>
        </div>
        <div class="collapsible-content active">
            <div class="collapsible-body">
                <div class="issues-list">
                    @foreach($issues as $issue)
                    <div class="issue-card">
                        <div class="issue-header">
                            <div class="issue-icon">{{ substr($issue['title'], 0, 2) }}</div>
                            <div class="issue-content">
                                <div class="issue-title">{{ $issue['title'] }}</div>
                                <div class="issue-message">{{ $issue['message'] }}</div>

                                @if(isset($issue['healable']) && $issue['healable'])
                                <div class="issue-actions">
                                    <button class="heal-btn" onclick="manualHeal('{{ $issue['issue_type'] }}')">
                                        <i class="fas fa-magic"></i> Auto-Heal Issue
                                    </button>
                                </div>
                                @endif

                                <div class="troubleshooting-box">
                                    <div class="troubleshooting-title">
                                        <i class="fas fa-lightbulb"></i> How to Fix
                                    </div>
                                    <ul class="troubleshooting-steps">
                                        @foreach($issue['troubleshooting'] as $step)
                                        <li>{{ $step }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Warnings - Collapsible -->
    @if(count($warnings) > 0)
    <div class="collapsible-section">
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <div class="collapsible-title">
                <i class="fas fa-exclamation-triangle" style="color: #f59e0b;"></i>
                Warnings
                <span class="collapsible-badge warning">{{ count($warnings) }}</span>
            </div>
            <i class="fas fa-chevron-down collapsible-icon"></i>
        </div>
        <div class="collapsible-content">
            <div class="collapsible-body">
                <div class="issues-list">
                    @foreach($warnings as $warning)
                    <div class="issue-card warning">
                        <div class="issue-header">
                            <div class="issue-icon">⚠️</div>
                            <div class="issue-content">
                                <div class="issue-title">{{ $warning['title'] }}</div>
                                <div class="issue-message">{{ $warning['message'] }}</div>

                                @if(isset($warning['healable']) && $warning['healable'])
                                <div class="issue-actions">
                                    <button class="heal-btn" onclick="manualHeal('{{ $warning['issue_type'] }}')">
                                        <i class="fas fa-magic"></i> Auto-Heal Issue
                                    </button>
                                </div>
                                @endif

                                <div class="troubleshooting-box">
                                    <div class="troubleshooting-title">
                                        <i class="fas fa-lightbulb"></i> Recommended Actions
                                    </div>
                                    <ul class="troubleshooting-steps">
                                        @foreach($warning['troubleshooting'] as $step)
                                        <li>{{ $step }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Healing History - Collapsible -->
    @if($table_exists && $healing_logs->count() > 0)
    <div class="collapsible-section">
        <div class="collapsible-header" onclick="toggleCollapsible(this)">
            <div class="collapsible-title">
                <i class="fas fa-history" style="color: #5a5ced;"></i>
                Auto-Healing History
                <span class="collapsible-badge success">{{ $healing_logs->count() }}</span>
            </div>
            <i class="fas fa-chevron-down collapsible-icon"></i>
        </div>
        <div class="collapsible-content">
            <div class="collapsible-body">
                <div class="history-timeline">
                    @foreach($healing_logs->take(15) as $log)
                    <div class="history-item">
                        <div class="history-item-icon {{ $log->status }}">
                            @if($log->status === 'success')
                                <i class="fas fa-check"></i>
                            @elseif($log->status === 'failed')
                                <i class="fas fa-times"></i>
                            @else
                                <i class="fas fa-hourglass-half"></i>
                            @endif
                        </div>
                        <div class="history-item-content">
                            <div class="history-item-header">
                                <div>
                                    <span class="history-item-title">{{ ucwords(str_replace('_', ' ', $log->issue_type)) }}</span>
                                    <span class="history-item-badge {{ $log->status }}">
                                        {{ ucfirst($log->status) }}
                                    </span>
                                </div>
                                <div class="history-item-time">{{ $log->created_at->diffForHumans() }}</div>
                            </div>
                            <div class="history-item-problem">{{ Str::limit($log->problem_description, 80) }}</div>
                            <div class="history-item-result">
                                <strong>Action:</strong> {{ $log->healing_action }} 
                                @if($log->execution_time_ms)
                                    • <strong>{{ $log->execution_time_ms }}ms</strong>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- No Issues State -->
    @if(count($issues) === 0 && count($warnings) === 0)
    <div style="background: white; border-radius: 12px; padding: 60px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.06);">
        <div style="font-size: 64px; color: #10b981; margin-bottom: 16px;">
            <i class="fas fa-check-circle"></i>
        </div>
        <div style="font-size: 18px; font-weight: 700; color: #111827; margin-bottom: 8px;">
            All Systems Operational
        </div>
        <div style="font-size: 14px; color: #6b7280;">
            No issues or warnings detected. Everything is running smoothly!
        </div>
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
    // Run database migration
    function runMigration() {
        if (!confirm('This will create the healing_logs table. Continue?')) {
            return;
        }

        document.getElementById('loadingOverlay').classList.add('show');

        fetch('{{ route('admin.system-health.migrate') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message + '\n\nPage will reload now.');
                location.reload();
            } else {
                alert('❌ Migration failed: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Migration failed. Check console for details.');
        })
        .finally(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
        });
    }

    // Manual heal specific issue
    function manualHeal(issueType) {
        if (!confirm('Attempt to auto-heal this issue?\n\nIssue: ' + issueType)) {
            return;
        }

        document.getElementById('loadingOverlay').classList.add('show');

        fetch('{{ route('admin.system-health.heal') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                issue_type: issueType
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('✅ Healing successful!\n\n' + data.message + '\n\nPage will reload to show updated status.');
                location.reload();
            } else {
                alert('❌ Healing failed:\n\n' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('❌ Healing request failed. Check console for details.');
        })
        .finally(() => {
            document.getElementById('loadingOverlay').classList.remove('show');
        });
    }

    // Draw health score circle
    function drawHealthCircle(score) {
        const canvas = document.getElementById('healthCanvas');
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 58;
        const lineWidth = 11;

        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.width);

        // Determine color based on score
        let color = '#10b981'; // green
        if (score < 70) color = '#ef4444'; // red
        else if (score < 90) color = '#f59e0b'; // orange

        // Draw background circle
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, 2 * Math.PI);
        ctx.strokeStyle = '#f3f4f6';
        ctx.lineWidth = lineWidth;
        ctx.stroke();

        // Draw score arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, 0, (score / 100) * 2 * Math.PI);
        ctx.strokeStyle = color;
        ctx.lineWidth = lineWidth;
        ctx.lineCap = 'round';
        ctx.stroke();
    }

    // Toggle collapsible sections
    function toggleCollapsible(header) {
        header.classList.toggle('active');
        const content = header.nextElementSibling;
        content.classList.toggle('active');
    }

    // Initial draw
    drawHealthCircle({{ $health_score }});

    // Refresh health check
    function refreshHealth() {
        document.getElementById('loadingOverlay').classList.add('show');

        fetch('{{ route('admin.system-health.check') }}')
            .then(response => response.json())
            .then(data => {
                // Reload page with new data
                location.reload();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to refresh health check. Please try again.');
            })
            .finally(() => {
                document.getElementById('loadingOverlay').classList.remove('show');
            });
    }

    // Auto-refresh every 60 seconds
    setInterval(function() {
        fetch('{{ route('admin.system-health.check') }}')
            .then(response => response.json())
            .then(data => {
                // Update last checked time
                document.getElementById('lastChecked').textContent = new Date().toLocaleString();
                
                // If status changed significantly, reload page
                const currentScore = parseInt(document.getElementById('healthScoreNumber').textContent);
                if (Math.abs(currentScore - data.health_score) > 10) {
                    location.reload();
                }
            });
    }, 60000); // Every 60 seconds
</script>
@endpush
