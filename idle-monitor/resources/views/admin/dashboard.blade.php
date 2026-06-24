<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Idle Monitor System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>
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
            margin-bottom: 20px;
        }
        .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 10px 10px 0 0;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 32px;
            font-weight: bold;
            color: #667eea;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
            margin-top: 10px;
        }
        .menu-icon {
            width: 20px;
            text-align: center;
            margin-right: 10px;
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
                <a class="nav-link active" href="/admin/dashboard">
                    <span class="menu-icon"><i class="fas fa-chart-line"></i></span>Dashboard
                </a>
                <a class="nav-link" href="{{ route('admin.user.index') }}">
                    <span class="menu-icon"><i class="fas fa-users"></i></span>User Management
                </a>
                <a class="nav-link" href="{{ route('admin.device.index') }}">
                    <span class="menu-icon"><i class="fas fa-car"></i></span>Device Management
                </a>
                <a class="nav-link" href="{{ route('admin.import-log.index') }}">
                    <span class="menu-icon"><i class="fas fa-history"></i></span>Import Logs
                </a>
                <a class="nav-link" href="{{ route('admin.data-pull.index') }}">
                    <span class="menu-icon"><i class="fas fa-download"></i></span>Data Pull
                </a>
                <a class="nav-link" href="{{ route('admin.system-setting.index') }}">
                    <span class="menu-icon"><i class="fas fa-cogs"></i></span>System Settings
                </a>
            </nav>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <h2 class="mb-4"><i class="fas fa-chart-line"></i> Admin Dashboard</h2>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['total_devices'] }}</div>
                    <div class="stat-label">Total Devices</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['total_idle_today'] }}</div>
                    <div class="stat-label">Idle Today</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['active_idle'] }}</div>
                    <div class="stat-label">Active Idle</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ round($stats['avg_duration']) }}</div>
                    <div class="stat-label">Avg Duration (min)</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['total_users'] }}</div>
                    <div class="stat-label">Total Users</div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="stat-card">
                    <div class="stat-number">{{ $stats['active_users'] }}</div>
                    <div class="stat-label">Active Users</div>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Idle Per Hour (Last 24h)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="idlePerHourChart"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Idle Per Day (Last 7 days)</h5>
                    </div>
                    <div class="card-body">
                        <canvas id="idlePerDayChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Devices & Recent Alarms -->
        <div class="row">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Top 10 Devices with Idle</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Device Name</th>
                                        <th>Idle Count</th>
                                        <th>Total Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($topDevices as $device)
                                        <tr>
                                            <td><strong>{{ $device->device_name }}</strong></td>
                                            <td>{{ $device->total_idle }}</td>
                                            <td>{{ round($device->total_duration, 0) }} min</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-muted">No data available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-table"></i> Recent Import Logs</h5>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Name</th>
                                        <th>Status</th>
                                        <th>Records</th>
                                        <th>Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($importLogs as $log)
                                        <tr>
                                            <td>{{ $log->job_name }}</td>
                                            <td>
                                                @if($log->status === 'completed')
                                                    <span class="badge bg-success">Completed</span>
                                                @elseif($log->status === 'failed')
                                                    <span class="badge bg-danger">Failed</span>
                                                @else
                                                    <span class="badge bg-warning">{{ ucfirst($log->status) }}</span>
                                                @endif
                                            </td>
                                            <td>{{ $log->total_record }}</td>
                                            <td>{{ $log->finished_at ? $log->finished_at->format('H:i') : '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted">No logs available</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Idle Per Hour Chart
        const ctx1 = document.getElementById('idlePerHourChart').getContext('2d');
        new Chart(ctx1, {
            type: 'line',
            data: {
                labels: {!! json_encode($idlePerHour['hours']) !!},
                datasets: [{
                    label: 'Idle Count',
                    data: {!! json_encode($idlePerHour['counts']) !!},
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4,
                    fill: true,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        });

        // Idle Per Day Chart
        const ctx2 = document.getElementById('idlePerDayChart').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: {!! json_encode($idlePerDay['days']) !!},
                datasets: [{
                    label: 'Idle Count',
                    data: {!! json_encode($idlePerDay['counts']) !!},
                    backgroundColor: '#667eea',
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false,
                    }
                }
            }
        });
    </script>
</body>
</html>
