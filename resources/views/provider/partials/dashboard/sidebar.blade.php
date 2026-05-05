@php
    use Illuminate\Support\Str;

    $authUser = auth()->user();

    $providerProfile = $authUser?->providerProfile;
    $isDocumentVerified = optional($providerProfile)->document_status === 'verified';

    $lockedClass = $isDocumentVerified ? '' : 'sidebar-locked';

    $menuUrl = function ($url) use ($isDocumentVerified) {
        return $isDocumentVerified ? $url : route('provider.profile');
    };

    $userName = $authUser->name ?? 'Provider User';
    $userEmail = $authUser->email ?? 'provider@mail.com';

    $nameParts = collect(explode(' ', trim($userName)))->filter()->values();

    $userInitials = $nameParts->count() >= 2
        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
        : strtoupper(substr($userName, 0, 1));

    $documentStatus = optional($providerProfile)->document_status ?? 'pending';

    $profileImage = optional($providerProfile)->image;
    $profileImageUrl = null;

    if (!empty($profileImage)) {
        $profileImage = ltrim($profileImage, '/');

        if (Str::startsWith($profileImage, ['http://', 'https://'])) {
            $profileImageUrl = $profileImage;
        } elseif (Str::startsWith($profileImage, 'storage/')) {
            $profileImageUrl = asset($profileImage);
        } else {
            $profileImageUrl = asset('storage/' . $profileImage);
        }
    }

    $sidebarSections = [
        [
            'title' => 'Main Menu',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'Dashboard',
                    'subtitle' => 'Overview',
                    'url' => route('provider.dashboard'),
                    'active' => ['provider.dashboard'],
                    'locked' => false,
                    'keywords' => 'dashboard home overview beranda',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M3 12 12 4l9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Leads',
                    'subtitle' => 'Customers',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.leads.*'],
                    'locked' => true,
                    'keywords' => 'leads customer prospect pelanggan',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Finance',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'Transaction',
                    'subtitle' => 'Payments',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.transactions.*', 'provider.transaction.*'],
                    'locked' => true,
                    'keywords' => 'transaction transaksi payment pembayaran invoice',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M4 9h16"/><path d="M8 13h4"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Payout',
                    'subtitle' => 'Withdraw',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.payout.*', 'provider.payouts.*'],
                    'locked' => true,
                    'keywords' => 'payout withdraw saldo penarikan earning',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7h16v10H4z"/><path d="M8 7V5h8v2"/><path d="M12 10v4"/><path d="M9 13h6"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Business',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'My Service',
                    'subtitle' => 'Services',
                    'url' => $menuUrl(route('provider.services.index')),
                    'active' => ['provider.services.*'],
                    'locked' => true,
                    'keywords' => 'my service layanan jasa service',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Bookings',
                    'subtitle' => 'Orders',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.bookings.*', 'provider.booking.*'],
                    'locked' => true,
                    'keywords' => 'bookings booking pesanan order appointment',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><path d="M3 10h18"/><path d="M5 5h14v16H5z"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Calendar',
                    'subtitle' => 'Schedule',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.calendar.*'],
                    'locked' => true,
                    'keywords' => 'calendar jadwal schedule agenda',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><path d="M5 5h14v16H5z"/><path d="M8 13h3M13 13h3M8 17h3"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Branch',
                    'subtitle' => 'Locations',
                    'url' => $menuUrl(route('provider.branch.index')),
                    'active' => ['provider.branch.*'],
                    'locked' => true,
                    'keywords' => 'branch cabang lokasi outlet',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h10v18"/><path d="M16 8h2a2 2 0 0 1 2 2v11"/><path d="M8 7h4M8 11h4M8 15h4"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Staffs',
                    'subtitle' => 'Employees',
                    'url' => $menuUrl(route('provider.staffs.index')),
                    'active' => ['provider.staffs.*'],
                    'locked' => true,
                    'keywords' => 'staffs staff karyawan pegawai employee',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Marketing',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'Subscription',
                    'subtitle' => 'Plan',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.subscription.*', 'provider.subscriptions.*'],
                    'locked' => true,
                    'keywords' => 'subscription paket langganan plan',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M20 13V6a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v7"/><path d="M2 19h20"/><path d="M8 9h8"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Reviews',
                    'subtitle' => 'Ratings',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.reviews.*', 'provider.review.*'],
                    'locked' => true,
                    'keywords' => 'reviews rating ulasan feedback',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="m12 2 3 7h7l-5.5 4.5L18 21l-6-4-6 4 1.5-7.5L2 9h7z"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Support',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'Chat',
                    'subtitle' => 'Messages',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.chat.*', 'provider.chats.*'],
                    'locked' => true,
                    'keywords' => 'chat message pesan inbox',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Notification',
                    'subtitle' => 'Alerts',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.notifications.*', 'provider.notification.*'],
                    'locked' => true,
                    'keywords' => 'notification notifications alert pemberitahuan notifikasi',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                ],
                [
                    'type' => 'link',
                    'label' => 'Tickets',
                    'subtitle' => 'Support',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.tickets.*', 'provider.ticket.*'],
                    'locked' => true,
                    'keywords' => 'tickets ticket support bantuan komplain',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"/><path d="M9 9h6M9 15h6"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Access',
            'items' => [
                [
                    'type' => 'link',
                    'label' => 'Roles & Permissions',
                    'subtitle' => 'Access Control',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.roles.*', 'provider.permissions.*', 'provider.roles-permissions.*'],
                    'locked' => true,
                    'keywords' => 'roles permissions role permission akses hak akses',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3 4 7v6c0 5 3.5 7.5 8 8 4.5-.5 8-3 8-8V7l-8-4z"/><path d="M9 12l2 2 4-4"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Preferences',
            'items' => [
                [
                    'type' => 'group',
                    'label' => 'Settings',
                    'subtitle' => 'Preferences',
                    'active' => ['provider.settings.*', 'provider.profile', 'provider.profile.*'],
                    'locked' => false,
                    'keywords' => 'settings setting pengaturan profile password account akun',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6V22a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1H2a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6V2a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.18.34.4.68.6 1H22a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-.6 1Z"/></svg>',
                    'children' => [
                        [
                            'label' => 'My Profile',
                            'url' => route('provider.profile'),
                            'active' => ['provider.profile', 'provider.profile.*'],
                            'locked' => false,
                            'keywords' => 'profile edit profile dokumen password akun',
                        ],
                        [
                            'label' => 'General Settings',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.general'],
                            'locked' => true,
                            'keywords' => 'general settings pengaturan umum',
                        ],
                        [
                            'label' => 'Payment Settings',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.payment'],
                            'locked' => true,
                            'keywords' => 'payment settings pembayaran',
                        ],
                        [
                            'label' => 'Notification Settings',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.notification'],
                            'locked' => true,
                            'keywords' => 'notification settings notifikasi',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $isActive = function ($item) {
        $patterns = $item['active'] ?? [];

        if (empty($patterns)) {
            return false;
        }

        return request()->routeIs(...$patterns);
    };

    $hasActiveChild = function ($item) {
        foreach (($item['children'] ?? []) as $child) {
            $patterns = $child['active'] ?? [];

            if (!empty($patterns) && request()->routeIs(...$patterns)) {
                return true;
            }
        }

        return false;
    };

    $currentItem = null;

    foreach ($sidebarSections as $section) {
        foreach ($section['items'] as $item) {
            if (($item['type'] ?? 'link') === 'link' && $isActive($item)) {
                $currentItem = $item;
                break 2;
            }

            if (($item['type'] ?? 'link') === 'group' && ($isActive($item) || $hasActiveChild($item))) {
                foreach (($item['children'] ?? []) as $child) {
                    $patterns = $child['active'] ?? [];

                    if (!empty($patterns) && request()->routeIs(...$patterns)) {
                        $currentItem = [
                            'type' => 'link',
                            'label' => $child['label'],
                            'subtitle' => $item['label'],
                            'url' => $child['url'],
                            'active' => $child['active'] ?? [],
                            'locked' => $child['locked'] ?? false,
                            'keywords' => $child['keywords'] ?? $child['label'],
                            'icon' => $item['icon'],
                        ];

                        break 3;
                    }
                }

                $currentItem = $item;
                break 2;
            }
        }
    }

    if (! $currentItem) {
        $currentItem = $sidebarSections[0]['items'][0];
    }
@endphp

<aside class="provider-sidebar" id="providerSidebar">
    <div class="sidebar-brand-row">
        <a href="{{ route('provider.dashboard') }}" class="sidebar-brand">
            <span class="brand-mark">
                <svg viewBox="0 0 24 24">
                    <path d="M4 4h16v16H4z"/>
                    <path d="M7 7h7l3 3v7H10l-3-3V7z"/>
                    <path d="M8 8l8 8"/>
                </svg>
            </span>

            <span class="brand-name">JasaKu.</span>
        </a>

        <button class="sidebar-collapse-btn" id="sidebarToggle" type="button" title="Hide sidebar">
            <svg viewBox="0 0 24 24">
                <path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>
            </svg>
        </button>
    </div>

    <div class="sidebar-search" id="providerSidebarSearchBox">
        <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7"/>
            <path d="m21 21-4.3-4.3"/>
        </svg>

        <input
            type="text"
            id="providerSidebarSearch"
            placeholder="Search menu"
            autocomplete="off"
        >

        <button type="button" id="providerSidebarSearchClear" class="sidebar-search-clear" title="Clear search">
            ×
        </button>
    </div>

    <div class="sidebar-current-wrap" id="providerSidebarCurrent">
        <p class="sidebar-current-label">Currently Open</p>

        <a href="{{ $currentItem['url'] ?? route('provider.dashboard') }}"
           class="sidebar-current-menu {{ ($currentItem['locked'] ?? false) && !$isDocumentVerified ? 'sidebar-locked' : '' }}">
            <span class="sidebar-icon">
                {!! $currentItem['icon'] !!}
            </span>

            <span class="sidebar-current-text">
                <strong>{{ $currentItem['label'] }}</strong>
                <small>{{ $currentItem['subtitle'] ?? 'Current menu' }}</small>
            </span>
        </a>
    </div>

    <div class="sidebar-menu-scroll" id="providerSidebarMenuScroll">
        <nav class="sidebar-nav" id="providerSidebarNav">
            @foreach ($sidebarSections as $section)
                <p class="sidebar-section-label" data-sidebar-section>{{ $section['title'] }}</p>

                @foreach ($section['items'] as $item)
                    @php
                        $itemType = $item['type'] ?? 'link';
                        $itemActive = $isActive($item);
                        $groupActive = $itemType === 'group' && ($itemActive || $hasActiveChild($item));
                        $itemLocked = ($item['locked'] ?? false) && !$isDocumentVerified;
                    @endphp

                    @if ($itemType === 'group')
                        <div
                            class="sidebar-group sidebar-menu-item {{ $groupActive ? 'open' : '' }}"
                            data-menu-keywords="{{ $item['keywords'] ?? $item['label'] }}"
                        >
                            <button
                                type="button"
                                class="sidebar-link sidebar-parent {{ $groupActive ? 'active' : '' }} {{ $itemLocked ? $lockedClass : '' }}"
                                data-submenu-toggle
                            >
                                <span class="sidebar-icon">
                                    {!! $item['icon'] !!}
                                </span>

                                <span class="sidebar-text">{{ $item['label'] }}</span>
                                <span class="sidebar-arrow">›</span>
                            </button>

                            <div class="sidebar-submenu">
                                @foreach (($item['children'] ?? []) as $child)
                                    @php
                                        $childPatterns = $child['active'] ?? [];
                                        $childActive = !empty($childPatterns) && request()->routeIs(...$childPatterns);
                                        $childLocked = ($child['locked'] ?? false) && !$isDocumentVerified;
                                    @endphp

                                    <a
                                        href="{{ $child['url'] }}"
                                        class="{{ $childActive ? 'active' : '' }} {{ $childLocked ? 'sidebar-locked' : '' }}"
                                        data-submenu-item
                                        data-menu-keywords="{{ $child['keywords'] ?? $child['label'] }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ $item['url'] }}"
                            class="sidebar-link sidebar-menu-item {{ $itemActive ? 'active' : '' }} {{ $itemLocked ? $lockedClass : '' }}"
                            data-menu-keywords="{{ $item['keywords'] ?? $item['label'] }}"
                        >
                            <span class="sidebar-icon">
                                {!! $item['icon'] !!}
                            </span>

                            <span class="sidebar-text">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach

            <div class="sidebar-search-empty" id="providerSidebarSearchEmpty">
                <strong>No menu found</strong>
                <span>Coba keyword lain.</span>
            </div>
        </nav>
    </div>

    <div class="sidebar-bottom">
        @if (!$isDocumentVerified)
            <div class="sidebar-warning-card">
                <div class="warning-icon">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 9v4"/>
                        <path d="M12 17h.01"/>
                        <path d="M10.3 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.7 3.86a2 2 0 0 0-3.4 0Z"/>
                    </svg>
                </div>

                <div class="warning-content">
                    <span class="warning-status">{{ ucfirst($documentStatus) }}</span>
                    <p>Lengkapi profile agar semua menu terbuka.</p>
                    <a href="{{ route('provider.profile.edit') }}">Complete</a>
                </div>
            </div>
        @endif

        <div class="sidebar-user-card">
            <div class="sidebar-user-avatar">
                @if ($profileImageUrl)
                    <img src="{{ $profileImageUrl }}" alt="{{ $userName }}">
                @else
                    {{ $userInitials }}
                @endif
            </div>

            <div class="sidebar-user-info">
                <strong>{{ $userName }}</strong>
                <span>{{ $userEmail }}</span>
            </div>

            <form action="{{ route('provider.logout') }}" method="POST" class="sidebar-logout-form">
                @csrf

                <button type="submit" title="Logout">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <path d="m16 17 5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>