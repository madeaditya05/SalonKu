<header class="jk-header">
    <div class="jk-container jk-header-inner">
        <a href="{{ route('home') }}" class="jk-brand">
            <span class="jk-brand-icon">
                <svg viewBox="0 0 48 48" fill="none">
                    <path d="M14 28L25 17L31 23L20 34C18.3 35.7 15.7 35.7 14 34C12.3 32.3 12.3 29.7 14 28Z" fill="currentColor"/>
                    <path d="M25 17L31.5 10.5C33.2 8.8 35.8 8.8 37.5 10.5C39.2 12.2 39.2 14.8 37.5 16.5L31 23L25 17Z" fill="currentColor" opacity="0.75"/>
                    <path d="M23 31L29 25L38 34C39.7 35.7 39.7 38.3 38 40C36.3 41.7 33.7 41.7 32 40L23 31Z" fill="currentColor" opacity="0.9"/>
                </svg>
            </span>
            <span>JasaKu</span>
        </a>

        <nav class="jk-nav">
            <a href="{{ route('home') }}" class="active">Home</a>
            <a href="#services">Layanan</a>
            <a href="#categories">Kategori</a>
            <a href="#how-it-works">Cara Kerja</a>
            <a href="#footer">Kontak</a>
        </nav>

        <div class="jk-header-actions">
            @auth
                @if (Auth::user()->role === 'customer')
                    <span class="jk-auth-name">Hi, {{ Auth::user()->name }}</span>

                    <form action="{{ route('customer.logout') }}" method="POST">
                        @csrf
                        <button type="submit" class="jk-btn-primary small">Logout</button>
                    </form>
                @else
                    <button type="button" class="jk-btn-text" data-auth-open="signin">Sign In</button>
                    <button type="button" class="jk-btn-primary small" data-auth-open="signup">Sign Up</button>
                @endif
            @else
                <button type="button" class="jk-btn-text" data-auth-open="signin">Sign In</button>
                <button type="button" class="jk-btn-primary small" data-auth-open="signup">Sign Up</button>
            @endauth
        </div>
    </div>
</header>