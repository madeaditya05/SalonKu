<div
    class="auth-modal"
    data-auth-modal
    data-auth-initial="{{ session('auth_modal') }}"
    hidden
>
    <div class="auth-backdrop" data-auth-close></div>

    <div class="auth-dialog">
        <button type="button" class="auth-close" data-auth-close>×</button>

        <div class="auth-tabs">
            <button type="button" class="auth-tab active" data-auth-tab="signin">Sign In</button>
            <button type="button" class="auth-tab" data-auth-tab="signup">Sign Up</button>
        </div>

        @if ($errors->any())
            <div class="auth-alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <section class="auth-panel active" data-auth-panel="signin">
            <h3>Masuk ke JasaKu</h3>
            <p>Masuk sebagai customer untuk memesan layanan.</p>

            <form action="{{ route('customer.signin') }}" method="POST" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label>Email / Username</label>
                    <input type="text" name="login" value="{{ old('login') }}" placeholder="Email atau username" required>
                </div>

                <div class="auth-field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Password" required>
                </div>

                <button type="submit" class="auth-submit">Sign In</button>
            </form>
        </section>

        <section class="auth-panel" data-auth-panel="signup">
            <h3>Buat akun customer</h3>
            <p>Daftar untuk mulai booking layanan.</p>

            <form action="{{ route('customer.signup') }}" method="POST" class="auth-form">
                @csrf

                <div class="auth-field">
                    <label>Nama Lengkap</label>
                    <input type="text" name="name" value="{{ old('name') }}" placeholder="Nama lengkap" required>
                </div>

                <div class="auth-field">
                    <label>Username</label>
                    <input type="text" name="username" value="{{ old('username') }}" placeholder="Username" required>
                </div>

                <div class="auth-field">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ old('email') }}" placeholder="Email" required>
                </div>

                <div class="auth-field">
                    <label>No. HP</label>
                    <input type="text" name="phone_number" value="{{ old('phone_number') }}" placeholder="Nomor HP">
                </div>

                <div class="auth-field">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Minimal 8 karakter" required>
                </div>

                <div class="auth-field">
                    <label>Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" placeholder="Ulangi password" required>
                </div>

                <button type="submit" class="auth-submit">Sign Up</button>
            </form>
        </section>
    </div>
</div>