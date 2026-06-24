<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title') - Idle Monitor Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css?v={{ time() }}" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css?v={{ time() }}" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css?v={{ time() }}" rel="stylesheet">
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .navbar-brand {
            font-weight: bold;
            font-size: 20px;
            color: white !important;
        }
        .sidebar {
            background: white;
            height: 100vh;
            box-shadow: 2px 0 4px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 250px;
            overflow-y: auto;
            z-index: 10;
        }
        .sidebar .nav-link {
            color: #333;
            border-left: 3px solid transparent;
            transition: all 0.3s;
            padding: 12px 20px;
            font-size: 14px;
        }
        .sidebar .nav-link:hover {
            background: #f0f0f0;
            border-left-color: #667eea;
        }
        .sidebar .nav-link.active {
            background: #f0f0f0;
            border-left-color: #667eea;
            color: #667eea;
            font-weight: 600;
        }
        .main-content {
            margin-left: 250px;
            margin-top: 70px;
            padding: 30px;
        }
        .card {
            border: none;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
            border: none;
        }
        .menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }
        .btn-primary:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            opacity: 0.9;
        }
        /* Floating Data Pull Button */
        .floating-data-pull {
            position: fixed;
            bottom: 30px;
            right: 30px;
            z-index: 9999;
        }
        .floating-data-pull .btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            transition: all 0.3s;
        }
        .floating-data-pull .btn:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        .floating-data-pull .btn-label {
            position: absolute;
            right: 70px;
            background: white;
            padding: 8px 15px;
            border-radius: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.2);
            white-space: nowrap;
            font-size: 14px;
            font-weight: 600;
            color: #667eea;
            display: none;
        }
        .floating-data-pull:hover .btn-label {
            display: block;
        }
        @media (max-width: 768px) {
            .sidebar {
                width: 0;
                transition: all 0.3s;
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin/dashboard">
                <i class="fas fa-map-marker-alt"></i> Idle Monitor Admin
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle"></i> {{ auth()->user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                            <li><a class="dropdown-item" href="#">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('admin.logout') }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button class="dropdown-item" type="submit">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3">
            <h5 class="text-center text-muted mt-3 mb-4">MENU</h5>
            <nav class="nav flex-column">
                <a class="nav-link {{ Route::currentRouteName() === 'admin.dashboard' ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                    <span class="menu-icon"><i class="fas fa-chart-line"></i></span>Dashboard
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.user.index' ? 'active' : '' }}" href="{{ route('admin.user.index') }}">
                    <span class="menu-icon"><i class="fas fa-users"></i></span>User Management
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.device.index' ? 'active' : '' }}" href="{{ route('admin.device.index') }}">
                    <span class="menu-icon"><i class="fas fa-car"></i></span>Device Management
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.import-log.index' ? 'active' : '' }}" href="{{ route('admin.import-log.index') }}">
                    <span class="menu-icon"><i class="fas fa-history"></i></span>Import Logs
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.data-pull.index' ? 'active' : '' }}" href="{{ route('admin.data-pull.index') }}">
                    <span class="menu-icon"><i class="fas fa-download"></i></span>Data Pull (Idle Alarm)
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.gps-track-pull.index' ? 'active' : '' }}" href="{{ route('admin.gps-track-pull.index') }}">
                    <span class="menu-icon"><i class="fas fa-map-marked-alt"></i></span>GPS Track Pull
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.system-control.index' ? 'active' : '' }}" href="{{ route('admin.system-control.index') }}">
                    <span class="menu-icon"><i class="fas fa-play-circle"></i></span>System Control
                </a>
                <a class="nav-link {{ Route::currentRouteName() === 'admin.system-setting.index' ? 'active' : '' }}" href="{{ route('admin.system-setting.index') }}">
                    <span class="menu-icon"><i class="fas fa-cogs"></i></span>System Settings
                </a>
            </nav>
        </div>
    </div>
    <!-- Menu updated: {{ now() }} -->

    <!-- Main Content -->
    <div class="main-content">
        @yield('content')
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
    
    <script>
        // Setup AJAX CSRF Token
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
    </script>
    
    @stack('scripts')
</body>
</html>
