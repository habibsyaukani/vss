<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'HOWEN VSS')</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }
        /* Top Navbar */
        .navbar-top {
            background-color: #1963f2;
            height: 60px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 0 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
        }
        .navbar-brand-vss {
            color: white;
            font-weight: 700;
            font-size: 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            width: 250px;
        }
        .navbar-brand-vss i {
            margin-right: 10px;
            font-size: 24px;
        }
        .navbar-center-links {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        .navbar-center-links a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 10px 0;
            border-bottom: 2px solid transparent;
            transition: all 0.2s;
        }
        .navbar-center-links a:hover {
            color: white;
        }
        .navbar-center-links a.active {
            color: white;
            border-bottom: 2px solid #ffc107;
        }
        .navbar-right {
            display: flex;
            align-items: center;
            gap: 20px;
            color: white;
            font-size: 13px;
        }
        .badge-live {
            background-color: rgba(255, 255, 255, 0.2);
            color: #00ff88;
            padding: 4px 10px;
            border-radius: 12px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .badge-live::before {
            content: '';
            display: inline-block;
            width: 8px;
            height: 8px;
            background-color: #00ff88;
            border-radius: 50%;
        }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
        }
        .user-profile img {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: white;
        }
        
        /* Layout Wrapper */
        .wrapper {
            display: flex;
            margin-top: 60px;
            height: calc(100vh - 60px);
        }

        /* Sidebar */
        .sidebar {
            width: 280px;
            background-color: #ffffff;
            border-right: 1px solid #eaedf2;
            height: 100%;
            overflow-y: auto;
            flex-shrink: 0;
        }
        
        /* Main Content */
        .main-content {
            flex-grow: 1;
            padding: 20px 30px;
            overflow-y: auto;
            background-color: #f8f9fc;
        }

        /* Common Utility Classes */
        .card-custom {
            background: white;
            border-radius: 8px;
            border: 1px solid #eaedf2;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        
        @yield('styles')
    </style>
</head>
<body>
    <!-- Top Navbar -->
    <div class="navbar-top">
        <!-- Brand -->
        <a href="{{ route('frontend.dashboard') }}" class="navbar-brand-vss">
            <i class="fas fa-bolt"></i> HOWEN VSS
        </a>

        <!-- Center Links -->
        <div class="navbar-center-links">
            <a href="{{ route('frontend.dashboard') }}" class="{{ Route::currentRouteName() === 'frontend.dashboard' ? 'active' : '' }}">
                <i class="fas fa-border-all"></i> Dashboard
            </a>
            <a href="{{ route('frontend.idle-alarm.index') }}" class="{{ str_contains(Route::currentRouteName(), 'idle-alarm') ? 'active' : '' }}">
                <i class="fas fa-bolt"></i> Idle
            </a>
            <a href="{{ route('frontend.speed.index') }}" class="{{ Route::currentRouteName() === 'frontend.speed.index' ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> Speed
            </a>
            <a href="{{ route('frontend.speed-performance.index') }}" class="{{ Route::currentRouteName() === 'frontend.speed-performance.index' ? 'active' : '' }}">
                <i class="fas fa-chart-line"></i> Speed Performance
            </a>
        </div>

        <!-- Right Side -->
        <div class="navbar-right">
            <div><i class="far fa-calendar-alt me-1"></i> {{ date('d M Y') }}</div>
            <div><i class="far fa-clock me-1"></i> <span id="clockTop"></span> WITA</div>
            <div>VSS Fleet Monitoring</div>
            
            <div class="dropdown">
                <div class="user-profile" data-bs-toggle="dropdown">
                    <div class="d-flex align-items-center justify-content-center text-primary fw-bold" style="width:32px; height:32px; border-radius:50%; background:white;">
                        <i class="fas fa-user"></i>
                    </div>
                    <span>{{ explode(' ', auth()->user()->name)[0] }} <i class="fas fa-caret-down ms-1"></i></span>
                </div>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0 mt-2">
                    <li>
                        <form action="{{ route('frontend.logout') }}" method="POST" class="m-0 p-0">
                            @csrf
                            <button class="dropdown-item text-danger" type="submit"><i class="fas fa-sign-out-alt me-2"></i> Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Wrapper -->
    <div class="wrapper">
        <!-- Sidebar -->
        <aside class="sidebar p-0">
            @yield('sidebar')
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            @yield('content')
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <script>
        // =========================================================
        // GLOBAL SESSION & CSRF HANDLER
        // Mencegah infinite loading / page expired dari semua halaman
        // =========================================================

        // 1. Set CSRF token pada semua request AJAX jQuery
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        // 2. Tangkap semua error AJAX secara global
        $(document).ajaxError(function(event, xhr, settings, error) {
            if (xhr.status === 419) {
                // Sesi/CSRF expired — redirect ke halaman login
                window.location.href = '{{ route("login") }}';
            } else if (xhr.status === 401) {
                // Unauthorized — redirect ke login
                window.location.href = '{{ route("login") }}';
            }
        });

        // 3. Tangkap respons JSON dari DataTables yang berisi redirect 419
        $(document).on('preXhr.dt', function(e, settings, data) {
            // Inject fresh CSRF token ke setiap request DataTables
            data._token = $('meta[name="csrf-token"]').attr('content');
        });

        // 4. Auto-refresh CSRF token setiap 30 menit agar tidak expired
        setInterval(function() {
            $.get('{{ route("csrf.refresh") }}').done(function(data) {
                if (data.token) {
                    $('meta[name="csrf-token"]').attr('content', data.token);
                    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': data.token } });
                }
            });
        }, 30 * 60 * 1000); // setiap 30 menit

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
    </script>
    @yield('scripts')
</body>
</html>
