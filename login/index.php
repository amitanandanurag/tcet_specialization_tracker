<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
include_once("../database/db_connect.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TCET Mumbai — Academic ERP Portal Login</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <style>
        :root {
            --erp-primary: #423cbc;
            --erp-primary-hover: #352fa1;
            --erp-primary-dark: #2a2485;
            --erp-primary-light: #eef2ff;
            --erp-primary-border: #c7d2fe;
            --erp-bg: #f8fafc;
            --erp-surface: #ffffff;
            --erp-border: #e2e8f0;
            --erp-border-input: #cbd5e1;
            --erp-text-main: #0f172a;
            --erp-text-body: #334155;
            --erp-text-secondary: #475569;
            --erp-text-muted: #64748b;
            --erp-radius: 4px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        html,
        body {
            min-height: 100%;
            background-color: var(--erp-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            font-size: 13px;
            color: var(--erp-text-body);
            line-height: 1.5;
            -webkit-font-smoothing: antialiased;
        }

        .erp-login-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            padding: 24px 16px;
        }

        /* Institutional Top Header Bar */
        .erp-login-topbar {
            width: 100%;
            max-width: 940px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 4px 16px;
        }

        .erp-topbar-brand {
            display: flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            color: var(--erp-text-main);
        }

        .erp-topbar-title {
            font-size: 13px;
            font-weight: 700;
            color: var(--erp-primary-dark);
            letter-spacing: 0.3px;
        }

        .erp-topbar-link {
            font-size: 12px;
            color: var(--erp-text-muted);
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 4px;
            transition: color 0.15s ease;
        }

        .erp-topbar-link:hover {
            color: var(--erp-primary);
            text-decoration: underline;
        }

        /* Main Institutional Card */
        .erp-login-card {
            width: 100%;
            max-width: 940px;
            background: var(--erp-surface);
            border: 1px solid var(--erp-border);
            border-radius: 6px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -2px rgba(0, 0, 0, 0.05);
            display: flex;
            overflow: hidden;
            margin: auto 0;
        }

        /* Left Column: Institutional Brand Pane */
        .erp-brand-pane {
            flex: 1;
            background: #f8fafc;
            border-right: 1px solid var(--erp-border);
            padding: 44px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .erp-brand-header {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .erp-brand-logo {
            height: 72px;
            width: auto;
            object-fit: contain;
            margin-bottom: 20px;
        }

        .erp-inst-name {
            font-size: 16px;
            font-weight: 800;
            color: var(--erp-text-main);
            text-transform: uppercase;
            letter-spacing: 0.03em;
            line-height: 1.35;
            margin-bottom: 6px;
        }

        .erp-inst-affil {
            font-size: 12px;
            color: var(--erp-text-secondary);
            font-weight: 500;
            line-height: 1.4;
            margin-bottom: 18px;
        }

        .erp-brand-divider {
            width: 100%;
            height: 1px;
            background: var(--erp-border);
            margin: 16px 0;
        }

        .erp-portal-name {
            font-size: 13px;
            font-weight: 700;
            color: var(--erp-primary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 4px;
        }

        .erp-portal-desc {
            font-size: 12px;
            color: var(--erp-text-muted);
            line-height: 1.45;
        }

        .erp-brand-meta {
            margin-top: 24px;
            font-size: 11px;
            color: var(--erp-text-muted);
            display: flex;
            align-items: center;
            gap: 6px;
            font-weight: 500;
        }

        /* Right Column: Sign In Form Pane */
        .erp-form-pane {
            width: 440px;
            padding: 40px 38px;
            background: var(--erp-surface);
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .erp-form-header {
            margin-bottom: 22px;
        }

        .erp-form-title {
            font-size: 18px;
            font-weight: 700;
            color: var(--erp-text-main);
            margin-bottom: 4px;
            letter-spacing: -0.01em;
        }

        .erp-form-subtitle {
            font-size: 12px;
            color: var(--erp-text-muted);
        }

        .erp-form-group {
            margin-bottom: 16px;
        }

        .erp-form-label {
            display: block;
            font-size: 11px;
            font-weight: 700;
            color: var(--erp-text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .erp-input-group {
            display: flex;
            align-items: stretch;
            border: 1px solid var(--erp-border-input);
            border-radius: var(--erp-radius);
            background: #ffffff;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
            overflow: hidden;
        }

        .erp-input-group:focus-within {
            border-color: var(--erp-primary);
            box-shadow: 0 0 0 3px rgba(66, 60, 188, 0.12);
        }

        .erp-input-group.erp-input-invalid {
            border-color: #dc2626 !important;
        }

        .erp-input-icon {
            width: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f8fafc;
            color: var(--erp-text-muted);
            border-right: 1px solid var(--erp-border);
            font-size: 13px;
        }

        .erp-input-control {
            flex: 1;
            border: none;
            outline: none;
            padding: 9px 12px;
            font-size: 13px;
            color: var(--erp-text-main);
            background: transparent;
            font-family: inherit;
        }

        .erp-input-control::placeholder {
            color: #94a3b8;
            font-size: 12px;
        }

        .erp-pwd-toggle {
            width: 38px;
            border: none;
            background: #f8fafc;
            border-left: 1px solid var(--erp-border);
            color: var(--erp-text-muted);
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            transition: color 0.15s ease;
        }

        .erp-pwd-toggle:hover {
            color: var(--erp-text-main);
        }

        .erp-input-error-msg {
            color: #dc2626;
            font-size: 11px;
            margin-top: 4px;
            font-weight: 500;
        }

        .erp-form-options {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            font-size: 12px;
        }

        .erp-checkbox-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            cursor: pointer;
            color: var(--erp-text-body);
            font-weight: 500;
            user-select: none;
        }

        .erp-checkbox-label input[type="checkbox"] {
            accent-color: var(--erp-primary);
            width: 14px;
            height: 14px;
            cursor: pointer;
        }

        .erp-forgot-link {
            color: var(--erp-primary);
            text-decoration: none;
            font-weight: 600;
            cursor: pointer;
        }

        .erp-forgot-link:hover {
            text-decoration: underline;
        }

        .btn-erp-login {
            width: 100%;
            height: 38px;
            background: var(--erp-primary);
            color: #ffffff;
            border: 1px solid var(--erp-primary-dark);
            border-radius: var(--erp-radius);
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            cursor: pointer;
            transition: background-color 0.15s ease, border-color 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-erp-login:hover {
            background: var(--erp-primary-hover);
        }

        .btn-erp-login:disabled {
            opacity: 0.7;
            cursor: not-allowed;
        }

        .erp-login-alert {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: 9px 12px;
            border-radius: var(--erp-radius);
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .erp-login-divider {
            position: relative;
            text-align: center;
            margin: 20px 0 16px;
        }

        .erp-login-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            border-top: 1px solid var(--erp-border);
        }

        .erp-login-divider span {
            position: relative;
            background: #ffffff;
            padding: 0 10px;
            font-size: 11px;
            color: var(--erp-text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-erp-register {
            width: 100%;
            height: 36px;
            background: #ffffff;
            color: var(--erp-text-secondary);
            border: 1px solid var(--erp-border-input);
            border-radius: var(--erp-radius);
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
        }

        .btn-erp-register:hover {
            background: #f8fafc;
            color: var(--erp-text-main);
            border-color: var(--erp-text-muted);
        }

        /* Footer */
        .erp-login-footer {
            width: 100%;
            max-width: 940px;
            padding-top: 16px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: var(--erp-text-muted);
        }

        .erp-login-footer a {
            color: var(--erp-primary);
            text-decoration: none;
            font-weight: 600;
        }

        .erp-login-footer a:hover {
            text-decoration: underline;
        }

        /* Overlay Popups */
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            display: none;
            justify-content: center;
            align-items: center;
            background: rgba(15, 23, 42, 0.6);
            z-index: 1000;
            padding: 16px;
        }

        .popup {
            position: relative;
            width: 100%;
            max-width: 480px;
            height: 490px;
            max-height: 90vh;
            border-radius: 6px;
            background: #ffffff;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2), 0 10px 10px -5px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            border: 1px solid var(--erp-border);
        }

        #forgotPasswordPopup .popup {
            height: 390px;
        }

        .popup iframe {
            width: 100%;
            height: 100%;
            border: none;
            display: block;
        }

        .popup .close-btn {
            position: absolute;
            top: 12px;
            right: 14px;
            font-size: 18px;
            line-height: 1;
            color: var(--erp-text-muted);
            cursor: pointer;
            z-index: 10;
            background: #ffffff;
            width: 28px;
            height: 28px;
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 1px solid var(--erp-border);
            transition: all 0.15s ease;
        }

        .popup .close-btn:hover {
            color: var(--erp-text-main);
            background: #f1f5f9;
            border-color: var(--erp-border-dark);
        }

        /* Responsive Breakpoints */
        @media (max-width: 820px) {
            .erp-login-card {
                flex-direction: column;
                max-width: 440px;
            }

            .erp-brand-pane {
                border-right: none;
                border-bottom: 1px solid var(--erp-border);
                padding: 28px 24px;
                align-items: center;
                text-align: center;
            }

            .erp-brand-header {
                align-items: center;
            }

            .erp-brand-logo {
                height: 60px;
                margin-bottom: 14px;
            }

            .erp-brand-meta {
                justify-content: center;
                margin-top: 14px;
            }

            .erp-form-pane {
                width: 100%;
                padding: 28px 24px;
            }

            .erp-login-topbar,
            .erp-login-footer {
                flex-direction: column;
                gap: 6px;
                text-align: center;
            }
        }
    </style>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="script/validation.min.js"></script>
    <script src="script/login.js"></script>
    <script>
        function openRegister() {
            document.getElementById("registerPopup").style.display = "flex";
        }

        function closePopup() {
            document.getElementById("registerPopup").style.display = "none";
        }

        function openForgotPassword() {
            document.getElementById("forgotPasswordPopup").style.display = "flex";
        }

        function closeForgotPasswordPopup() {
            document.getElementById("forgotPasswordPopup").style.display = "none";
        }

        function togglePasswordVisibility() {
            var passwordField = document.getElementById("password");
            var toggleButton = document.getElementById("passwordToggle");
            var toggleIcon = document.getElementById("passwordToggleIcon");

            if (!passwordField || !toggleButton || !toggleIcon) {
                return;
            }

            var isPassword = passwordField.type === "password";
            passwordField.type = isPassword ? "text" : "password";
            toggleIcon.className = isPassword ? "fa fa-eye-slash" : "fa fa-eye";
            toggleButton.setAttribute("aria-label", isPassword ? "Hide password" : "Show password");
        }

        window.addEventListener("message", function (event) {
            if (event.origin !== window.location.origin) {
                return;
            }

            var data = event.data || {};
            if (data.type === "student-registration-success") {
                var usernameField = document.getElementById("username");
                var passwordField = document.getElementById("password");

                if (usernameField) {
                    usernameField.value = data.username || "";
                }

                if (passwordField) {
                    passwordField.value = data.password || "";
                }

                closePopup();

                if (usernameField) {
                    usernameField.focus();
                }
            }
        });
    </script>
</head>

<body>

    <div class="erp-login-wrapper">
        <!-- Institutional Top Bar -->
        <div class="erp-login-topbar">
            <div class="erp-topbar-brand">
                <span class="erp-topbar-title">TCET Academic ERP</span>
            </div>
            <a href="https://www.tcetmumbai.in" target="_blank" class="erp-topbar-link">
                <i class="fa fa-external-link"></i> tcetmumbai.in
            </a>
        </div>

        <!-- Main 2-Column Institutional Login Card -->
        <div class="erp-login-card">
            <!-- Left Pane: Institutional Branding -->
            <div class="erp-brand-pane">
                <div class="erp-brand-header">
                    <img src="images/tcet_logo.png" alt="TCET Mumbai Crest" class="erp-brand-logo">
                    <h1 class="erp-inst-name">Thakur College of Engineering & Technology</h1>
                    <p class="erp-inst-affil">An Autonomous Institute Affiliated to University of Mumbai</p>
                    
                    <div class="erp-brand-divider"></div>
                    
                    <div class="erp-portal-name">Specialization Tracker & ERP</div>
                    <p class="erp-portal-desc">Centralized portal for student specialization management, academic progression, and faculty mentoring.</p>
                </div>

                <div class="erp-brand-meta">
                    <span>Student</span> &bull; <span>Faculty</span> &bull; <span>Coordinator</span> &bull; <span>Administration</span>
                </div>
            </div>

            <!-- Right Pane: Sign In Form -->
            <div class="erp-form-pane">
                <div class="erp-form-header">
                    <h2 class="erp-form-title">Welcome Back</h2>
                    <p class="erp-form-subtitle">Sign in to continue to the TCET ERP portal</p>
                </div>

                <form id="login-form">
                    <div class="erp-form-group">
                        <label for="username" class="erp-form-label">Username or Registration No.</label>
                        <div class="erp-input-group">
                            <span class="erp-input-icon"><i class="fa fa-user"></i></span>
                            <input type="text" class="erp-input-control" placeholder="Enter username or registration no." name="username" id="username" required autocomplete="username">
                        </div>
                    </div>

                    <div class="erp-form-group">
                        <label for="password" class="erp-form-label">Password</label>
                        <div class="erp-input-group">
                            <span class="erp-input-icon"><i class="fa fa-lock"></i></span>
                            <input type="password" class="erp-input-control" placeholder="Enter password" name="password" id="password" required autocomplete="current-password">
                            <button type="button" class="erp-pwd-toggle" id="passwordToggle" aria-label="Show password" onclick="togglePasswordVisibility()">
                                <i id="passwordToggleIcon" class="fa fa-eye"></i>
                            </button>
                        </div>
                    </div>

                    <div class="erp-form-options">
                        <label class="erp-checkbox-label">
                            <input type="checkbox" name="checkbox" checked>
                            <span>Keep me logged in</span>
                        </label>
                        <a href="javascript:void(0);" onclick="openForgotPassword()" class="erp-forgot-link">Forgot password?</a>
                    </div>

                    <div id="error" style="display:none;"></div>

                    <button type="submit" class="btn-erp-login" name="login_button" id="login_button">
                        <i class="fa fa-sign-in" style="margin-right: 4px;"></i> Sign In
                    </button>

                    <div class="erp-login-divider">
                        <span>Student Access</span>
                    </div>

                    <button type="button" class="btn-erp-register" onclick="openRegister()">
                        <i class="fa fa-graduation-cap"></i> New Student Registration
                    </button>
                </form>
            </div>
        </div>

        <!-- Institutional Footer -->
        <div class="erp-login-footer">
            <span>&copy; <?php echo date('Y'); ?> Thakur College of Engineering & Technology. All rights reserved.</span>
            <a href="https://www.tcetmumbai.in" target="_blank">Official Website</a>
        </div>
    </div>

    <!-- Student Registration Modal Popup -->
    <div id="registerPopup" class="overlay">
        <div class="popup">
            <span class="close-btn" onclick="closePopup()" title="Close">&times;</span>
            <iframe src="student_register.php" style="width:100%; height:100%; border:none;"></iframe>
        </div>
    </div>

    <!-- Forgot Password Modal Popup -->
    <div id="forgotPasswordPopup" class="overlay">
        <div class="popup">
            <span class="close-btn" onclick="closeForgotPasswordPopup()" title="Close">&times;</span>
            <iframe src="forgot_password.php" style="width:100%; height:100%; border:none;"></iframe>
        </div>
    </div>

</body>
</html>


