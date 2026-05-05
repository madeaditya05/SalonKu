<header class="site-header">
    <div class="header-left">
        <a href="{{ route('provider.landing') }}" class="brand">
            <span class="brand-mark">✦</span>
            <span class="brand-pink">Jasa</span>
            <span class="brand-dark">Ku</span>
        </a>
    </div>

    <nav class="main-nav">
        <button class="category-btn" type="button">
            ⌘ Categories
            <span>⌄</span>
        </button>

        <a href="{{ route('provider.landing') }}" class="active">Home</a>
        <a href="#service">Service</a>
        <a href="#about">About Us</a>
        <a href="#blogs">Blogs</a>

        <a href="#" data-open-modal="registerModal">
            Become a Provider
        </a>

        <button class="language-btn" type="button">
            🇺🇸
            <span>⌄</span>
        </button>
    </nav>

    <div class="header-actions">
        <button class="signin-btn" type="button" data-open-modal="signinModal">
            ♙ Sign in
        </button>

        <button class="join-btn" type="button" data-open-modal="registerModal">
            ♙ Join us
        </button>
    </div>
</header>