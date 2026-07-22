<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/gpe-logo-transparent.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/gpe-logo-transparent.png') }}">
    <title>Admin Portal - Idle Monitor System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: linear-gradient(135deg, rgba(10, 14, 39, 0.82) 0%, rgba(22, 33, 62, 0.82) 100%), url('{{ asset('images/bglogin.jpg') }}') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        /* Animated background waves */
        body::before {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 300px;
            background: linear-gradient(90deg, rgba(29, 78, 216, 0.1) 0%, rgba(59, 130, 246, 0.1) 100%);
            border-radius: 50% 50% 0 0;
            transform: translateY(50%);
        }

        body::after {
            content: '';
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(29, 78, 216, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .login-wrapper {
            display: flex;
            max-width: 1400px;
            width: 95%;
            gap: 40px;
            position: relative;
            z-index: 1;
        }

        /* LEFT SIDE - Dashboard Preview */
        .left-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            gap: 30px;
        }

        .dashboard-preview {
            background: linear-gradient(135deg, #1d4ed8 0%, #3b82f6 100%);
            border-radius: 20px;
            padding: 40px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(29, 78, 216, 0.4);
        }

        .dashboard-preview::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
        }

        .security-badge {
            background: rgba(255, 255, 255, 0.2);
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .security-badge i {
            font-size: 28px;
            color: white;
        }

        .dashboard-screen {
            background: rgba(10, 14, 39, 0.6);
            border-radius: 15px;
            padding: 30px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .chart-preview {
            display: flex;
            gap: 15px;
            align-items: flex-end;
            margin-bottom: 20px;
        }

        .chart-bar {
            flex: 1;
            background: linear-gradient(to top, #3b82f6, #60a5fa);
            border-radius: 5px;
            min-height: 40px;
            animation: pulse 2s infinite;
        }

        .chart-bar:nth-child(1) { height: 60px; animation-delay: 0s; }
        .chart-bar:nth-child(2) { height: 90px; animation-delay: 0.2s; }
        .chart-bar:nth-child(3) { height: 70px; animation-delay: 0.4s; }
        .chart-bar:nth-child(4) { height: 100px; animation-delay: 0.6s; }
        .chart-bar:nth-child(5) { height: 80px; animation-delay: 0.8s; }

        @keyframes pulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }

        .dashboard-icons {
            display: flex;
            gap: 15px;
            margin-top: 20px;
        }

        .icon-box {
            background: rgba(29, 78, 216, 0.3);
            width: 45px;
            height: 45px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }

        .secure-access {
            text-align: center;
            color: rgba(255, 255, 255, 0.9);
        }

        .secure-access-icon {
            background: rgba(29, 78, 216, 0.2);
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            border: 1px solid rgba(29, 78, 216, 0.3);
        }

        .secure-access-icon i {
            font-size: 24px;
            color: #60a5fa;
        }

        .secure-access h3 {
            font-size: 20px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .secure-access p {
            font-size: 14px;
            opacity: 0.7;
        }

        /* CENTER - Login Card */
        .login-card {
            background: white;
            border-radius: 25px;
            padding: 30px 45px 45px 45px;
            width: 450px;
            box-shadow: 0 30px 90px rgba(0, 0, 0, 0.3);
            position: relative;
        }

        .login-logo-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 16px;
            margin-bottom: 8px;
        }
        .login-logo-header img.logo-mapan {
            height: 46px;
            max-height: 46px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,0.12));
        }
        .login-logo-header img.logo-gpe {
            height: 46px;
            max-height: 46px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 3px 6px rgba(0,0,0,0.12));
        }
        .logo-divider {
            height: 28px;
            width: 1.5px;
            background: #e2e8f0;
            border-radius: 1px;
        }
        .brand-co-text {
            text-align: center;
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 1.2px;
            text-transform: uppercase;
            margin-bottom: 12px;
        }

        .lock-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            background: transparent;
            border: none;
            box-shadow: none;
            padding: 0;
        }

        .lock-icon img {
            max-width: 200px;
            max-height: 75px;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .login-card h1 {
            text-align: center;
            font-size: 32px;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 10px;
        }

        .login-card h1::after {
            content: '';
            display: block;
            width: 60px;
            height: 4px;
            background: linear-gradient(90deg, #1d4ed8, #0ea5e9);
            margin: 15px auto;
            border-radius: 2px;
        }

        .form-group {
            margin-bottom: 25px;
        }

        .form-label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            color: #475569;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .form-label i {
            color: #1d4ed8;
        }

        .input-wrapper {
            position: relative;
        }

        .form-control {
            width: 100%;
            padding: 14px 18px;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            background: #f8fafc;
        }

        .form-control:focus {
            outline: none;
            border-color: #1d4ed8;
            background: white;
            box-shadow: 0 0 0 4px rgba(29, 78, 216, 0.1);
        }

        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #94a3b8;
            transition: color 0.3s;
        }

        .toggle-password:hover {
            color: #1d4ed8;
        }

        .form-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #64748b;
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .forgot-password {
            font-size: 14px;
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s;
        }

        .forgot-password:hover {
            color: #1e40af;
        }

        .btn-signin {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            box-shadow: 0 10px 25px rgba(29, 78, 216, 0.3);
        }

        .btn-signin:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(29, 78, 216, 0.4);
        }

        /* RIGHT SIDE - Features */
        .right-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 20px;
            justify-content: center;
        }

        .feature-card {
            background: rgba(30, 41, 59, 0.5);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(148, 163, 184, 0.1);
            border-radius: 15px;
            padding: 20px;
            display: flex;
            gap: 15px;
            align-items: center;
            transition: all 0.3s;
        }

        .feature-card:hover {
            background: rgba(30, 41, 59, 0.7);
            border-color: rgba(29, 78, 216, 0.3);
            transform: translateX(10px);
        }

        .feature-icon {
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, #1d4ed8 0%, #1e40af 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .feature-icon i {
            font-size: 24px;
            color: white;
        }

        .feature-content h4 {
            font-size: 16px;
            font-weight: 600;
            color: white;
            margin-bottom: 5px;
        }

        .feature-content p {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            margin: 0;
        }

        /* Footer */
        .login-footer {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            color: rgba(255, 255, 255, 0.5);
            font-size: 13px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Alert */
        .alert {
            border-radius: 12px;
            padding: 14px 18px;
            margin-bottom: 20px;
            border: none;
            font-size: 14px;
        }

        .alert-danger {
            background: #fef2f2;
            color: #991b1b;
            border-left: 4px solid #dc2626;
        }

        .alert-success {
            background: #f0fdf4;
            color: #166534;
            border-left: 4px solid #22c55e;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .login-wrapper {
                flex-direction: column;
                max-width: 500px;
            }

            .left-section,
            .right-section {
                display: none;
            }

            .login-card {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            .login-card {
                padding: 35px 25px;
            }

            .login-card h1 {
                font-size: 26px;
            }
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <!-- LEFT SECTION -->
        <div class="left-section">
            <div class="dashboard-preview">
                <div class="security-badge">
                    <i class="fas fa-shield-check"></i>
                </div>
                
                <div class="dashboard-screen">
                    <div class="chart-preview">
                        <div class="chart-bar"></div>
                        <div class="chart-bar"></div>
                        <div class="chart-bar"></div>
                        <div class="chart-bar"></div>
                        <div class="chart-bar"></div>
                    </div>
                    
                    <div class="dashboard-icons">
                        <div class="icon-box"><i class="fas fa-home"></i></div>
                        <div class="icon-box"><i class="fas fa-chart-line"></i></div>
                        <div class="icon-box"><i class="fas fa-cog"></i></div>
                        <div class="icon-box"><i class="fas fa-chart-pie"></i></div>
                    </div>
                </div>
            </div>

            <div class="secure-access">
                <div class="secure-access-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <h3>Secure Access</h3>
                <p>Your data is protected<br>and access is secure</p>
            </div>
        </div>

        <!-- CENTER - LOGIN CARD -->
        <div class="login-card">
            <div class="login-logo-header">
                <img src="{{ asset('images/gpe-logo-transparent.png') }}" alt="GPE Logo" class="logo-gpe">
                <div class="logo-divider"></div>
                <img src="{{ asset('images/mapan-logo-transparent.png') }}" alt="MAPAN Logo" class="logo-mapan">
            </div>
            
            <h1>Admin Portal</h1>

            @if ($errors->any())
                <div class="alert alert-danger">
                    @foreach ($errors->all() as $error)
                        <div>{{ $error }}</div>
                    @endforeach
                </div>
            @endif

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <form action="{{ route('admin.login') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-user"></i>
                        Username
                    </label>
                    <input type="text" name="username" class="form-control" 
                           placeholder="Enter your username" 
                           value="{{ old('username') }}" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <i class="fas fa-lock"></i>
                        Password
                    </label>
                    <div class="input-wrapper">
                        <input type="password" name="password" id="password" class="form-control" 
                               placeholder="Enter your password" required>
                        <i class="fas fa-eye toggle-password" onclick="togglePassword()"></i>
                    </div>
                </div>

                <div class="form-footer">
                    <label class="remember-me">
                        <input type="checkbox" name="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="btn-signin">
                    <i class="fas fa-sign-in-alt"></i>
                    Sign In to Admin Panel
                </button>
            </form>
        </div>

        <!-- RIGHT SECTION -->
        <div class="right-section">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="feature-content">
                    <h4>Real-time Monitoring</h4>
                    <p>Track performance and activities in real time</p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-truck"></i>
                </div>
                <div class="feature-content">
                    <h4>Fleet Management</h4>
                    <p>Manage and optimize your entire fleet</p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <div class="feature-content">
                    <h4>Performance Analytics</h4>
                    <p>Get insights and make data-driven decisions</p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-file-alt"></i>
                </div>
                <div class="feature-content">
                    <h4>Data Reports</h4>
                    <p>Generate detailed reports and export data</p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-cog"></i>
                </div>
                <div class="feature-content">
                    <h4>Equipment Status</h4>
                    <p>Monitor equipment health and status</p>
                </div>
            </div>

            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-content">
                    <h4>Safety Monitoring</h4>
                    <p>Ensure safety compliance and risk management</p>
                </div>
            </div>
        </div>
    </div>

    <div class="login-footer">
        <i class="fas fa-shield-check"></i>
        <span>© 2025. All rights reserved.</span>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script>
        // ═══════════════════════════════════════════════════════════════
        // AUTO-REFRESH CSRF TOKEN ON LOGIN PAGE (prevent Page Expired)
        // ═══════════════════════════════════════════════════════════════
        $(document).ready(function() {
            // Auto-refresh CSRF token every 30 minutes
            setInterval(function() {
                $.ajax({
                    url: '/refresh-csrf',
                    method: 'GET',
                    success: function(data) {
                        if (data.token) {
                            // Update meta tag
                            $('meta[name="csrf-token"]').attr('content', data.token);
                            // Update form token
                            $('input[name="_token"]').val(data.token);
                            console.log('[Login] CSRF token refreshed successfully');
                        }
                    },
                    error: function() {
                        console.warn('[Login] CSRF token refresh failed');
                    }
                });
            }, 30 * 60 * 1000); // 30 minutes
            
            // Also refresh before form submit to ensure token is fresh
            $('form').on('submit', function(e) {
                e.preventDefault();
                const form = this;
                
                console.log('[Login] Refreshing CSRF token before submit...');
                
                $.ajax({
                    url: '/refresh-csrf',
                    method: 'GET',
                    success: function(data) {
                        if (data.token) {
                            // Update form token with fresh one
                            $(form).find('input[name="_token"]').val(data.token);
                            console.log('[Login] Fresh token obtained, submitting form...');
                            
                            // Submit form with fresh token
                            setTimeout(() => {
                                form.submit();
                            }, 100);
                        } else {
                            // No token received, submit anyway
                            form.submit();
                        }
                    },
                    error: function() {
                        console.warn('[Login] Token refresh failed, submitting anyway...');
                        // Submit anyway
                        form.submit();
                    }
                });
            });
        });
        
        // Fix "Page Expired" error after logout by reloading page once
        window.addEventListener('DOMContentLoaded', function() {
            // Check if we just came from logout (has success message)
            const hasSuccessMessage = document.querySelector('.alert-success');
            const hasReloaded = sessionStorage.getItem('login_page_reloaded');
            
            // If coming from logout and haven't reloaded yet, reload once to get fresh CSRF token
            if (hasSuccessMessage && !hasReloaded) {
                sessionStorage.setItem('login_page_reloaded', '1');
                location.reload();
            }
            
            // Clear the reload flag when user starts typing (fresh login attempt)
            const usernameInput = document.querySelector('input[name="username"]');
            if (usernameInput) {
                usernameInput.addEventListener('focus', function() {
                    sessionStorage.removeItem('login_page_reloaded');
                });
            }
        });

        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleIcon = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleIcon.classList.remove('fa-eye');
                toggleIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                toggleIcon.classList.remove('fa-eye-slash');
                toggleIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
