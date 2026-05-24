@php
    $adminLoginEmail = 'admin@gmail.com';
    $adminLoginPassword = 'admin12345';
@endphp

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - JasaKu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('admin/css/admin-auth.css') }}">
</head>
<body>

    <main class="auth-wrapper" data-admin-email="{{ $adminLoginEmail }}" data-admin-password="{{ $adminLoginPassword }}">
        <div class="auth-logo">
            <div class="logo-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3l2.2 6.2L20 12l-5.8 2.8L12 21l-2.2-6.2L4 12l5.8-2.8L12 3Z"></path>
                </svg>
            </div>
            <span>JasaKu</span>
        </div>

        <div class="auth-card">
            <h1>Welcome</h1>
            <p>Please enter your details to sign in</p>

            @if ($errors->any())
                <div class="alert-error">
                    {{ $errors->first() }}
                </div>
            @endif

            <form action="{{ route('admin.login.post') }}" method="POST">
                @csrf

                <div class="form-group">
                    <label>Email Address</label>
                    <div class="input-wrapper">
                        <input type="email" name="email" value="{{ old('email') }}" autocomplete="email">
                        <span class="input-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 6h16v12H4z"></path>
                                <path d="m4 7 8 6 8-6"></path>
                            </svg>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label>Password</label>
                        <a href="#">Forgot Password?</a>
                    </div>

                    <div class="input-wrapper">
                        <input type="password" name="password" id="password">
                        <button type="button" class="password-toggle" onclick="togglePassword()" aria-label="Show password" aria-pressed="false">
                            <svg class="eye-open" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"></path>
                                <circle cx="12" cy="12" r="3"></circle>
                            </svg>
                            <svg class="eye-closed" viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M3 3l18 18"></path>
                                <path d="M10.6 10.6a3 3 0 0 0 3.8 3.8"></path>
                                <path d="M7.1 7.5C4.1 9.2 2.5 12 2.5 12s3.5 6 9.5 6c1.8 0 3.3-.4 4.6-1"></path>
                                <path d="M13.4 6.1c5 .7 8.1 5.9 8.1 5.9a15.8 15.8 0 0 1-2.4 3"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign in
                </button>
            </form>

            <div class="login-info">
                <div class="login-info-title">
                    <span class="login-info-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"></path>
                            <path d="M9.5 12.5 11.2 14l3.5-4"></path>
                        </svg>
                    </span>

                    <div>
                        <h4>Admin Login Info</h4>
                        <p>Akun admin yang aktif untuk dashboard.</p>
                    </div>
                </div>

                <div class="info-box">
                    <div class="info-list">
                        <div class="info-row">
                            <span class="info-row-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <path d="M4 6h16v12H4z"></path>
                                    <path d="m4 7 8 6 8-6"></path>
                                </svg>
                            </span>

                            <div>
                                <small>Email</small>
                                <strong>{{ $adminLoginEmail }}</strong>
                            </div>
                        </div>

                        <div class="info-row">
                            <span class="info-row-icon" aria-hidden="true">
                                <svg viewBox="0 0 24 24">
                                    <circle cx="8" cy="12" r="4"></circle>
                                    <path d="M12 12h9"></path>
                                    <path d="M17 12v3"></path>
                                    <path d="M20 12v3"></path>
                                </svg>
                            </span>

                            <div>
                                <small>Password</small>
                                <strong>{{ $adminLoginPassword }}</strong>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="info-copy-btn" onclick="copyLoginInfo()" aria-label="Copy admin login info">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <rect x="8" y="8" width="11" height="11" rx="2"></rect>
                            <path d="M5 15H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v1"></path>
                        </svg>
                        <span id="copyFeedback">Copy</span>
                    </button>
                </div>
            </div>
        </div>

        <footer>
            &copy; 2025 All right reserved. <strong>JasaKu</strong>
        </footer>
    </main>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            const toggle = document.querySelector('.password-toggle');
            const shouldShow = password.type === 'password';

            password.type = shouldShow ? 'text' : 'password';

            if (toggle) {
                toggle.classList.toggle('showing', shouldShow);
                toggle.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
            }
        }

        function copyLoginInfo() {
            const wrapper = document.querySelector('.auth-wrapper');
            const email = wrapper ? wrapper.dataset.adminEmail : '';
            const password = wrapper ? wrapper.dataset.adminPassword : '';
            const text = 'Email: ' + email + '\nPassword: ' + password;
            const button = document.querySelector('.info-copy-btn');
            const feedback = document.getElementById('copyFeedback');

            function markCopied() {
                if (!button || !feedback) {
                    return;
                }

                button.classList.add('copied');
                feedback.textContent = 'Copied';

                window.setTimeout(function () {
                    button.classList.remove('copied');
                    feedback.textContent = 'Copy';
                }, 1400);
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(text).then(markCopied);
                return;
            }

            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            markCopied();
        }
    </script>

</body>
</html>
