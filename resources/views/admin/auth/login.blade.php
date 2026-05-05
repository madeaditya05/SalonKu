<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - JasaKu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('admin/css/admin-auth.css') }}">
</head>
<body>

    <main class="auth-wrapper">
        <div class="auth-logo">
            <div class="logo-icon">✦</div>
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
                        <span>✉</span>
                    </div>
                </div>

                <div class="form-group">
                    <div class="label-row">
                        <label>Password</label>
                        <a href="#">Forgot Password?</a>
                    </div>

                    <div class="input-wrapper">
                        <input type="password" name="password" id="password">
                        <button type="button" class="password-toggle" onclick="togglePassword()">◉</button>
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    Sign in
                </button>
            </form>

            <div class="login-info">
                <h4>Admin Login Info:</h4>

                <div class="info-box">
                    <div>
                        <p><strong>Email :</strong> demoadmin@gmail.com</p>
                        <p><strong>Password :</strong> 12345678</p>
                    </div>

                    <button type="button" onclick="copyLoginInfo()">⧉</button>
                </div>
            </div>
        </div>

        <footer>
            © 2025 All right reserved. <strong>JasaKu</strong>
        </footer>
    </main>

    <script>
        function togglePassword() {
            const password = document.getElementById('password');
            password.type = password.type === 'password' ? 'text' : 'password';
        }

        function copyLoginInfo() {
            navigator.clipboard.writeText('Email: demoadmin@gmail.com\nPassword: 12345678');
            alert('Login info berhasil disalin.');
        }
    </script>

</body>
</html>