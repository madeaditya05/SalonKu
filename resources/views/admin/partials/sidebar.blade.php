@php
    $authUser = auth()->user();
    $adminName = $authUser->name ?? 'Demo Admin';
    $adminEmail = $authUser->email ?? 'admin@mail.com';

    $parts = collect(explode(' ', trim($adminName)))->filter()->values();

    $initials = $parts->count() >= 2
        ? strtoupper(substr($parts[0], 0, 1) . substr($parts[1], 0, 1))
        : strtoupper(substr($adminName, 0, 1));

    $image = $authUser->image ?? null;
    $imageUrl = null;

    if (!empty($image)) {
        $image = ltrim($image, '/');

        if (\Illuminate\Support\Str::startsWith($image, ['http://', 'https://'])) {
            $imageUrl = $image;
        } elseif (\Illuminate\Support\Str::startsWith($image, 'storage/')) {
            $imageUrl = asset($image);
        } else {
            $imageUrl = asset('storage/' . $image);
        }
    }

    $menuSections = [
        [
            'title' => 'Main Menu',
            'items' => [
                [
                    'label' => 'Dashboard',
                    'route' => 'admin.dashboard',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin/dashboard'),
                    'active' => request()->routeIs('admin.dashboard'),
                    'subtitle' => 'Overview',
                    'keywords' => 'dashboard overview home',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M3 12 12 4l9 8"></path><path d="M5 10v10h14V10"></path><path d="M9 20v-6h6v6"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Application',
            'items' => [
                [
                    'label' => 'Bookings',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.bookings.index') ? route('admin.bookings.index') : '#',
                    'active' => request()->routeIs('admin.bookings.*'),
                    'keywords' => 'bookings booking orders',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M5 5h14v16H5z"></path><path d="M3 10h18"></path></svg>',
                ],
                [
                    'label' => 'Calendar',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.calendar.index') ? route('admin.calendar.index') : '#',
                    'active' => request()->routeIs('admin.calendar.*'),
                    'keywords' => 'calendar schedule',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4"></path><path d="M16 2v4"></path><path d="M5 5h14v16H5z"></path><path d="M8 14h3"></path><path d="M13 14h3"></path><path d="M8 18h3"></path></svg>',
                ],
                [
                    'label' => 'Chat',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'chat message',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path></svg>',
                ],
                [
                    'label' => 'WhatsApp Chat',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'whatsapp chat wa',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M20 11.5a8 8 0 0 1-11.8 7L4 20l1.5-4.1A8 8 0 1 1 20 11.5z"></path><path d="M9 9c.5 3 2.5 5 6 6"></path></svg>',
                ],
                [
                    'label' => 'Chatbot',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'chatbot bot ai',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 8V4"></path><rect x="4" y="8" width="16" height="12" rx="3"></rect><path d="M9 13h.01"></path><path d="M15 13h.01"></path><path d="M9 17h6"></path></svg>',
                ],
                [
                    'label' => 'Leads',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'leads prospect',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"></circle><path d="M5.5 21a6.5 6.5 0 0 1 13 0"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Business',
            'items' => [
                [
                    'label' => 'Services',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.services.index') ? route('admin.services.index') : '#',
                    'active' => request()->routeIs('admin.services.*') || request()->routeIs('admin.service-categories.*') || request()->routeIs('admin.service-subcategories.*'),
                    'keywords' => 'services service categories subcategories',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7h16"></path><path d="M4 12h16"></path><path d="M4 17h16"></path></svg>',
                    'children' => [
                        [
                            'label' => 'Services',
                            'url' => \Illuminate\Support\Facades\Route::has('admin.services.index') ? route('admin.services.index') : '#',
                            'active' => request()->routeIs('admin.services.*'),
                        ],
                        [
                            'label' => 'Category',
                            'url' => \Illuminate\Support\Facades\Route::has('admin.service-categories.index') ? route('admin.service-categories.index') : '#',
                            'active' => request()->routeIs('admin.service-categories.*'),
                        ],
                        [
                            'label' => 'Sub Category',
                            'url' => \Illuminate\Support\Facades\Route::has('admin.service-subcategories.index') ? route('admin.service-subcategories.index') : '#',
                            'active' => request()->routeIs('admin.service-subcategories.*'),
                        ],
                    ],
                ],
                [
                    'label' => 'Notification',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'notification alert',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path><path d="M13.73 21a2 2 0 0 1-3.46 0"></path></svg>',
                ],
                [
                    'label' => 'Addons',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'addons extras',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 2v20"></path><path d="M2 12h20"></path></svg>',
                ],
                [
                    'label' => 'Coupon',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.coupons.index') ? route('admin.coupons.index') : '#',
                    'active' => request()->routeIs('admin.coupons.*'),
                    'keywords' => 'coupon coupons promo discount',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Content',
            'items' => [
                [
                    'label' => 'Pages',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'pages menu builder footer builder',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><path d="M14 2v6h6"></path></svg>',
                    'children' => [
                        ['label' => 'All Pages', 'url' => '#', 'active' => false],
                        ['label' => 'Menu Builder', 'url' => '#', 'active' => false],
                        ['label' => 'Footer Builder', 'url' => '#', 'active' => false],
                    ],
                ],
                [
                    'label' => 'Testimonials',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'testimonials reviews',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"></path><path d="M8 9h8"></path><path d="M8 13h5"></path></svg>',
                ],
                [
                    'label' => 'FAQ',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'faq questions',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"></circle><path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"></path><path d="M12 17h.01"></path></svg>',
                ],
                [
                    'label' => 'Newsletter',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'newsletter subscribers campaigns email',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"></path><path d="m4 7 8 6 8-6"></path></svg>',
                ],
                [
                    'label' => 'Blogs',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'blogs articles posts',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h8"></path><path d="M8 17h5"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'People',
            'items' => [
                [
                    'label' => 'Providers',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.providers.index') ? route('admin.providers.index') : '#',
                    'active' => request()->routeIs('admin.providers.*'),
                    'keywords' => 'providers provider sellers',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="9" cy="7" r="4"></circle><path d="M2 21a7 7 0 0 1 14 0"></path><path d="M17 11l2 2 4-4"></path></svg>',
                ],
                [
                    'label' => 'Users',
                    'url' => \Illuminate\Support\Facades\Route::has('admin.users.index') ? route('admin.users.index') : '#',
                    'active' => request()->routeIs('admin.users.*'),
                    'keywords' => 'users customers',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"></circle><path d="M4 21a8 8 0 0 1 16 0"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Finance',
            'items' => [
                [
                    'label' => 'Transactions',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'transactions payment',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"></path><path d="M4 9h16"></path><path d="M8 13h4"></path></svg>',
                ],
                [
                    'label' => 'Provider Earning',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'provider earning income',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>',
                ],
                [
                    'label' => 'Provider Request',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'provider request',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 4h16v16H4z"></path><path d="M8 9h8"></path><path d="M8 13h8"></path></svg>',
                ],
                [
                    'label' => 'Refund',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'refund',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M9 14 4 9l5-5"></path><path d="M4 9h11a5 5 0 0 1 0 10h-1"></path></svg>',
                ],
                [
                    'label' => 'Subscription List',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'subscription list',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"></path><path d="M8 9h8"></path><path d="M8 13h8"></path><path d="M8 17h4"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Support',
            'items' => [
                [
                    'label' => 'Tickets',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'tickets support',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"></path></svg>',
                ],
            ],
        ],
        [
            'title' => 'Settings',
            'items' => [
                [
                    'label' => 'Settings',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'settings general payment',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5z"></path><path d="M19.4 15a1.8 1.8 0 0 0 .36 1.98l.05.05a2 2 0 1 1-2.83 2.83l-.05-.05A1.8 1.8 0 0 0 15 19.4a1.8 1.8 0 0 0-1 .6 1.8 1.8 0 0 0-.4 1.1V21a2 2 0 1 1-4 0v-.1A1.8 1.8 0 0 0 8.6 19a1.8 1.8 0 0 0-1.98.36l-.05.05a2 2 0 1 1-2.83-2.83l.05-.05A1.8 1.8 0 0 0 4.6 15a1.8 1.8 0 0 0-.6-1 1.8 1.8 0 0 0-1.1-.4H3a2 2 0 1 1 0-4h.1A1.8 1.8 0 0 0 5 8.6a1.8 1.8 0 0 0-.36-1.98l-.05-.05a2 2 0 1 1 2.83-2.83l.05.05A1.8 1.8 0 0 0 9 4.6a1.8 1.8 0 0 0 1-.6 1.8 1.8 0 0 0 .4-1.1V3a2 2 0 1 1 4 0v.1A1.8 1.8 0 0 0 15.4 5a1.8 1.8 0 0 0 1.98-.36l.05-.05a2 2 0 1 1 2.83 2.83l-.05.05A1.8 1.8 0 0 0 19.4 9a1.8 1.8 0 0 0 .6 1 1.8 1.8 0 0 0 1.1.4h.1a2 2 0 1 1 0 4h-.1A1.8 1.8 0 0 0 19.4 15z"></path></svg>',
                    'children' => [
                        ['label' => 'General Settings', 'url' => '#', 'active' => false],
                        ['label' => 'Payment Settings', 'url' => '#', 'active' => false],
                    ],
                ],
                [
                    'label' => 'Roles & Permissions',
                    'url' => '#',
                    'active' => false,
                    'keywords' => 'roles permissions',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path></svg>',
                ],
            ],
        ],
    ];

    $currentItem = $menuSections[0]['items'][0];

    foreach ($menuSections as $section) {
        foreach ($section['items'] as $item) {
            if (!empty($item['active'])) {
                $currentItem = $item;
                break 2;
            }

            foreach (($item['children'] ?? []) as $child) {
                if (!empty($child['active'])) {
                    $currentItem = [
                        'label' => $child['label'],
                        'subtitle' => $item['label'],
                        'url' => $child['url'],
                        'icon' => $item['icon'],
                    ];
                    break 3;
                }
            }
        }
    }
@endphp

<aside class="admin-sidebar" id="sidebar">
    <div class="admin-sidebar-header">
        <a href="{{ \Illuminate\Support\Facades\Route::has('admin.dashboard') ? route('admin.dashboard') : url('/admin/dashboard') }}" class="admin-sidebar-brand">
            <span class="admin-brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M4 4h16v16H4z"></path>
                    <path d="M7 7h7l3 3v7H10l-3-3V7z"></path>
                    <path d="M8 8l8 8"></path>
                </svg>
            </span>

            <span class="admin-brand-text">JasaKu.</span>
        </a>

        <button class="admin-sidebar-toggle-btn" id="sidebarToggle" type="button">
            <svg viewBox="0 0 24 24">
                <path d="M9 6h11"></path>
                <path d="M9 12h11"></path>
                <path d="M9 18h11"></path>
                <path d="M4 6h.01"></path>
                <path d="M4 12h.01"></path>
                <path d="M4 18h.01"></path>
            </svg>
        </button>
    </div>

    <div class="admin-sidebar-search">
        <svg viewBox="0 0 24 24">
            <circle cx="11" cy="11" r="7"></circle>
            <path d="m21 21-4.3-4.3"></path>
        </svg>

        <input type="text" id="sidebarSearchInput" placeholder="Search menu" autocomplete="off">

        <button type="button" id="sidebarSearchClear">×</button>
    </div>

    <div class="admin-current-open" id="currentOpenBox">
        <p>Currently Open</p>

        <a href="{{ $currentItem['url'] ?? '#' }}" class="admin-current-link">
            <span class="admin-menu-icon">{!! $currentItem['icon'] !!}</span>

            <span>
                <strong>{{ $currentItem['label'] }}</strong>
                <small>{{ $currentItem['subtitle'] ?? 'Overview' }}</small>
            </span>
        </a>
    </div>

    <div class="admin-sidebar-scroll" id="sidebarScroll">
        <nav class="admin-sidebar-menu" id="sidebarMenu">
            @foreach ($menuSections as $section)
                <p class="admin-menu-title" data-section-title>{{ $section['title'] }}</p>

                @foreach ($section['items'] as $item)
                    @php
                        $hasChildren = !empty($item['children']);
                        $isOpen = $hasChildren && ($item['active'] || collect($item['children'])->contains(fn ($child) => !empty($child['active'])));
                    @endphp

                    @if ($hasChildren)
                        <div class="admin-menu-group admin-menu-search-item {{ $isOpen ? 'open' : '' }}" data-keywords="{{ $item['keywords'] ?? $item['label'] }}">
                            <button type="button" class="admin-menu-item admin-menu-parent {{ $item['active'] ? 'active' : '' }}" data-submenu-toggle>
                                <span class="admin-menu-icon">{!! $item['icon'] !!}</span>
                                <span class="admin-menu-label">{{ $item['label'] }}</span>
                                <span class="admin-menu-arrow">›</span>
                            </button>

                            <div class="admin-submenu">
                                @foreach ($item['children'] as $child)
                                    <a href="{{ $child['url'] }}" class="{{ $child['active'] ? 'active' : '' }}">
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a href="{{ $item['url'] }}"
                           class="admin-menu-item admin-menu-search-item {{ $item['active'] ? 'active' : '' }}"
                           data-keywords="{{ $item['keywords'] ?? $item['label'] }}">
                            <span class="admin-menu-icon">{!! $item['icon'] !!}</span>
                            <span class="admin-menu-label">{{ $item['label'] }}</span>
                        </a>
                    @endif
                @endforeach
            @endforeach

            <div class="admin-menu-empty" id="sidebarSearchEmpty">
                <strong>No menu found</strong>
                <span>Coba keyword lain.</span>
            </div>
        </nav>
    </div>

    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-user">
            <div class="admin-sidebar-avatar">
                @if ($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $adminName }}">
                @else
                    {{ $initials }}
                @endif
            </div>

            <div class="admin-sidebar-user-info">
                <strong>{{ $adminName }}</strong>
                <span>{{ $adminEmail }}</span>
            </div>

            @if (\Illuminate\Support\Facades\Route::has('admin.logout'))
                <form action="{{ route('admin.logout') }}" method="POST">
                    @csrf

                    <button type="submit">
                        <svg viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <path d="m16 17 5-5-5-5"></path>
                            <path d="M21 12H9"></path>
                        </svg>
                    </button>
                </form>
            @endif
        </div>
    </div>
</aside>