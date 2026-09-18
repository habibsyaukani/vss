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
            padding: 24px;
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

        /* Main Container: Full Showcase on Left + Login Card on Right */
        .page-container {
            width: 100%;
            max-width: 1360px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 28px;
            position: relative;
            z-index: 1;
            margin: auto;
        }

        /* ═══════════════════════════════════════════════════════════════
           LEFT SECTION: Full Background Showcase (Image 3)
           ═══════════════════════════════════════════════════════════════ */
        .showcase-section {
            flex: 1;
            height: 600px;
            background: #ffffff;
            border-radius: 24px;
            padding: 28px 36px 24px 36px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.06);
            border: 1px solid rgba(226, 232, 240, 0.8);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
        }

        /* Right Header Partner Logos (GPE | MAPAN) */
        .showcase-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            gap: 14px;
        }

        .showcase-header img.logo-gpe {
            height: 38px;
            width: auto;
            object-fit: contain;
        }

        .showcase-header img.logo-mapan {
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

        /* Large G-VAMS Logo Display */
        .showcase-hero {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 10px 0;
        }

        .showcase-hero img {
            max-width: 620px;
            width: 100%;
            height: auto;
            max-height: 360px;
            object-fit: contain;
            filter: drop-shadow(0 10px 25px rgba(0, 0, 0, 0.05));
        }

        /* Bottom 7 Features Bar */
        .features-bar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 4px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
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
            width: 38px;
            height: 38px;
            border-radius: 50%;
            margin: 0 auto 8px auto;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
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
            font-size: 0.7rem;
            font-weight: 700;
            color: #1e293b;
            text-transform: uppercase;
            line-height: 1.25;
            letter-spacing: -0.2px;
        }

        /* ═══════════════════════════════════════════════════════════════
           RIGHT SECTION: Floating Login Form Card (Image 2 Modified)
           No GPE or MAPAN logos inside card — G-VAMS Logo ONLY
           ═══════════════════════════════════════════════════════════════ */
        .login-card {
            width: 420px;
            height: 600px;
            background: #ffffff;
            border-radius: 24px;
            padding: 36px 36px 28px 36px;
            box-shadow: 0 20px 50px -10px rgba(0, 0, 0, 0.1), 0 0 1px rgba(0,0,0,0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            flex-shrink: 0;
            border: 1px solid rgba(226, 232, 240, 0.8);
        }

        /* G-VAMS Logo Emblem inside Login Card (Top of Card) */
        .card-logo-container {
            text-align: center;
            margin-bottom: 20px;
            margin-top: 10px;
        }

        .card-logo-container img {
            max-width: 290px;
            width: 100%;
            height: auto;
            max-height: 140px;
            object-fit: contain;
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
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.25);
            margin-top: 14px;
        }

        .btn-signin:hover {
            background: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 6px 16px rgba(37, 99, 235, 0.35);
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
            margin-top: 16px;
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
           RESPONSIVE LAYOUT
           ═══════════════════════════════════════════════════════════════ */
        @media (max-width: 1100px) {
            .showcase-section {
                display: none;
            }
            .page-container {
                justify-content: center;
                max-width: 440px;
            }
            .login-card {
                width: 100%;
                height: auto;
                min-height: 560px;
            }
        }
    </style>
</head>
<body>

    <div class="page-container">
        
        <!-- 1. LEFT SECTION: Full Background Showcase (Image 3) -->
        <div class="showcase-section">
            <!-- Header Logos (GPE | MAPAN) -->
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

        <!-- 2. RIGHT SECTION: Login Form Card (Image 2 Modified) -->
        <div class="login-card">
            <div>
                <!-- G-VAMS Logo ONLY (No GPE / MAPAN logos inside card) -->
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
