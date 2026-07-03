<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo e(csrf_token()); ?>">
    <title><?php echo $__env->yieldContent('title', 'Fleet Monitoring System'); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- NProgress: top loading bar for fast navigation feedback -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/nprogress@0.2.0/nprogress.css">
    <style>
        /* NProgress customization */
        #nprogress .bar { background: #3b82f6 !important; height: 3px !important; }
        #nprogress .peg  { box-shadow: 0 0 10px #3b82f6, 0 0 5px #3b82f6 !important; }
        #nprogress .spinner-icon { border-top-color: #3b82f6 !important; border-left-color: #3b82f6 !important; }
        /* Page fade-in */
        .page-fade { animation: pageFadeIn 0.18s ease; }
        @keyframes pageFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
    </style>
    <style>
        body {
            background-color: #f3f4f6;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            color: #1e293b;
        }
        
        /* Top Navbar */
        .navbar-top {
            background-color: #0b1a30;
            height: 70px;
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
            color: white;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Brand Area */
        .navbar-brand-area {
            display: flex;
            align-items: center;
            width: 280px; /* Match sidebar width */
            text-decoration: none;
            color: white;
        }
        .navbar-brand-icon {
            font-size: 28px;
            color: #3b82f6;
            margin-right: 15px;
        }
        .navbar-brand-text h1 {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            letter-spacing: 0.5px;
        }
        .navbar-brand-text p {
            font-size: 10px;
            color: #94a3b8;
            margin: 0;
            white-space: nowrap;
        }

        /* Center Nav Links */
        .navbar-center-links {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-grow: 1;
            padding-left: 20px;
        }
        .navbar-center-links a {
            color: #cbd5e1;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.2s;
        }
        .navbar-center-links a:hover {
            color: white;
            background-color: rgba(255,255,255,0.05);
        }
        .navbar-center-links a.active {
            color: white;
            background-color: #1d4ed8;
        }
        .navbar-center-links a i {
            font-size: 14px;
        }

        /* Right Navbar Area */
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            font-size: 12px;
            color: #cbd5e1;
        }
        .navbar-right-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .navbar-right-item i {
            font-size: 14px;
        }
        
        /* User Profile Dropdown */
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            padding-left: 10px;
            border-left: 1px solid rgba(255,255,255,0.1);
            color: white;
            font-weight: 600;
        }
        .user-avatar {
            width: 28px;
            height: 28px;
            border-radius: 50%;
            background-color: white;
            color: #0b1a30;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
        }
        
        /* Layout Wrapper */
        .wrapper {
            display: flex;
            margin-top: 70px;
            height: calc(100vh - 70px);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: #0b1a30;
            border-right: 1px solid rgba(255,255,255,0.05);
            height: 100%;
            display: flex;
            flex-direction: column;
            flex-shrink: 0;
            color: white;
            overflow-y: auto;
            overflow-x: hidden;
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .sidebar.collapsed {
            width: 0;
            border-right: none;
        }
        .sidebar.collapsed .sidebar-content,
        .sidebar.collapsed .sidebar-promo,
        .sidebar.collapsed .sidebar-footer {
            opacity: 0;
            pointer-events: none;
        }
        .sidebar::-webkit-scrollbar { width: 4px; }
        .sidebar::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.2); border-radius: 4px; }

        .sidebar-content {
            flex-grow: 1;
            transition: opacity 0.2s;
        }

        /* Promo Box in Sidebar */
        .sidebar-promo {
            margin: 20px 15px;
            border-radius: 12px;
            overflow: hidden;
            position: relative;
            background: url('<?php echo e(asset('images/bglogin.png')); ?>') no-repeat center center;
            background-size: cover;
            min-height: 200px;
        }
        .sidebar-promo-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to bottom, rgba(11,26,48,0.4), rgba(11,26,48,0.95));
            padding: 20px 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .sidebar-promo-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 10px;
            line-height: 1.3;
        }
        .sidebar-promo-desc {
            font-size: 11px;
            color: #cbd5e1;
            line-height: 1.4;
        }

        /* Collapse Menu Button */
        .sidebar-footer {
            padding: 15px;
            border-top: 1px solid rgba(255,255,255,0.05);
            transition: opacity 0.2s;
        }
        .collapse-btn {
            display: flex;
            align-items: center;
            gap: 10px;
            color: #94a3b8;
            font-size: 13px;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            transition: color 0.2s;
            white-space: nowrap;
        }
        .collapse-btn:hover {
            color: white;
        }

        /* Sidebar Toggle Button (shown when sidebar is collapsed) */
        .sidebar-toggle-fab {
            position: fixed;
            left: 10px;
            bottom: 20px;
            z-index: 1100;
            width: 38px;
            height: 38px;
            background-color: #1d4ed8;
            color: white;
            border: none;
            border-radius: 50%;
            display: none;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(29,78,216,0.5);
            transition: background-color 0.2s, transform 0.2s;
        }
        .sidebar-toggle-fab:hover {
            background-color: #2563eb;
            transform: scale(1.08);
        }
        .sidebar-toggle-fab.visible {
            display: flex;
        }
        
        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background-color: #f3f4f6;
        }
        /* Add fade-in to main content on load */
        .main-content > * { animation: pageFadeIn 0.18s ease; }

        /* Utility Classes */
        .card-custom {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);
            border: 1px solid #e2e8f0;
        }
        
        <?php echo $__env->yieldContent('styles'); ?>
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <div class="navbar-top">
        <!-- Brand -->
        <a href="<?php echo e(route('frontend.dashboard')); ?>" class="navbar-brand-area">
            <div class="navbar-brand-icon"><i class="fas fa-bolt"></i></div>
            <div class="navbar-brand-text">
                <h1>Fleet Monitoring System</h1>
                <p>Production & Equipment Performance Monitoring</p>
            </div>
        </a>

        <!-- Center Links -->
        <div class="navbar-center-links">
            <a href="<?php echo e(route('frontend.dashboard')); ?>" class="<?php echo e(Route::currentRouteName() === 'frontend.dashboard' ? 'active' : ''); ?>">
                <i class="fas fa-border-all"></i> Dashboard
            </a>
            <a href="<?php echo e(route('frontend.idle-alarm.index')); ?>" class="<?php echo e(str_contains(Route::currentRouteName(), 'idle-alarm') ? 'active' : ''); ?>">
                <i class="fas fa-bolt"></i> Idle
            </a>
            <a href="<?php echo e(route('frontend.speed.index')); ?>" class="<?php echo e(Route::currentRouteName() === 'frontend.speed.index' ? 'active' : ''); ?>">
                <i class="fas fa-tachometer-alt"></i> Speed
            </a>
            <a href="<?php echo e(route('frontend.speed-performance.index')); ?>" class="<?php echo e(Route::currentRouteName() === 'frontend.speed-performance.index' ? 'active' : ''); ?>">
                <i class="fas fa-chart-line"></i> Speed Performance
            </a>
        </div>

        <!-- Right Side -->
        <div class="navbar-right">
            <div class="navbar-right-item"><i class="far fa-calendar-alt"></i> <?php echo e(date('d M Y')); ?></div>
            <div class="navbar-right-item"><i class="far fa-clock"></i> <span id="clockTop"></span> WITA</div>
            <div class="navbar-right-item d-none d-xl-flex">VSS Fleet Monitoring</div>
            
            <div class="dropdown">
                <div class="user-profile" data-bs-toggle="dropdown">
                    <div class="user-avatar">
                        <i class="fas fa-user"></i>
                    </div>
                    <span><?php echo e(explode(' ', auth()->user()->name ?? 'Administrator')[0]); ?> <i class="fas fa-caret-down ms-1" style="font-size:10px;"></i></span>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                    <li>
                        <a href="#" onclick="event.preventDefault(); handleFrontendLogout();" class="dropdown-item text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                        <!-- Hidden form for logout (will be submitted via JS) -->
                        <form id="frontendLogoutForm" action="<?php echo e(route('frontend.logout')); ?>" method="POST" style="display: none;">
                            <?php echo csrf_field(); ?>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Wrapper -->
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-content">
                <?php echo $__env->yieldContent('sidebar'); ?>
            </div>
            
            <!-- Promo Box -->
            <div class="sidebar-promo">
                <div class="sidebar-promo-overlay">
                    <div class="sidebar-promo-title">Monitor Fleet.<br>Maximize Performance.</div>
                    <div class="sidebar-promo-desc">Data akurat, keputusan tepat, operasional lebih efisien.</div>
                </div>
            </div>

            <!-- Collapse Toggle -->
            <div class="sidebar-footer">
                <a href="#" class="collapse-btn" id="collapseSidebarBtn">
                    <i class="fas fa-outdent" id="collapseIcon"></i>
                    <span id="collapseText">Collapse Menu</span>
                </a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <?php echo $__env->yieldContent('content'); ?>
        </main>
    </div>

    <!-- FAB button to re-open sidebar when collapsed -->
    <button class="sidebar-toggle-fab" id="expandSidebarFab" title="Expand Menu">
        <i class="fas fa-indent"></i>
    </button>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        // =========================================================
        // GLOBAL SESSION & CSRF HANDLER
        // =========================================================
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        // ═══════════════════════════════════════════════════════════════
        // FRONTEND LOGOUT HANDLER (prevent Page Expired)
        // ═══════════════════════════════════════════════════════════════
        function handleFrontendLogout() {
            console.log('[Logout] Refreshing CSRF token before logout...');
            
            // Refresh CSRF token first
            $.get('<?php echo e(route("csrf.refresh")); ?>')
                .done(function(data) {
                    if (data.token) {
                        console.log('[Logout] Got fresh token, submitting logout...');
                        // Update token in hidden form
                        $('#frontendLogoutForm input[name="_token"]').val(data.token);
                        // Update meta tag
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        
                        // Submit logout form with fresh token
                        setTimeout(() => {
                            document.getElementById('frontendLogoutForm').submit();
                        }, 100);
                    } else {
                        console.warn('[Logout] No token received, trying direct logout');
                        document.getElementById('frontendLogoutForm').submit();
                    }
                })
                .fail(function(error) {
                    console.error('[Logout] Token refresh failed:', error);
                    // Fallback: redirect to login
                    alert('Session may have expired. Redirecting to login...');
                    window.location.href = '<?php echo e(route("login")); ?>';
                });
        }

        $(document).ajaxError(function(event, xhr, settings, error) {
            if (xhr.status === 419 || xhr.status === 401) {
                window.location.href = '<?php echo e(route("login")); ?>';
            }
        });

        $(document).on('preXhr.dt', function(e, settings, data) {
            data._token = $('meta[name="csrf-token"]').attr('content');
        });

        setInterval(function() {
            $.get('<?php echo e(route("csrf.refresh")); ?>').done(function(data) {
                if (data.token) {
                    // Update meta tag
                    $('meta[name="csrf-token"]').attr('content', data.token);
                    // Update all CSRF input fields (including logout form)
                    $('input[name="_token"]').val(data.token);
                    // Update AJAX setup
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                    console.log('[CSRF] Token refreshed successfully');
                }
            }).fail(function() {
                console.warn('[CSRF] Token refresh failed - session may have expired');
            });
        }, 30 * 60 * 1000); // 30 minutes
        
        // Also refresh on user activity after idle
        let lastActivity = Date.now();
        document.addEventListener('click', () => lastActivity = Date.now());
        document.addEventListener('keypress', () => lastActivity = Date.now());
        
        setInterval(function() {
            const idleTime = Date.now() - lastActivity;
            // If idle > 20 min but < 60 min, preemptively refresh
            if (idleTime > 20 * 60 * 1000 && idleTime < 60 * 60 * 1000) {
                $.get('<?php echo e(route("csrf.refresh")); ?>').done(function(data) {
                    if (data.token) {
                        $('meta[name="csrf-token"]').attr('content', data.token);
                        $('input[name="_token"]').val(data.token);
                        $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                    }
                });
            }
        }, 5 * 60 * 1000); // Check every 5 minutes

        // Clock update
        function updateClock() {
            var now = new Date();
            var h = now.getHours().toString().padStart(2, '0');
            var m = now.getMinutes().toString().padStart(2, '0');
            var el = document.getElementById('clockTop');
            if (el) el.textContent = h + ':' + m;
        }
        setInterval(updateClock, 1000);
        updateClock();

        // =========================================================
        // SIDEBAR COLLAPSE LOGIC
        // =========================================================
        (function() {
            var sidebar      = document.querySelector('.sidebar');
            var collapseBtn  = document.getElementById('collapseSidebarBtn');
            var expandFab    = document.getElementById('expandSidebarFab');
            var collapseIcon = document.getElementById('collapseIcon');
            var collapseText = document.getElementById('collapseText');

            if (!sidebar || !collapseBtn) return;

            function applyState(collapsed, animate) {
                if (!animate) sidebar.style.transition = 'none';
                if (collapsed) {
                    sidebar.classList.add('collapsed');
                    if (expandFab) expandFab.classList.add('visible');
                } else {
                    sidebar.classList.remove('collapsed');
                    if (expandFab) expandFab.classList.remove('visible');
                }
                if (!animate) {
                    // Force reflow then restore transition
                    sidebar.offsetHeight;
                    sidebar.style.transition = '';
                }
            }

            // Restore state on load (no animation)
            var isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
            applyState(isCollapsed, false);

            // Collapse button click
            collapseBtn.addEventListener('click', function(e) {
                e.preventDefault();
                isCollapsed = true;
                localStorage.setItem('sidebarCollapsed', 'true');
                applyState(true, true);
            });

            // FAB expand button click
            if (expandFab) {
                expandFab.addEventListener('click', function() {
                    isCollapsed = false;
                    localStorage.setItem('sidebarCollapsed', 'false');
                    applyState(false, true);
                });
            }
        })();
    </script>
    <?php echo $__env->yieldContent('scripts'); ?>

    <!-- instant.page: prefetch pages on hover for faster navigation -->
    <script src="https://cdn.jsdelivr.net/npm/instant.page@5.2.0/instantpage.js" type="module"></script>

    <!-- NProgress: show loading bar when navigating -->
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

        // Also start on form submit
        document.addEventListener('submit', function() {
            NProgress.start();
        });

        // Stop NProgress when page is fully loaded
        window.addEventListener('pageshow', function() {
            NProgress.done();
        });
    </script>
</body>
</html>
<?php /**PATH G:\project\vss\idle-monitor\resources\views\frontend\layouts\app.blade.php ENDPATH**/ ?>