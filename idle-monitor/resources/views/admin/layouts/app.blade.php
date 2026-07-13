<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - Idle Monitor System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- SweetAlert2 for nice alerts -->
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <!-- NProgress: top loading bar for fast navigation feedback -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
    <style>
        /* NProgress customization - purple to match admin theme */
        #nprogress .bar { background: #5a5ced !important; height: 3px !important; }
        #nprogress .peg  { box-shadow: 0 0 10px #5a5ced, 0 0 5px #5a5ced !important; }
        #nprogress .spinner-icon { border-top-color: #5a5ced !important; border-left-color: #5a5ced !important; }
        /* Page fade-in */
        .page-fade { animation: pageFadeIn 0.18s ease; }
        @keyframes pageFadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <style>
        body {
            background-color: #f8f9fc;
            font-family: 'Inter', sans-serif;
            color: #333;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        .top-accent {
            height: 4px;
            background: #5a5ced;
            width: 100%;
            position: fixed;
            top: 0;
            z-index: 1050;
        }

        /* Sidebar */
        .sidebar {
            background: white;
            height: 100vh;
            width: 250px;
            position: fixed;
            top: 0;
            left: 0;
            overflow-y: auto;
            border-right: 1px solid #eef2f7;
            display: flex;
            flex-direction: column;
            z-index: 1040;
            padding-top: 20px;
        }
        .sidebar-section {
            font-size: 11px;
            font-weight: 600;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 20px 0 10px 20px;
        }
        .sidebar .nav-link {
            color: #4b5563;
            padding: 10px 20px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            border-radius: 0 20px 20px 0;
            margin-right: 15px;
            margin-bottom: 2px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .sidebar .nav-link:hover {
            background-color: #f3f4f6;
            color: #111827;
        }
        .sidebar .nav-link.active {
            background-color: #f0f0ff;
            color: #5a5ced;
            font-weight: 600;
        }
        .sidebar .nav-link i {
            width: 24px;
            font-size: 16px;
            margin-right: 10px;
            text-align: center;
        }
        .sidebar-footer {
            margin-top: auto;
            padding: 15px 20px;
            border-top: 1px solid #eef2f7;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #6b7280;
            font-size: 13px;
        }
        .toggle-switch {
            width: 36px;
            height: 20px;
            background: #e5e7eb;
            border-radius: 10px;
            position: relative;
            cursor: pointer;
        }
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }

        /* Main Content */
        .main-content {
            margin-left: 250px;
            padding: 30px;
            padding-top: 20px;
            min-height: 100vh;
        }

        /* Global Card Styling for other pages (like User Management) */
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #eef2f7;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
            margin-bottom: 24px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #eef2f7;
            padding: 16px 20px;
            font-weight: 600;
            font-size: 16px;
            color: #111827;
            border-radius: 12px 12px 0 0 !important;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .card-body {
            padding: 20px;
        }

        /* Global Button Styling */
        .btn-primary {
            background-color: #5a5ced;
            border-color: #5a5ced;
            border-radius: 6px;
            padding: 8px 16px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
        }
        .btn-primary:hover {
            background-color: #4a4ccf;
            border-color: #4a4ccf;
        }

        /* DataTables adjustments for the new theme */
        .dataTables_wrapper .dataTables_paginate .paginate_button.current {
            background: #5a5ced !important;
            color: white !important;
            border: 1px solid #5a5ced !important;
            border-radius: 6px;
        }
        table.dataTable {
            border-collapse: collapse !important;
        }
        table.dataTable thead th {
            color: #6b7280;
            font-weight: 600;
            padding: 12px 10px;
            border-bottom: 1px solid #eef2f7 !important;
            font-size: 13px;
        }
        table.dataTable tbody td {
            padding: 12px 10px;
            border-bottom: 1px solid #f9fafb !important;
            color: #374151;
            font-size: 13px;
            vertical-align: middle;
        }

        @media (max-width: 768px) {
            .sidebar { transform: translateX(-100%); }
            .main-content { margin-left: 0; }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="top-accent"></div>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Admin Profile -->
        <div style="padding: 10px 20px; display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
            <div style="width: 40px; height: 40px; border-radius: 50%; background: #f0f0ff; color: #5a5ced; display: flex; align-items: center; justify-content: center; font-size: 18px; font-weight: bold;">
                {{ substr(auth()->user()->name, 0, 1) }}
            </div>
            <div style="overflow: hidden;">
                <div style="font-size: 14px; font-weight: 600; color: #111827; white-space: nowrap; text-overflow: ellipsis;">{{ auth()->user()->name }}</div>
                <div style="font-size: 12px; color: #6b7280;">Administrator</div>
            </div>
        </div>
        
        <div class="sidebar-section">MENU</div>
        <a href="{{ route('admin.dashboard') }}" class="nav-link {{ Route::currentRouteName() === 'admin.dashboard' ? 'active' : '' }}">
            <i class="fas fa-home"></i> Dashboard
        </a>
        <a href="{{ route('admin.user.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.user.index' ? 'active' : '' }}">
            <i class="fas fa-users"></i> User Management
        </a>
        <a href="{{ route('admin.device.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.device.index' ? 'active' : '' }}">
            <i class="fas fa-car"></i> Device Management
        </a>
        <a href="{{ route('admin.import-log.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.import-log.index' ? 'active' : '' }}">
            <i class="fas fa-history"></i> Import Logs
        </a>
        <a href="{{ route('admin.data-pull.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.data-pull.index' ? 'active' : '' }}">
            <i class="fas fa-download"></i> Data Pull (Idle)
        </a>
        <a href="{{ route('admin.gps-track-pull.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.gps-track-pull.index' ? 'active' : '' }}">
            <i class="fas fa-map-marked-alt"></i> GPS Track Pull
        </a>
        <a href="{{ route('admin.auto-data-pull.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.auto-data-pull.index' ? 'active' : '' }}">
            <i class="fas fa-sync-alt"></i> Auto Data Pull
        </a>
        
        <div class="sidebar-section mt-3">SYSTEM</div>
        <a href="{{ route('admin.system-health.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.system-health.index' ? 'active' : '' }}">
            <i class="fas fa-heartbeat"></i> System Health
        </a>
        <a href="{{ route('admin.system-setting.index') }}" class="nav-link {{ Route::currentRouteName() === 'admin.system-setting.index' ? 'active' : '' }}">
            <i class="fas fa-cogs"></i> System Settings
        </a>
        
        <div style="padding: 0 20px; margin-top: 10px;">
            <a href="#" onclick="event.preventDefault(); handleLogout();" class="nav-link" style="width: 100%; text-align: left; padding: 10px 0; color: #dc2626; cursor: pointer;">
                <i class="fas fa-sign-out-alt" style="color: #dc2626; width: 24px; text-align: center; margin-right: 10px;"></i> Logout
            </a>
            
            <!-- Hidden form for logout (will be submitted via JS) -->
            <form id="logoutForm" action="{{ route('admin.logout') }}" method="POST" style="display: none;">
                @csrf
            </form>
        </div>


        <div style="font-size: 11px; color: #9ca3af; padding: 10px 20px; text-align: center;">
            &copy; {{ date('Y') }} Idle Monitor Backend
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        // Setup AJAX CSRF Token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // ═══════════════════════════════════════════════════════════════
        // LOGOUT HANDLER (prevent Page Expired by refreshing token first)
        // ═══════════════════════════════════════════════════════════════
        function handleLogout() {
            // Show loading (optional)
            console.log('[Logout] Refreshing CSRF token before logout...');
            
            // First, refresh CSRF token
            fetch('/admin/refresh-csrf', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    console.log('[Logout] Got fresh token, submitting logout...');
                    // Update token in hidden form
                    $('#logoutForm input[name="_token"]').val(data.token);
                    // Update meta tag
                    $('meta[name="csrf-token"]').attr('content', data.token);
                    
                    // Submit logout form with fresh token after a tiny delay
                    setTimeout(() => {
                        document.getElementById('logoutForm').submit();
                    }, 100);
                } else {
                    console.warn('[Logout] No token received, trying direct logout');
                    document.getElementById('logoutForm').submit();
                }
            })
            .catch(error => {
                console.error('[Logout] Token refresh failed:', error);
                // Fallback: try direct logout anyway
                alert('Session may have expired. Redirecting to login...');
                window.location.href = '/admin/login';
            });
        }
        
        // ═══════════════════════════════════════════════════════════════
        // AUTO-REFRESH CSRF TOKEN (prevent "Page Expired" on logout)
        // ═══════════════════════════════════════════════════════════════
        // Refresh CSRF token every 50 minutes (before 60 min session expires)
        setInterval(function() {
            fetch('/admin/refresh-csrf', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.token) {
                    // Update meta tag
                    $('meta[name="csrf-token"]').attr('content', data.token);
                    // Update all CSRF input fields
                    $('input[name="_token"]').val(data.token);
                    // Update AJAX setup
                    $.ajaxSetup({
                        headers: {
                            'X-CSRF-TOKEN': data.token
                        }
                    });
                    console.log('[CSRF] Token refreshed successfully');
                }
            })
            .catch(error => console.error('[CSRF] Token refresh failed:', error));
        }, 50 * 60 * 1000); // 50 minutes
        
        // Also refresh on user activity after idle
        let lastActivity = Date.now();
        document.addEventListener('click', () => lastActivity = Date.now());
        document.addEventListener('keypress', () => lastActivity = Date.now());
        
        setInterval(function() {
            const idleTime = Date.now() - lastActivity;
            // If idle > 30 min, refresh token on next activity
            if (idleTime > 30 * 60 * 1000) {
                // Will refresh on next interval
            }
        }, 60 * 1000); // Check every minute
    </script>
    
    @stack('scripts')

    <!-- instant.page: prefetch pages on hover for faster navigation -->
    <script src="https://cdn.jsdelivr.net/npm/instant.page@5.2.0/instantpage.js" type="module"></script>

    <!-- NProgress: show loading bar when navigating between pages -->
    <script src="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.js"></script>
    <script>
        NProgress.configure({ showSpinner: false, speed: 200, minimum: 0.1 });

        // Start NProgress on any internal link click
        document.addEventListener('click', function(e) {
            var link = e.target.closest('a[href]');
            if (!link) return;
            var href = link.getAttribute('href');
            // Only internal links, not anchors or javascript:
            if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto')) return;
            // Skip links that open in new tab
            if (link.target === '_blank') return;
            // Skip form-trigger links (logout etc)
            if (link.closest('form')) return;
            NProgress.start();
        });

        // Also start on form submit (e.g. filters)
        document.addEventListener('submit', function() {
            NProgress.start();
        });

        // Stop NProgress when page fully loads
        window.addEventListener('pageshow', function() {
            NProgress.done();
        });
    </script>
</body>
</html>
