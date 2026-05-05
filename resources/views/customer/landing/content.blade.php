@php
    $categories = [
        ['name' => 'Cleaning', 'icon' => 'cleaning', 'color' => 'mint'],
        ['name' => 'Perbaikan', 'icon' => 'repair', 'color' => 'blue'],
        ['name' => 'Kecantikan', 'icon' => 'beauty', 'color' => 'pink'],
        ['name' => 'Laundry', 'icon' => 'laundry', 'color' => 'purple'],
        ['name' => 'Pindahan', 'icon' => 'moving', 'color' => 'orange'],
        ['name' => 'AC Service', 'icon' => 'ac', 'color' => 'cyan'],
        ['name' => 'Les Privat', 'icon' => 'book', 'color' => 'green'],
        ['name' => 'Otomotif', 'icon' => 'car', 'color' => 'sky'],
    ];

    $services = [
        ['title' => 'Home Cleaning', 'provider' => 'Bersih Prima', 'price' => 'Rp 60.000', 'rating' => '4.9', 'type' => 'cleaning'],
        ['title' => 'AC Service', 'provider' => 'Dingin Sejuk', 'price' => 'Rp 75.000', 'rating' => '4.8', 'type' => 'ac'],
        ['title' => 'Perawatan Wajah', 'provider' => 'Glow Beauty', 'price' => 'Rp 120.000', 'rating' => '4.9', 'type' => 'beauty'],
        ['title' => 'Laundry Kilat', 'provider' => 'CleanWash', 'price' => 'Rp 20.000/kg', 'rating' => '4.7', 'type' => 'laundry'],
        ['title' => 'Jasa Pindahan', 'provider' => 'Aman Pindah', 'price' => 'Rp 250.000', 'rating' => '4.8', 'type' => 'moving'],
    ];
@endphp

<main class="jk-page">
    <section class="jk-hero">
        <div class="jk-container jk-hero-grid">
            <div class="jk-hero-copy">
                <h1>
                    Temukan & Pesan
                    <span>Layanan Terpercaya</span>
                    Dengan Mudah
                </h1>

                <p>
                    JasaKu adalah platform reservasi layanan all-in-one.
                    Cepat, mudah, aman, dan terpercaya.
                </p>

                <form class="jk-search" data-customer-search>
                    <div class="jk-search-item">
                        <span class="jk-search-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M12 21C12 21 18 15.8 18 10.5C18 7 15.3 4.5 12 4.5C8.7 4.5 6 7 6 10.5C6 15.8 12 21 12 21Z" stroke="currentColor" stroke-width="1.8"/>
                                <circle cx="12" cy="10.5" r="2.4" stroke="currentColor" stroke-width="1.8"/>
                            </svg>
                        </span>

                        <div>
                            <label>Lokasi Anda</label>
                            <input type="text" placeholder="Jakarta Selatan">
                        </div>
                    </div>

                    <div class="jk-search-item">
                        <span class="jk-search-icon">
                            <svg viewBox="0 0 24 24" fill="none">
                                <circle cx="11" cy="11" r="6" stroke="currentColor" stroke-width="1.8"/>
                                <path d="M20 20L16 16" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                            </svg>
                        </span>

                        <div>
                            <label>Cari layanan</label>
                            <input
                                type="text"
                                placeholder="Cleaning, AC Service, Laundry"
                                data-customer-search-input
                            >
                        </div>
                    </div>

                    <button type="submit">Cari</button>
                </form>

                <div class="jk-trust-row">
                    <span>✓ Provider Terverifikasi</span>
                    <span>✓ Harga Transparan</span>
                    <span>✓ Pembayaran Aman</span>
                    <span>✓ Bergaransi</span>
                </div>
            </div>

            <div class="jk-hero-art">
                <div class="hero-bg-shape"></div>

                <div class="worker worker-main">
                    <div class="worker-head"></div>
                    <div class="worker-cap"></div>
                    <div class="worker-body">JasaKu</div>
                </div>

                <div class="mini-card mini-left">
                    <div class="mini-icon">🧹</div>
                    <strong>Cleaning</strong>
                    <span>Mulai Rp60K</span>
                </div>

                <div class="mini-card mini-right">
                    <div class="mini-icon">❄️</div>
                    <strong>AC Service</strong>
                    <span>4.8 Rating</span>
                </div>

                <div class="rating-card">
                    <div class="rating-users">
                        <span>D</span>
                        <span>R</span>
                        <span>A</span>
                    </div>
                    <div>
                        <strong>10.000+</strong>
                        <p>Pelanggan Puas</p>
                    </div>
                    <b>★ 4.8/5</b>
                </div>
            </div>
        </div>
    </section>

    <section class="jk-section" id="categories">
        <div class="jk-container">
            <div class="jk-section-head">
                <h2>Kategori Populer</h2>
                <a href="#services">Lihat Semua</a>
            </div>

            <div class="jk-category-grid">
                @foreach ($categories as $category)
                    <a href="#services" class="jk-category-card" data-category-filter="{{ strtolower($category['name']) }}">
                        <div class="jk-category-icon {{ $category['color'] }}">
                            @if ($category['icon'] === 'cleaning')
                                <svg viewBox="0 0 48 48" fill="none"><path d="M30 8L36 14L19 31L13 25L30 8Z" fill="currentColor"/><path d="M14 26L24 36L19 41C16 44 11 44 8 41L6 39L14 26Z" fill="currentColor" opacity=".65"/></svg>
                            @elseif ($category['icon'] === 'repair')
                                <svg viewBox="0 0 48 48" fill="none"><path d="M15 11L24 20L20 24L11 15L15 11Z" fill="currentColor"/><path d="M22 26L33 37C35 39 38 39 40 37C42 35 42 32 40 30L29 19L22 26Z" fill="currentColor" opacity=".7"/></svg>
                            @elseif ($category['icon'] === 'beauty')
                                <svg viewBox="0 0 48 48" fill="none"><circle cx="24" cy="18" r="8" fill="currentColor" opacity=".65"/><path d="M12 39C15 31 19 28 24 28C29 28 33 31 36 39" stroke="currentColor" stroke-width="4" stroke-linecap="round"/></svg>
                            @elseif ($category['icon'] === 'laundry')
                                <svg viewBox="0 0 48 48" fill="none"><rect x="13" y="7" width="22" height="34" rx="4" fill="currentColor" opacity=".55"/><circle cx="24" cy="28" r="8" fill="currentColor"/><path d="M18 12H30" stroke="white" stroke-width="3" stroke-linecap="round"/></svg>
                            @elseif ($category['icon'] === 'moving')
                                <svg viewBox="0 0 48 48" fill="none"><path d="M6 17H29V34H6V17Z" fill="currentColor"/><path d="M29 23H38L43 29V34H29V23Z" fill="currentColor" opacity=".7"/><circle cx="15" cy="36" r="4" fill="currentColor" opacity=".45"/><circle cx="36" cy="36" r="4" fill="currentColor" opacity=".45"/></svg>
                            @elseif ($category['icon'] === 'ac')
                                <svg viewBox="0 0 48 48" fill="none"><rect x="8" y="14" width="32" height="14" rx="3" fill="currentColor"/><path d="M14 33H34" stroke="currentColor" stroke-width="4" stroke-linecap="round" opacity=".5"/><path d="M19 36V41M24 36V43M29 36V41" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                            @elseif ($category['icon'] === 'book')
                                <svg viewBox="0 0 48 48" fill="none"><path d="M10 10H22C24 10 24 12 24 14V39C23 37 21 36 19 36H10V10Z" fill="currentColor"/><path d="M38 10H26C24 10 24 12 24 14V39C25 37 27 36 29 36H38V10Z" fill="currentColor" opacity=".7"/></svg>
                            @else
                                <svg viewBox="0 0 48 48" fill="none"><path d="M10 25L15 15H33L38 25H41V34H7V25H10Z" fill="currentColor"/><circle cx="16" cy="35" r="4" fill="currentColor" opacity=".5"/><circle cx="32" cy="35" r="4" fill="currentColor" opacity=".5"/></svg>
                            @endif
                        </div>
                        <span>{{ $category['name'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    <section class="jk-section" id="services">
        <div class="jk-container">
            <div class="jk-section-head">
                <h2>Layanan Pilihan</h2>
                <a href="javascript:void(0)">Lihat Semua</a>
            </div>

            <div class="jk-service-grid" data-customer-service-list>
                @foreach ($services as $service)
                    <article
                        class="jk-service-card"
                        data-customer-service-card
                        data-name="{{ strtolower($service['title'] . ' ' . $service['provider']) }}"
                    >
                        <div class="service-art {{ $service['type'] }}">
                            <span>Top Rated</span>
                            <div class="service-art-person"></div>
                        </div>

                        <div class="service-info">
                            <div class="service-title">
                                <h3>{{ $service['title'] }}</h3>
                                <b>★ {{ $service['rating'] }}</b>
                            </div>

                            <p>{{ $service['provider'] }}</p>
                            <strong>Mulai dari {{ $service['price'] }}</strong>
                        </div>
                    </article>
                @endforeach
            </div>

            <div class="search-empty" data-search-empty hidden>
                Layanan yang kamu cari tidak ditemukan.
            </div>
        </div>
    </section>

    <section class="jk-section jk-how" id="how-it-works">
        <div class="jk-container">
            <div class="jk-center-title">
                <h2>Cara Kerja JasaKu</h2>
            </div>

            <div class="jk-how-grid">
                <div class="jk-how-card">
                    <div class="jk-how-icon">🔎</div>
                    <h3>Cari Layanan</h3>
                    <p>Temukan layanan yang kamu butuhkan dengan cepat.</p>
                </div>

                <div class="jk-how-card">
                    <div class="jk-how-icon">📅</div>
                    <h3>Pilih & Pesan</h3>
                    <p>Pilih provider terbaik dan tentukan jadwal.</p>
                </div>

                <div class="jk-how-card">
                    <div class="jk-how-icon">💳</div>
                    <h3>Bayar Aman</h3>
                    <p>Lakukan pembayaran dengan aman dan mudah.</p>
                </div>

                <div class="jk-how-card">
                    <div class="jk-how-icon">✅</div>
                    <h3>Selesai</h3>
                    <p>Nikmati layanan dan berikan ulasan.</p>
                </div>
            </div>

            <div class="jk-feature-panel">
                <div><strong>Provider Terverifikasi</strong><span>Aman dan terpercaya</span></div>
                <div><strong>Harga Transparan</strong><span>Tidak ada biaya tersembunyi</span></div>
                <div><strong>Pembayaran Aman</strong><span>Transaksi lebih nyaman</span></div>
                <div><strong>Layanan Bergaransi</strong><span>Kualitas lebih terjaga</span></div>
            </div>
        </div>
    </section>

    <section class="jk-section jk-testimonial">
        <div class="jk-container">
            <div class="jk-center-title">
                <h2>Apa Kata Mereka?</h2>
            </div>

            <div class="jk-testimonial-grid">
                <article>
                    <p>“Sangat mudah digunakan dan petugasnya ramah. Rumah jadi bersih dan rapi.”</p>
                    <div><span>D</span><strong>Dewi Lestari</strong></div>
                </article>

                <article>
                    <p>“AC normal lagi dan teknisinya datang tepat waktu. Harganya juga terjangkau.”</p>
                    <div><span>R</span><strong>Rizky Pratama</strong></div>
                </article>

                <article>
                    <p>“Pindahan jadi praktis dan aman. Timnya cepat dan hati-hati.”</p>
                    <div><span>A</span><strong>Andi Wijaya</strong></div>
                </article>
            </div>
        </div>
    </section>

    <section class="jk-cta-section">
        <div class="jk-container">
            <div class="jk-cta">
                <div>
                    <h2>Siap menikmati layanan terbaik?</h2>
                    <p>Pesan sekarang di JasaKu dan rasakan kemudahannya.</p>
                </div>

                <a href="javascript:void(0)">Pesan Sekarang →</a>
            </div>
        </div>
    </section>
</main>