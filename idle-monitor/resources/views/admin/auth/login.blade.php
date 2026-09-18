<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/png" href="{{ asset('images/gpe-logo-transparent.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('images/gpe-logo-transparent.png') }}">
    <title>G-VAMS - GPE Vehicle Activity Monitoring System (Admin)</title>
    
    <!-- Bootstrap & FontAwesome & Google Fonts -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-blue: #2563eb;
            --primary-hover: #1d4ed8;
            --dark-navy: #0f172a;
            --text-muted: #64748b;
            --border-color: #e2e8f0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background: url('{{ asset('images/bglogin.png') }}') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            width: 100vw;
            position: relative;
            overflow-x: hidden;
            color: #ffffff;
        }

        /* Gradient Overlay for high text readability & contrast */
        .page-overlay {
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(135deg, 
                rgba(10, 25, 47, 0.72) 0%, 
                rgba(15, 34, 75, 0.45) 50%, 
                rgba(10, 25, 47, 0.68) 100%);
            z-index: 1;
            pointer-events: none;
        }

        .main-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 30px 50px 100px 50px;
        }

        /* ═══════════════════════════════════════════════════════════════
           1. TOP LEFT LOGOS (GPE | MAPAN)
           ═══════════════════════════════════════════════════════════════ */
        .top-header-logos {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .top-header-logos img.logo-gpe {
            height: 44px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
        }

        .top-header-logos img.logo-mapan {
            height: 44px;
            width: auto;
            object-fit: contain;
            filter: drop-shadow(0 2px 6px rgba(0,0,0,0.3));
        }

        .logo-divider {
            width: 1.5px;
            height: 28px;
            background: rgba(255, 255, 255, 0.4);
            border-radius: 1px;
        }

        /* ═══════════════════════════════════════════════════════════════
           2. CENTER-LEFT CONTENT AREA (Hero G-VAMS Logo & Subtitle)
           ═══════════════════════════════════════════════════════════════ */
        .content-body {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: auto;
            margin-bottom: auto;
            gap: 40px;
            padding: 20px 0;
        }

        .hero-left-section {
            max-width: 580px;
            color: #ffffff;
        }

        .hero-logo-img {
            max-width: 480px;
            width: 100%;
            height: auto;
            object-fit: contain;
            filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.4));
            margin-bottom: 20px;
            background: rgba(255, 255, 255, 0.85);
            padding: 16px 24px;
            border-radius: 16px;
            backdrop-filter: blur(8px);
        }

        .hero-desc-text {
            font-size: 1.05rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.95);
            font-weight: 500;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
            margin: 0;
            max-width: 540px;
        }

        /* ═══════════════════════════════════════════════════════════════
           3. FLOATING RIGHT LOGIN CARD (White Card)
           ═══════════════════════════════════════════════════════════════ */
        .login-card-wrapper {
            width: 420px;
            flex-shrink: 0;
        }

        .login-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 36px 40px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, 0.4);
            color: #0f172a;
            border: 1px solid rgba(255, 255, 255, 0.9);
        }

        .card-logo-container {
            text-align: center;
            margin-bottom: 20px;
        }

        .card-logo-container img {
            max-width: 280px;
            width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: contain;
        }

        .card-title-accent {
            width: 40px;
            height: 3px;
            background: var(--primary-blue);
            margin: 8px auto 0 auto;
            border-radius: 2px;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 18px;
        }

        .form-label-custom {
            font-size: 0.88rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 8px;
            display: block;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom i.icon-left {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            transition: color 0.2s;
        }

        .input-group-custom .form-control {
            height: 48px;
            padding-left: 44px;
            padding-right: 44px;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            font-size: 0.9rem;
            background: #f8fafc;
            color: #0f172a;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .input-group-custom .form-control:focus {
            background: #ffffff;
            border-color: var(--primary-blue);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
            outline: none;
        }

        .input-group-custom .form-control:focus ~ i.icon-left {
            color: var(--primary-blue);
        }

        .input-group-custom i.icon-right {
            position: absolute;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 15px;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s;
        }

        .input-group-custom i.icon-right:hover {
            color: var(--primary-blue);
        }

        /* Form Extras (Remember me & Forgot password) */
        .form-extras {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }

        .remember-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            cursor: pointer;
        }

        .forgot-link {
            color: var(--primary-blue);
            text-decoration: none;
            font-weight: 600;
        }

        .forgot-link:hover {
            text-decoration: underline;
        }

        /* Sign In Button */
        .btn-signin {
            width: 100%;
            height: 48px;
            background: var(--primary-blue);
            color: #ffffff;
            border: none;
            border-radius: 12px;
            font-size: 0.98rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 14px rgba(37, 99, 235, 0.3);
        }

        .btn-signin:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 18px rgba(37, 99, 235, 0.4);
        }

        .btn-signin i {
            font-size: 0.95rem;
            transition: transform 0.2s;
        }

        .btn-signin:hover i {
            transform: translateX(3px);
        }

        /* Role Access Subtitle */
        .role-badge-text {
            text-align: center;
            font-size: 0.82rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 20px;
        }

        .role-badge-text i {
            color: var(--primary-blue);
            font-size: 0.85rem;
        }

        /* Alerts */
        .alert-custom {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.85rem;
            margin-bottom: 14px;
            border: none;
        }

        /* ═══════════════════════════════════════════════════════════════
           4. FLOATING GLASSBAR FOOTER AT BOTTOM
           ═══════════════════════════════════════════════════════════════ */
        .footer-glassbar {
            position: fixed;
            bottom: 20px;
            left: 50px;
            right: 50px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 20;
            pointer-events: auto;
        }

        .glass-pill-left {
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 18px;
            padding: 10px 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            gap: 22px;
        }

        .feature-item-pill {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            position: relative;
        }

        .feature-item-pill:not(:last-child)::after {
            content: '';
            position: absolute;
            right: -11px;
            top: 15%;
            height: 70%;
            width: 1px;
            background: rgba(255, 255, 255, 0.15);
        }

        .feature-icon-circle {
            width: 34px;
            height: 34px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            color: #ffffff;
            margin-bottom: 4px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.2);
        }

        .icon-fleet { background: #2563eb; }
        .icon-speed { background: #f97316; }
        .icon-payload { background: #16a34a; }
        .icon-abuse { background: #dc2626; }
        .icon-idle { background: #9333ea; }
        .icon-equipment { background: #0891b2; }
        .icon-analytics { background: #1e3a8a; }

        .feature-text-label {
            font-size: 0.65rem;
            font-weight: 700;
            color: #ffffff;
            text-transform: uppercase;
            line-height: 1.2;
            letter-spacing: -0.2px;
        }

        .glass-pill-right {
            background: rgba(15, 23, 42, 0.78);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border-radius: 18px;
            padding: 10px 24px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
            display: flex;
            align-items: center;
            gap: 24px;
            color: #ffffff;
        }

        .footer-secure-item {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .footer-secure-item i {
            font-size: 1.4rem;
            color: #94a3b8;
        }

        .footer-secure-text h6 {
            font-size: 0.78rem;
            font-weight: 700;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .footer-secure-text p {
            font-size: 0.7rem;
            color: #94a3b8;
            margin: 0;
        }

        .footer-time-item {
            display: flex;
            align-items: center;
            gap: 10px;
            border-left: 1px solid rgba(255, 255, 255, 0.15);
            padding-left: 20px;
        }

        .footer-time-item i {
            font-size: 1.2rem;
            color: #94a3b8;
        }

        .footer-time-text strong {
            display: block;
            font-size: 0.85rem;
            font-weight: 600;
            color: #ffffff;
        }

        /* ═══════════════════════════════════════════════════════════════
           RESPONSIVE LAYOUT
           ═══════════════════════════════════════════════════════════════ */
        @media (max-width: 1200px) {
            .content-body {
                flex-direction: column;
                justify-content: center;
            }
            .hero-left-section {
                text-align: center;
                max-width: 100%;
            }
            .hero-desc-text {
                margin: 0 auto;
            }
            .login-card-wrapper {
                width: 100%;
                max-width: 420px;
            }
            .footer-glassbar {
                flex-direction: column;
                gap: 12px;
                left: 20px;
                right: 20px;
                bottom: 15px;
            }
            .main-wrapper {
                padding-bottom: 160px;
            }
        }
    </style>
</head>
<body>

    <div class="page-overlay"></div>

    <div class="main-wrapper">
        
        <!-- 1. TOP HEADER LOGOS (GPE | MAPAN) -->
        <div class="top-header-logos">
            <img src="{{ asset('images/gpe-logo-transparent.png') }}" alt="GPE Logo" class="logo-gpe">
            <div class="logo-divider"></div>
            <img src="{{ asset('images/mapan-logo-transparent.png') }}" alt="MAPAN Logo" class="logo-mapan">
        </div>

        <!-- 2. MAIN CONTENT BODY -->
        <div class="content-body">
            
            <!-- Left Hero Section: G-VAMS Logo & Slogan -->
            <div class="hero-left-section">
                <img src="{{ asset('images/gvams-logo-main.jpg') }}" alt="G-VAMS Vehicle Activity Monitoring System" class="hero-logo-img">
                <p class="hero-desc-text">
                    Sistem monitoring kendaraan terintegrasi untuk memantau kinerja dan aktivitas armada secara real-time, akurat, dan efisien.
                </p>
            </div>

            <!-- Right Floating Login Card -->
            <div class="login-card-wrapper">
                <div class="login-card">
                    <div>
                        <!-- G-VAMS Logo inside card -->
                        <div class="card-logo-container">
                            <img src="{{ asset('images/gvams-logo-main.jpg') }}" alt="G-VAMS Vehicle Activity Monitoring System">
                            <div class="card-title-accent"></div>
                        </div>

                        <!-- Session Alerts -->
                        @if ($errors->any())
                            <div class="alert alert-danger alert-custom bg-danger text-white">
                                @foreach ($errors->all() as $error)
                                    <div><i class="fas fa-exclamation-circle me-1"></i> {{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        @if (session('error'))
                            <div class="alert alert-danger alert-custom bg-danger text-white">
                                <i class="fas fa-exclamation-circle me-1"></i> {{ session('error') }}
                            </div>
                        @endif

                        @if (session('success'))
                            <div class="alert alert-success alert-custom bg-success text-white">
                                <i class="fas fa-check-circle me-1"></i> {{ session('success') }}
                            </div>
                        @endif

                        <!-- Admin Login Form -->
                        <form action="{{ route('admin.login') }}" method="POST">
                            @csrf
                            
                            <div class="form-group">
                                <label class="form-label-custom">Username</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-user icon-left"></i>
                                    <input type="text" name="username" class="form-control" placeholder="Enter your username" value="{{ old('username') }}" required autofocus>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label-custom">Password</label>
                                <div class="input-group-custom">
                                    <i class="fas fa-lock icon-left"></i>
                                    <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                                    <i class="far fa-eye icon-right" id="togglePassword"></i>
                                </div>
                            </div>

                            <div class="form-extras">
                                <label class="remember-checkbox">
                                    <input type="checkbox" name="remember" class="form-check-input me-1"> Remember me
                                </label>
                                <a href="#" class="forgot-link">Forgot password?</a>
                            </div>

                            <button type="submit" class="btn-signin">
                                Sign In <i class="fas fa-arrow-right"></i>
                            </button>
                        </form>
                    </div>

                    <!-- Role Badge -->
                    <div class="role-badge-text">
                        <i class="fas fa-shield-alt"></i> Available for Admin & Fleet Manager roles
                    </div>
                </div>
            </div>

        </div>

    </div>

    <!-- 3. FLOATING GLASSBAR FOOTER AT BOTTOM -->
    <div class="footer-glassbar">
        <!-- Left Glass Pill: 7 Features Icons Bar -->
        <div class="glass-pill-left">
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-fleet"><i class="fas fa-location-dot"></i></div>
                <div class="feature-text-label">FLEET<br>TRACKING</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-speed"><i class="fas fa-gauge-high"></i></div>
                <div class="feature-text-label">SPEED<br>MONITORING</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-payload"><i class="fas fa-weight-hanging"></i></div>
                <div class="feature-text-label">PAYLOAD<br>MONITORING</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-abuse"><i class="fas fa-shield-halved"></i></div>
                <div class="feature-text-label">ABUSE<br>OPERATION</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-idle"><i class="far fa-clock"></i></div>
                <div class="feature-text-label">IDLE<br>MONITORING</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-equipment"><i class="fas fa-gear"></i></div>
                <div class="feature-text-label">EQUIPMENT<br>MONITORING</div>
            </div>
            <div class="feature-item-pill">
                <div class="feature-icon-circle icon-analytics"><i class="fas fa-chart-column"></i></div>
                <div class="feature-text-label">PERFORMANCE<br>ANALYTICS</div>
            </div>
        </div>

        <!-- Right Glass Pill: Secure Access & System Clock -->
        <div class="glass-pill-right">
            <div class="footer-secure-item">
                <i class="fas fa-lock"></i>
                <div class="footer-secure-text">
                    <h6>SECURE ACCESS</h6>
                    <p>Data akurat. Keputusan tepat. Operasional lebih efisien.</p>
                </div>
            </div>
            <div class="footer-time-item">
                <i class="far fa-clock"></i>
                <div class="footer-time-text">
                    <strong id="systemTime">Loading...</strong>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Auto-refresh CSRF token every 15 minutes to prevent session expiration
            setInterval(function() {
                $.ajax({
                    url: '{{ route("csrf.refresh") }}',
                    method: 'GET',
                    success: function(data) {
                        if (data && data.token) {
                            $('meta[name="csrf-token"]').attr('content', data.token);
                            $('input[name="_token"]').val(data.token);
                        }
                    }
                });
            }, 15 * 60 * 1000);
        });

        // Toggle password visibility
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                this.classList.toggle('fa-eye');
                this.classList.toggle('fa-eye-slash');
            });
        }

        // Live System Clock
        function updateClock() {
            const now = new Date();
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            const clockEl = document.getElementById('systemTime');
            if (clockEl) {
                clockEl.textContent = dateStr + ' - ' + timeStr + ' WITA';
            }
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
