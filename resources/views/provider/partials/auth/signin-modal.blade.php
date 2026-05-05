<div class="modal-overlay" id="signinModal">
    <div class="register-modal signin-modal">
        <button class="modal-close" type="button" data-close-modal aria-label="Close modal">
            ×
        </button>

        <div class="modal-heading">
            <h2>Sign In</h2>
            <p>Enter your email and password to access your account</p>
        </div>

        @if ($errors->signin->any())
            <div class="form-alert error">
                {{ $errors->signin->first() }}
            </div>
        @endif

        @if (session('signin_success'))
            <div class="form-alert success">
                {{ session('signin_success') }}
            </div>
        @endif

        <form action="{{ route('provider.signin') }}" method="POST" autocomplete="on">
            @csrf

            <div class="form-group">
                <label for="login_email">Email</label>
                <input
                    type="email"
                    id="login_email"
                    name="login_email"
                    value="{{ old('login_email') }}"
                    placeholder="Enter Email"
                    autocomplete="email"
                    required
                >
            </div>

            <div class="form-group">
                <label for="signinPasswordInput">Password</label>

                <div class="password-input">
                    <input
                        type="password"
                        id="signinPasswordInput"
                        name="login_password"
                        placeholder="Enter Password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        data-toggle-password="signinPasswordInput"
                        aria-label="Show or hide password"
                    >
                        ⌧
                    </button>
                </div>
            </div>

            <div class="signin-extra-row">
                <label class="remember-check">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        {{ old('remember') ? 'checked' : '' }}
                    >

                    <span>Remember me</span>
                </label>

                <a href="#" class="forgot-link">
                    Forgot Password?
                </a>
            </div>

            <button type="submit" class="signup-submit">
                Sign in
            </button>

            <div class="signin-text">
                Don’t have an account?
                <a href="#" data-switch-modal="registerModal">
                    Sign up
                </a>
            </div>
        </form>
    </div>
</div>