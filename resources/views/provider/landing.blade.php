@extends('provider.layouts.app')

@section('title', 'Welcome to Truely Sell')

@section('content')
<section class="hero-section">
    <div class="hero-content">
        <h1>
            Get your <span class="typing-line"></span>
        </h1>

        <p>
            We can connect you to the right Service, first time and everytime.
        </p>

        <form class="hero-search" action="#" method="GET">
            <div class="search-field">
                <span>⌕</span>
                <input type="text" placeholder="Search for Service">
            </div>

            <div class="search-field location-field">
                <span>⌾</span>
                <input type="text" placeholder="Enter Location">
            </div>

            <button type="submit">
                ⌕ Search
            </button>
        </form>

        <div class="popular-search">
            <strong>Popular Searches</strong>
            <a href="#">Electrical</a>
            <a href="#">Catering Services</a>
            <a href="#">Removal</a>
        </div>

        <div class="hero-stats">
            <div class="stat-item">
                <div class="stat-icon">♙</div>
                <div>
                    <strong>215+</strong>
                    <span>Verified Providers</span>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon">↗</div>
                <div>
                    <strong>500+</strong>
                    <span>Services Completed</span>
                </div>
            </div>

            <div class="stat-item">
                <div class="stat-icon">☆</div>
                <div>
                    <strong>2000</strong>
                    <span>Reviews Globally</span>
                </div>
            </div>
        </div>
    </div>

    <div class="hero-visual">
        <div class="rating-card">
            <span>★</span>

            <div>
                <strong>4 / 5</strong>
                <p>(300 reviews)</p>
            </div>
        </div>

        <div class="completed-card">
            <span>✓</span>
            <strong>300 Booking Completed</strong>
        </div>

        <div class="hero-circle"></div>

        <div class="person person-one">
            <div class="helmet">▔</div>
            <div class="face"></div>
            <div class="body"></div>
            <div class="tool"></div>
        </div>

        <div class="person person-two">
            <div class="hair"></div>
            <div class="face"></div>
            <div class="body"></div>
            <div class="brush"></div>
        </div>
    </div>
</section>

<section class="category-section" id="service">
    <h2>Checkout our Recent <span>Category</span></h2>

    <div class="category-grid">
        <div class="category-card">
            <div>⚡</div>
            <h3>Electrical</h3>
            <p>Home and commercial electrical services.</p>
        </div>

        <div class="category-card">
            <div>🍽</div>
            <h3>Catering Services</h3>
            <p>Food and beverage services for events.</p>
        </div>

        <div class="category-card">
            <div>🚚</div>
            <h3>Removal</h3>
            <p>Removal and waste management services.</p>
        </div>
    </div>
</section>

<section class="about-section" id="about">
    <div class="about-container">
        <div>
            <span class="section-label">About Us</span>
            <h2>We Help Customers Find Trusted Service Providers</h2>
        </div>

        <p>
            Truely Sell connects customers with verified service providers. Providers can join as partners
            to promote their services and reach more customers through our platform.
        </p>
    </div>
</section>

<section class="blogs-section" id="blogs">
    <div class="blogs-heading">
        <span class="section-label">Blogs</span>
        <h2>Latest Updates</h2>
    </div>

    <div class="blogs-grid">
        <div class="blog-card">
            <h3>How to Choose the Right Service Provider</h3>
            <p>Find trusted providers for your daily service needs.</p>
        </div>

        <div class="blog-card">
            <h3>Grow Your Service Business Online</h3>
            <p>Providers can promote services and reach more potential customers.</p>
        </div>

        <div class="blog-card">
            <h3>Why Verified Providers Matter</h3>
            <p>Verified providers help customers make safer decisions.</p>
        </div>
    </div>
</section>

<div class="help-bubble">Need help?</div>

<div class="floating-chat">
    <button class="chat-btn blue">■</button>
    <button class="chat-btn green">☏</button>
</div>
@endsection