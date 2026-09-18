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
            --bg-light: #f8fafc;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            color: #0f172a;
            position: relative;
            overflow-x: hidden;
        }

        /* Subtle Background Decor */
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(37, 99, 235, 0.05) 0%, transparent 40%),
                radial-gradient(circle at 90% 80%, rgba(37, 99, 235, 0.05) 0%, transparent 40%);
            z-index: 0;
            pointer-events: none;
        }

        /* Main Container layout matching Image 1 */
        .page-container {
            width: 100%;
            max-width: 1360px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            position: relative;
            z-index: 1;
            margin: auto;
        }

        /* ═══════════════════════════════════════════════════════════════
           LEFT SECTION: Miner Worker Image Banner (Image 1 style)
           ═══════════════════════════════════════════════════════════════ */
        .miner-card {
            width: 320px;
            height: 560px;
            border-radius: 20px;
            background: url('{{ asset('images/miner-banner.jpg') }}') no-repeat center center;
            background-size: cover;
            position: relative;
            overflow: hidden;
            box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: flex-end;
            padding: 32px 24px;
            flex-shrink: 0;
        }

        .miner-card-overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(to top, 
                rgba(11, 23, 57, 0.95) 0%, 
                rgba(15, 34, 75, 0.6) 45%, 
                rgba(15, 34, 75, 0.15) 100%);
            z-index: 1;
        }

        .miner-card-content {
            position: relative;
            z-index: 2;
            color: #ffffff;
        }

        .miner-card-title {
            font-size: 1.55rem;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 10px;
            letter-spacing: -0.3px;
            color: #ffffff;
        }

        .miner-card-subtitle {
            font-size: 0.85rem;
            color: #cbd5e1;
            line-height: 1.5;
            margin: 0;
            font-weight: 400;
        }

        /* ═══════════════════════════════════════════════════════════════
           CENTER SECTION: Floating Login Form Card (Image 1 style)
           ═══════════════════════════════════════════════════════════════ */
        .login-card {
            width: 380px;
            height: 560px;
            background: #ffffff;
            border-radius: 20px;
            padding: 28px 32px 24px 32px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.1), 0 0 1px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        /* Partner Logos at Top of Login Card */
        .partner-logo-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 14px;
            margin-bottom: 8px;
        }

        .partner-logo-header img.logo-gpe {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .partner-logo-header img.logo-mapan {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .logo-divider {
            width: 1.5px;
            height: 24px;
            background: #cbd5e1;
            border-radius: 1px;
        }

        /* G-VAMS Logo Emblem inside Login Card */
        .card-logo-container {
            text-align: center;
            margin-bottom: 12px;
        }

        .card-logo-container img {
            max-width: 250px;
            width: 100%;
            height: auto;
            max-height: 120px;
            object-fit: contain;
        }

        /* Form Controls */
        .form-group {
            margin-bottom: 16px;
        }

        .form-label-custom {
            font-size: 0.85rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: block;
        }

        .input-group-custom {
            position: relative;
        }

        .input-group-custom i.icon-left {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            transition: color 0.2s;
        }

        .input-group-custom .form-control {
            height: 46px;
            padding-left: 42px;
            padding-right: 42px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 0.88rem;
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
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
            padding: 4px;
            transition: color 0.2s;
        }

        .input-group-custom i.icon-right:hover {
            color: var(--primary-blue);
        }

        /* Sign In Button */
        .btn-signin {
            width: 100%;
            height: 46px;
            background: var(--primary-blue);
            color: #ffffff;
            border: none;
            border-radius: 10px;
            font-size: 0.95rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            margin-top: 10px;
        }

        .btn-signin:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
        }

        .btn-signin i {
            font-size: 0.9rem;
            transition: transform 0.2s;
        }

        .btn-signin:hover i {
            transform: translateX(3px);
        }

        /* Role Access Subtitle */
        .role-badge-text {
            text-align: center;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            margin-top: 12px;
        }

        .role-badge-text i {
            color: var(--primary-blue);
            font-size: 0.82rem;
        }

        /* ═══════════════════════════════════════════════════════════════
           RIGHT SECTION: Showcase & Features Bar (Image 1 style)
           ═══════════════════════════════════════════════════════════════ */
        .showcase-section {
            flex: 1;
            height: 560px;
            background: #ffffff;
            border-radius: 20px;
            padding: 24px 32px 20px 32px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Right Header Partner Logos */
        .showcase-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 12px;
        }

        .showcase-header img.logo-gpe {
            height: 36px;
            width: auto;
            object-fit: contain;
        }

        .showcase-header img.logo-mapan {
            height: 36px;
            width: auto;
            object-fit: contain;
        }

        /* Large G-VAMS Logo Display */
        .showcase-hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
        }

        .showcase-hero img {
            max-width: 580px;
            width: 100%;
            height: auto;
            max-height: 340px;
            object-fit: contain;
            filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.05));
        }

        /* Bottom 7 Features Bar */
        .features-bar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            border-top: 1px solid #e2e8f0;
            padding-top: 18px;
            margin-top: 10px;
        }

        .feature-box {
            text-align: center;
            padding: 4px 6px;
            position: relative;
        }

        .feature-box:not(:last-child)::after {
            content: '';
            position: absolute;
            right: 0;
            top: 15%;
            height: 70%;
            width: 1px;
            background: #cbd5e1;
        }

        .feature-box-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            margin: 0 auto 6px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 15px;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        .icon-fleet { background: #2563eb; }
        .icon-speed { background: #f97316; }
        .icon-payload { background: #16a34a; }
        .icon-abuse { background: #dc2626; }
        .icon-idle { background: #9333ea; }
        .icon-equipment { background: #0891b2; }
        .icon-analytics { background: #1e3a8a; }

        .feature-box-title {
            font-size: 0.68rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            line-height: 1.25;
            letter-spacing: -0.2px;
        }

        /* Alerts */
        .alert-custom {
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.82rem;
            margin-bottom: 12px;
            border: none;
        }

        /* ═══════════════════════════════════════════════════════════════
           RESPONSIVE LAYOUT
           ═══════════════════════════════════════════════════════════════ */
        @media (max-width: 1200px) {
            .showcase-section {
                display: none;
            }
            .page-container {
                max-width: 740px;
            }
        }

        @media (max-width: 768px) {
            .miner-card {
                display: none;
            }
            .page-container {
                max-width: 420px;
            }
            .login-card {
                width: 100%;
                height: auto;
                min-height: 540px;
            }
        }
    </style>
</head>
<body>

    <div class="page-container">
        
        <!-- 1. LEFT SECTION: Miner Worker Image Banner -->
        <div class="miner-card">
            <div class="miner-card-overlay"></div>
            <div class="miner-card-content">
                <h2 class="miner-card-title">Monitor Fleet.<br>Maximize Performance.</h2>
                <p class="miner-card-subtitle">Data akurat, keputusan tepat, operasional lebih efisien.</p>
            </div>
        </div>

        <!-- 2. CENTER SECTION: Login Form Card -->
        <div class="login-card">
            <div>
                <!-- Partner Logos (GPE | MAPAN) -->
                <div class="partner-logo-header">
                    <img src="{{ asset('images/gpe-logo-transparent.png') }}" alt="GPE Logo" class="logo-gpe">
                    <div class="logo-divider"></div>
                    <img src="{{ asset('images/mapan-logo-transparent.png') }}" alt="MAPAN Logo" class="logo-mapan">
                </div>

                <!-- G-VAMS Logo -->
                <div class="card-logo-container">
                    <img src="{{ asset('images/gvams-logo-main.jpg') }}" alt="G-VAMS Vehicle Activity Monitoring System">
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

                    <button type="submit" class="btn-signin">
                        Sign In <i class="fas fa-arrow-right"></i>
                    </button>
                </form>
            </div>

            <!-- Card Subtitle Badge -->
            <div class="role-badge-text">
                <i class="fas fa-location-dot"></i> Available for Admin & Fleet Manager roles
            </div>
        </div>

        <!-- 3. RIGHT SECTION: Showcase & 7 Features Bar -->
        <div class="showcase-section">
            <!-- Header Logos -->
            <div class="showcase-header">
                <img src="{{ asset('images/gpe-logo-transparent.png') }}" alt="GPE Logo" class="logo-gpe">
                <div class="logo-divider"></div>
                <img src="{{ asset('images/mapan-logo-transparent.png') }}" alt="MAPAN Logo" class="logo-mapan">
            </div>

            <!-- Center Artwork (G-VAMS Logo Emblem) -->
            <div class="showcase-hero">
                <img src="{{ asset('images/gvams-logo-main.jpg') }}" alt="G-VAMS Vehicle Activity Monitoring System">
            </div>

            <!-- Bottom 7 Feature Icons Bar -->
            <div class="features-bar">
                <div class="feature-box">
                    <div class="feature-box-icon icon-fleet"><i class="fas fa-location-dot"></i></div>
                    <div class="feature-box-title">FLEET<br>TRACKING</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-speed"><i class="fas fa-gauge-high"></i></div>
                    <div class="feature-box-title">SPEED<br>MONITORING</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-payload"><i class="fas fa-weight-hanging"></i></div>
                    <div class="feature-box-title">PAYLOAD<br>MONITORING</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-abuse"><i class="fas fa-shield-halved"></i></div>
                    <div class="feature-box-title">ABUSE<br>OPERATION</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-idle"><i class="far fa-clock"></i></div>
                    <div class="feature-box-title">IDLE<br>MONITORING</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-equipment"><i class="fas fa-gear"></i></div>
                    <div class="feature-box-title">EQUIPMENT<br>MONITORING</div>
                </div>
                <div class="feature-box">
                    <div class="feature-box-icon icon-analytics"><i class="fas fa-chart-column"></i></div>
                    <div class="feature-box-title">PERFORMANCE<br>ANALYTICS</div>
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
    </script>
</body>
</html>
