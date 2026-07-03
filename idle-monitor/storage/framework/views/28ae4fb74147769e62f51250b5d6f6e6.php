<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Fleet Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            background: url('<?php echo e(asset('images/bglogin.png')); ?>') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 0;
            position: relative;
            color: #fff;
        }
        
        /* Dark overlay for better text readability */
        .overlay {
            position: absolute;
            top: 0; left: 0; right: 0; bottom: 0;
            background: linear-gradient(90deg, rgba(15, 23, 42, 0.9) 0%, rgba(15, 23, 42, 0.5) 40%, rgba(15, 23, 42, 0.1) 100%);
            z-index: 1;
        }

        .main-wrapper {
            position: relative;
            z-index: 2;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Header Section */
        .page-header {
            padding: 40px 60px;
        }
        .header-title-container {
            border-left: 4px solid #3b82f6;
            padding-left: 20px;
        }
        .header-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
            letter-spacing: -0.5px;
        }
        .header-subtitle {
            font-size: 1.1rem;
            color: #cbd5e1;
            margin-top: 5px;
        }

        /* Content Section */
        .content-area {
            flex-grow: 1;
            display: flex;
            padding: 0 80px;
            align-items: center;
            justify-content: space-between;
        }

        /* Features List */
        .features-list {
            max-width: 420px;
        }
        .feature-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .feature-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            border: 1px solid rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 18px;
            font-size: 16px;
            flex-shrink: 0;
            background: rgba(15, 23, 42, 0.4);
            backdrop-filter: blur(4px);
        }
        .feature-text h4 {
            font-size: 1rem;
            font-weight: 600;
            margin: 0 0 4px 0;
        }
        .feature-text p {
            font-size: 0.85rem;
            color: #94a3b8;
            margin: 0;
            line-height: 1.5;
        }
        .feature-highlight {
            border: 1px solid rgba(255, 255, 255, 0.15);
            background: rgba(15, 23, 42, 0.5);
            backdrop-filter: blur(4px);
            padding: 12px 20px;
            border-radius: 8px;
            margin-top: 35px;
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }
        .feature-highlight i {
            margin-right: 12px;
            font-size: 1.2rem;
            color: #3b82f6;
        }

        /* Login Box */
        .login-box-wrapper {
            flex: 1;
            display: flex;
            justify-content: flex-end;
            padding-right: 40px;
        }
        .login-container {
            background: #ffffff;
            border-radius: 16px;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            width: 100%;
            max-width: 420px;
            padding: 45px 40px;
            color: #0f172a;
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo-icon {
            width: 60px;
            height: 60px;
            background: #eff6ff;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: #1d4ed8;
            margin-bottom: 15px;
            border: 1px solid #bfdbfe;
        }
        .login-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            text-align: center;
        }
        .login-subtitle {
            font-size: 0.9rem;
            color: #64748b;
            text-align: center;
            margin-top: 5px;
            position: relative;
            padding-bottom: 15px;
        }
        .login-subtitle::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 2px;
            background: #3b82f6;
        }

        .form-group {
            margin-bottom: 20px;
        }
        .form-label {
            font-size: 0.85rem;
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
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
        }
        .input-group-custom .form-control {
            padding-left: 40px;
            padding-right: 40px;
            height: 48px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.9rem;
            background: #f8fafc;
            color: #334155;
            transition: all 0.2s;
        }
        .input-group-custom .form-control:focus {
            background: #fff;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
            outline: none;
        }
        .input-group-custom i.icon-right {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 14px;
            cursor: pointer;
        }

        .btn-login {
            background: #1d4ed8;
            color: white;
            border: none;
            height: 48px;
            border-radius: 8px;
            width: 100%;
            font-weight: 600;
            font-size: 1rem;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background: #1e40af;
            transform: translateY(-1px);
        }
        .btn-login i {
            margin-left: 8px;
        }

        .role-badge {
            text-align: center;
            margin-top: 25px;
            font-size: 0.8rem;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .role-badge i {
            color: #3b82f6;
            margin-right: 6px;
        }

        /* Footer Bar */
        .footer-bar {
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(10px);
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            padding: 15px 60px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .footer-left {
            display: flex;
            align-items: center;
        }
        .footer-left-icon {
            width: 36px;
            height: 36px;
            border: 1px solid rgba(255,255,255,0.3);
            border-radius: 6px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 15px;
            font-size: 18px;
        }
        .footer-left-text h5 {
            font-size: 0.9rem;
            margin: 0 0 2px 0;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }
        .footer-left-text p {
            font-size: 0.75rem;
            color: #94a3b8;
            margin: 0;
        }
        
        .footer-right {
            display: flex;
            gap: 40px;
        }
        .footer-item {
            display: flex;
            align-items: center;
        }
        .footer-item i {
            font-size: 20px;
            margin-right: 12px;
            color: #94a3b8;
        }
        .footer-item-text span {
            display: block;
            font-size: 0.75rem;
            color: #94a3b8;
            margin-bottom: 2px;
        }
        .footer-item-text strong {
            display: block;
            font-size: 0.85rem;
            font-weight: 500;
            color: #fff;
        }

        /* Error/Success Alerts */
        .alert {
            font-size: 0.85rem;
            padding: 10px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }

        /* Responsive */
        @media (max-width: 1024px) {
            .content-area {
                flex-direction: column;
                padding: 0 20px;
                justify-content: center;
            }
            .features-list {
                display: none;
            }
            .login-box-wrapper {
                padding-right: 0;
                margin-top: 20px;
            }
            .page-header {
                padding: 30px 20px;
            }
            .footer-bar {
                flex-direction: column;
                gap: 20px;
                padding: 20px;
                text-align: center;
            }
            .footer-left, .footer-right {
                justify-content: center;
                flex-wrap: wrap;
            }
            .footer-right {
                gap: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="overlay"></div>
    
    <div class="main-wrapper">
        <!-- Header -->
        <div class="page-header">
            <div class="header-title-container">
                <h1 class="header-title">Fleet Monitoring System</h1>
                <p class="header-subtitle">Production & Equipment Performance Monitoring</p>
            </div>
        </div>

        <!-- Main Content -->
        <div class="content-area">
            <!-- Left Features -->
            <div class="features-list">
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-map-marker-alt"></i></div>
                    <div class="feature-text">
                        <h4>Real-Time Fleet Tracking</h4>
                        <p>Pantau lokasi dan pergerakan seluruh unit secara real-time</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="far fa-clock"></i></div>
                    <div class="feature-text">
                        <h4>Idle Time Monitoring</h4>
                        <p>Monitor waktu idle untuk meningkatkan produktivitas</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-chart-bar"></i></div>
                    <div class="feature-text">
                        <h4>Productivity Analytics</h4>
                        <p>Analisa performa untuk pengambilan keputusan</p>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon"><i class="fas fa-cog"></i></div>
                    <div class="feature-text">
                        <h4>Equipment Performance</h4>
                        <p>Pantau kesehatan dan performa equipment</p>
                    </div>
                </div>
                
                <div class="feature-highlight">
                    <i class="fas fa-shield-alt"></i>
                    Data akurat. Keputusan tepat. Operasional lebih efisien.
                </div>
            </div>

            <!-- Right Login Box -->
            <div class="login-box-wrapper">
                <div class="login-container">
                    <div class="login-logo">
                        <div class="login-logo-icon">
                            <i class="fas fa-truck-moving"></i>
                        </div>
                        <h2 class="login-title">Fleet Monitoring System</h2>
                        <p class="login-subtitle">Fleet Dashboard</p>
                    </div>

                    <?php if($errors->any()): ?>
                        <div class="alert alert-danger bg-danger text-white">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <div><i class="fas fa-exclamation-circle me-1"></i> <?php echo e($error); ?></div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>

                    <?php if(session('error')): ?>
                        <div class="alert alert-danger bg-danger text-white"><i class="fas fa-exclamation-circle me-1"></i> <?php echo e(session('error')); ?></div>
                    <?php endif; ?>

                    <?php if(session('success')): ?>
                        <div class="alert alert-success bg-success text-white"><i class="fas fa-check-circle me-1"></i> <?php echo e(session('success')); ?></div>
                    <?php endif; ?>

                    <form action="<?php echo e(route('frontend.login')); ?>" method="POST">
                        <?php echo csrf_field(); ?>
                        
                        <div class="form-group">
                            <label class="form-label">Email Address</label>
                            <div class="input-group-custom">
                                <i class="fas fa-envelope icon-left"></i>
                                <input type="email" name="email" class="form-control" placeholder="user@example.com" value="<?php echo e(old('email')); ?>" required autofocus>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Password</label>
                            <div class="input-group-custom">
                                <i class="fas fa-lock icon-left"></i>
                                <input type="password" name="password" id="password" class="form-control" placeholder="Enter your password" required>
                                <i class="far fa-eye icon-right" id="togglePassword"></i>
                            </div>
                        </div>

                        <button type="submit" class="btn-login">
                            Sign In <i class="fas fa-arrow-right"></i>
                        </button>
                    </form>

                    <div class="role-badge">
                        <i class="fas fa-shield-alt"></i> Available for Admin & Fleet Manager roles
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer-bar">
            <div class="footer-left">
                <div class="footer-left-icon">
                    <i class="fas fa-plus"></i>
                </div>
                <div class="footer-left-text">
                    <h5>SAFETY FIRST, PRODUCTION ALWAYS</h5>
                    <p>Utamakan Keselamatan dalam Setiap Aktivitas</p>
                </div>
            </div>
            <div class="footer-right">
                <div class="footer-item">
                    <i class="fas fa-lock"></i>
                    <div class="footer-item-text">
                        <span>Status</span>
                        <strong>Secure Access</strong>
                    </div>
                </div>
                <div class="footer-item">
                    <i class="far fa-clock"></i>
                    <div class="footer-item-text">
                        <span>System Time</span>
                        <strong id="systemTime">Loading...</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // ═══════════════════════════════════════════════════════════════
        // AUTO-REFRESH CSRF TOKEN ON LOGIN PAGE (prevent Page Expired)
        // ═══════════════════════════════════════════════════════════════
        $(document).ready(function() {
            // Auto-refresh CSRF token every 30 minutes
            setInterval(function() {
                $.ajax({
                    url: '/csrf-refresh',
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
                    url: '/csrf-refresh',
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
            const emailInput = document.querySelector('input[name="email"]');
            if (emailInput) {
                emailInput.addEventListener('focus', function() {
                    sessionStorage.removeItem('login_page_reloaded');
                });
            }
        });

        // Toggle password visibility
        document.getElementById('togglePassword').addEventListener('click', function() {
            const passwordInput = document.getElementById('password');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('fa-eye');
            this.classList.toggle('fa-eye-slash');
        });

        // Live Clock Update
        function updateClock() {
            const now = new Date();
            const options = { day: '2-digit', month: 'short', year: 'numeric' };
            const dateStr = now.toLocaleDateString('id-ID', options);
            const timeStr = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('systemTime').textContent = dateStr + ' - ' + timeStr + ' WIB';
        }
        setInterval(updateClock, 1000);
        updateClock();
    </script>
</body>
</html>
<?php /**PATH G:\project\vss\idle-monitor\resources\views\frontend\auth\login.blade.php ENDPATH**/ ?>