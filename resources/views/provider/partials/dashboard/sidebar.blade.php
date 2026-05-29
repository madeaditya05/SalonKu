@php
    use Illuminate\Support\Str;
    use App\Models\ProviderProfile;
    use App\Support\ProviderMenuAccess;

    $authUser = auth()->user();
    $canSeeMenu = fn ($item) => ProviderMenuAccess::userCanAccess($authUser, $item['key'] ?? null);
    $chatUnreadCount = $authUser ? \App\Support\ChatUnreadCounter::forUser($authUser) : 0;
    $chatUnreadLabel = $chatUnreadCount > 99 ? '99+' : (string) $chatUnreadCount;

    $providerProfile = $authUser
        ? ProviderProfile::where('user_id', ProviderMenuAccess::providerOwnerId($authUser))->first()
        : null;
    $isDocumentVerified = optional($providerProfile)->document_status === 'verified';

    $menuUrl = function ($url) use ($isDocumentVerified) {
        return $isDocumentVerified ? $url : provider_route('provider.profile');
    };

    $userName = $authUser->name ?? 'Provider User';
    $userEmail = $authUser->email ?? 'provider@mail.com';

    $nameParts = collect(explode(' ', trim($userName)))->filter()->values();

    $userInitials = $nameParts->count() >= 2
        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
        : strtoupper(substr($userName, 0, 1));

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
                    'key' => 'dashboard',
                    'label' => 'Dashboard',
                    'subtitle' => 'Overview',
                    'url' => provider_route('provider.dashboard'),
                    'active' => ['provider.dashboard'],
                    'locked' => false,
                    'keywords' => 'dashboard home overview',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M3 12 12 4l9 8"/><path d="M5 10v10h14V10"/><path d="M9 20v-6h6v6"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'leads',
                    'label' => 'Leads',
                    'subtitle' => 'Customers',
                    'url' => $menuUrl('#'),
                    'active' => ['provider.leads.*'],
                    'locked' => true,
                    'keywords' => 'leads customer prospect',
                    'icon' => '<svg viewBox="0 0 24 24"><circle cx="12" cy="7" r="4"/><path d="M5.5 21a6.5 6.5 0 0 1 13 0"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Finance',
            'items' => [
                [
                    'type' => 'link',
                    'key' => 'payments',
                    'label' => 'Payments',
                    'subtitle' => 'Payments',
                    'url' => $menuUrl(provider_route('provider.payments.index')),
                    'active' => ['provider.payments.*', 'provider.transactions.*', 'provider.transaction.*'],
                    'locked' => true,
                    'keywords' => 'transaction payment invoice',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 5h16v14H4z"/><path d="M4 9h16"/><path d="M8 13h4"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'payout',
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
                    'key' => 'services',
                    'label' => 'My Service',
                    'subtitle' => 'Services',
                    'url' => $menuUrl(provider_route('provider.services.index')),
                    'active' => ['provider.services.*'],
                    'locked' => true,
                    'keywords' => 'my service services menu',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7h16"/><path d="M4 12h16"/><path d="M4 17h16"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'bookings',
                    'label' => 'Today Bookings',
                    'subtitle' => 'Orders',
                    'url' => $menuUrl(provider_route('provider.bookings.index')),
                    'active' => ['provider.bookings.*', 'provider.booking.*'],
                    'locked' => true,
                    'keywords' => 'bookings booking order appointment',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><path d="M3 10h18"/><path d="M5 5h14v16H5z"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'calendar',
                    'label' => 'Staff Calendar',
                    'subtitle' => 'Schedule',
                    'url' => $menuUrl(provider_route('provider.calendar.index')),
                    'active' => ['provider.calendar.*'],
                    'locked' => true,
                    'keywords' => 'calendar schedule agenda',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><path d="M5 5h14v16H5z"/><path d="M8 13h3M13 13h3M8 17h3"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'queue',
                    'label' => 'Queue',
                    'subtitle' => 'Queue',
                    'url' => $menuUrl(provider_route('provider.queue.index')),
                    'active' => ['provider.queue.*'],
                    'locked' => true,
                    'keywords' => 'queue call waiting',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h10"/><path d="M17 15l3 3-3 3"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'walk_in',
                    'label' => 'Walk-in',
                    'subtitle' => 'Offline',
                    'url' => $menuUrl(provider_route('provider.walk-in.index')),
                    'active' => ['provider.walk-in.*'],
                    'locked' => true,
                    'keywords' => 'walk-in walkin offline customer queue',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 3v18"/><path d="M5 8h14"/><path d="M7 21h10"/><path d="M8 8v13M16 8v13"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'branch',
                    'label' => 'Branch',
                    'subtitle' => 'Locations',
                    'url' => $menuUrl(provider_route('provider.branch.index')),
                    'active' => ['provider.branch.*'],
                    'locked' => true,
                    'keywords' => 'branch location outlet',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 21V5a2 2 0 0 1 2-2h10v18"/><path d="M16 8h2a2 2 0 0 1 2 2v11"/><path d="M8 7h4M8 11h4M8 15h4"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'staffs',
                    'label' => 'Staff',
                    'subtitle' => 'Employees',
                    'url' => $menuUrl(provider_route('provider.staffs.index')),
                    'active' => ['provider.staffs.*'],
                    'locked' => true,
                    'keywords' => 'staffs staff karyawan pegawai employee',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'staff_skills',
                    'label' => 'Staff Skills',
                    'subtitle' => 'Services',
                    'url' => $menuUrl(provider_route('provider.staff.skills')),
                    'active' => ['provider.staff.skills'],
                    'locked' => true,
                    'keywords' => 'skill staff service capability',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M20 7 9 18l-5-5"/><path d="M15 7h5v5"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'staff_schedules',
                    'label' => 'Staff Schedule',
                    'subtitle' => 'Availability',
                    'url' => $menuUrl(provider_route('provider.staff.schedules')),
                    'active' => ['provider.staff.schedules'],
                    'locked' => true,
                    'keywords' => 'staff schedule availability working hours',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4"/><path d="M3 10h18"/><path d="M5 5h14v16H5z"/><path d="M8 14h4"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Marketing',
            'items' => [
                [
                    'type' => 'link',
                    'key' => 'subscription',
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
                    'key' => 'reviews',
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
                    'key' => 'chat',
                    'label' => 'Chat',
                    'subtitle' => 'Messages',
                    'url' => provider_route('provider.chat.index'),
                    'active' => ['provider.chat.*', 'provider.chats.*'],
                    'locked' => false,
                    'keywords' => 'chat message inbox',
                    'badge' => $chatUnreadCount,
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M21 15a4 4 0 0 1-4 4H7l-4 4V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'notifications',
                    'label' => 'Notification',
                    'subtitle' => 'Alerts',
                    'url' => provider_route('provider.notifications.index'),
                    'active' => ['provider.notifications.*', 'provider.notification.*'],
                    'locked' => false,
                    'keywords' => 'notification notifications alert',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/></svg>',
                ],
                [
                    'type' => 'link',
                    'key' => 'tickets',
                    'label' => 'Support Help',
                    'subtitle' => 'FAQ & Tickets',
                    'url' => provider_route('provider.tickets.index'),
                    'active' => ['provider.tickets.*', 'provider.ticket.*'],
                    'locked' => false,
                    'keywords' => 'support help faq tickets ticket complaint',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"/><path d="M9 9h6M9 15h6"/></svg>',
                ],
            ],
        ],
        [
            'title' => 'Access',
            'items' => [
                [
                    'type' => 'link',
                    'key' => 'roles_permissions',
                    'label' => 'Roles & Permissions',
                    'subtitle' => 'Access Control',
                    'url' => $menuUrl(provider_route('provider.roles-permissions.index')),
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
                    'key' => 'profile',
                    'label' => 'Settings',
                    'subtitle' => 'Preferences',
                    'active' => ['provider.settings.*', 'provider.profile', 'provider.profile.*'],
                    'locked' => false,
                    'keywords' => 'settings setting profile password account',
                    'icon' => '<svg viewBox="0 0 24 24"><path d="M12 15.5A3.5 3.5 0 1 0 12 8a3.5 3.5 0 0 0 0 7.5Z"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.88l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06A1.7 1.7 0 0 0 15 19.4a1.7 1.7 0 0 0-1 .6V22a2 2 0 0 1-4 0v-.09a1.7 1.7 0 0 0-1-.6 1.7 1.7 0 0 0-1.88.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.6 15a1.7 1.7 0 0 0-.6-1H2a2 2 0 0 1 0-4h.09a1.7 1.7 0 0 0 .6-1 1.7 1.7 0 0 0-.34-1.88l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.6a1.7 1.7 0 0 0 1-.6V2a2 2 0 0 1 4 0v.09a1.7 1.7 0 0 0 1 .6 1.7 1.7 0 0 0 1.88-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.4 9c.18.34.4.68.6 1H22a2 2 0 0 1 0 4h-.09a1.7 1.7 0 0 0-.6 1Z"/></svg>',
                    'children' => [
                        [
                            'label' => 'My Profile',
                            'key' => 'profile',
                            'url' => provider_route('provider.profile'),
                            'active' => ['provider.profile', 'provider.profile.*'],
                            'locked' => false,
                            'keywords' => 'profile edit document password account',
                        ],
                        [
                            'label' => 'General Settings',
                            'key' => 'settings_general',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.general'],
                            'locked' => true,
                            'keywords' => 'general settings',
                        ],
                        [
                            'label' => 'Payment Settings',
                            'key' => 'settings_payment',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.payment'],
                            'locked' => true,
                            'keywords' => 'payment settings',
                        ],
                        [
                            'label' => 'Notification Settings',
                            'key' => 'settings_notification',
                            'url' => $menuUrl('#'),
                            'active' => ['provider.settings.notification'],
                            'locked' => true,
                            'keywords' => 'notification settings',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $menuItemsByKey = collect($sidebarSections)
        ->flatMap(fn ($section) => $section['items'])
        ->keyBy('key');

    $menuItem = function (string $key, ?string $label = null, ?string $subtitle = null, ?string $icon = null) use ($menuItemsByKey) {
        $item = $menuItemsByKey->get($key);

        if (! $item) {
            return null;
        }

        if ($label !== null) {
            $item['label'] = $label;
        }

        if ($subtitle !== null) {
            $item['subtitle'] = $subtitle;
        }

        if ($icon !== null) {
            $item['icon'] = $icon;
        }

        return $item;
    };

    $chatbotItem = [
        'type' => 'link',
        'key' => 'chatbot',
        'label' => 'Chatbot',
        'subtitle' => 'Bot AI',
        'url' => '#',
        'active' => [],
        'locked' => false,
        'keywords' => 'chatbot bot ai assistant otomatis',
        'icon' => '<svg viewBox="0 0 24 24"><path d="M12 8V4"/><rect x="4" y="8" width="16" height="12" rx="3"/><path d="M9 13h.01"/><path d="M15 13h.01"/><path d="M9 17h6"/></svg>',
    ];

    $settingsItem = $menuItem('profile', 'Settings', 'Preferences');

    if ($settingsItem) {
        $settingsItem['children'] = collect($settingsItem['children'] ?? [])
            ->filter(fn ($child) => in_array($child['key'] ?? null, ['profile', 'settings_general', 'settings_payment'], true))
            ->values()
            ->all();
    }

    $sidebarSections = [
        [
            'title' => 'Main Menu',
            'items' => array_values(array_filter([
                $menuItem('dashboard', 'Dashboard', 'Overview'),
            ])),
        ],
        [
            'title' => 'Application',
            'items' => array_values(array_filter([
                $menuItem('bookings', 'Bookings', 'Orders'),
                $menuItem('calendar', 'Calendar', 'Schedule'),
                $menuItem('chat', 'Chat', 'Messages'),
                $chatbotItem,
                $menuItem('leads', 'Leads', 'Customers'),
                $menuItem('queue', 'Queue', 'Queue'),
                $menuItem('walk_in', 'Walk-in', 'Offline'),
            ])),
        ],
        [
            'title' => 'Business',
            'items' => array_values(array_filter([
                $menuItem('services', 'Services', 'Services'),
                $menuItem('notifications', 'Notification', 'Alerts'),
                $menuItem('branch', 'Branch', 'Locations'),
            ])),
        ],
        [
            'title' => 'People',
            'items' => array_values(array_filter([
                $menuItem('staffs', 'Staff', 'Employees'),
                $menuItem('staff_skills', 'Staff Skills', 'Services'),
                $menuItem('staff_schedules', 'Staff Schedule', 'Availability'),
            ])),
        ],
        [
            'title' => 'Finance',
            'items' => array_values(array_filter([
                $menuItem('payments', 'Payments', 'Payments'),
                $menuItem('payout', 'Payout', 'Withdraw'),
            ])),
        ],
        [
            'title' => 'Marketing',
            'items' => array_values(array_filter([
                $menuItem('subscription', 'Subscription', 'Plan'),
                $menuItem('reviews', 'Reviews', 'Reviews'),
            ])),
        ],
        [
            'title' => 'Support',
            'items' => array_values(array_filter([
                $menuItem('tickets', 'Support Help', 'FAQ & Tickets'),
            ])),
        ],
        [
            'title' => 'Settings',
            'items' => array_values(array_filter([
                $settingsItem,
                $menuItem('roles_permissions', 'Roles & Permissions', 'Access Control'),
            ])),
        ],
    ];

    $sidebarSections = collect($sidebarSections)
        ->map(function ($section) use ($canSeeMenu) {
            $section['items'] = collect($section['items'])
                ->map(function ($item) use ($canSeeMenu) {
                    if (($item['type'] ?? 'link') === 'group') {
                        $item['children'] = collect($item['children'] ?? [])
                            ->filter(fn ($child) => $canSeeMenu($child))
                            ->values()
                            ->all();

                        return ($canSeeMenu($item) || !empty($item['children'])) ? $item : null;
                    }

                    return $canSeeMenu($item) ? $item : null;
                })
                ->filter()
                ->values()
                ->all();

            return $section;
        })
        ->filter(fn ($section) => !empty($section['items']))
        ->values()
        ->all();

    $routePatterns = function (array $patterns) {
        return collect($patterns)
            ->flatMap(fn ($pattern) => [
                $pattern,
                Str::startsWith($pattern, 'provider.')
                    ? 'provider-branch.' . Str::after($pattern, 'provider.')
                    : $pattern,
            ])
            ->unique()
            ->values()
            ->all();
    };

    $isActive = function ($item) use ($routePatterns) {
        $patterns = $item['active'] ?? [];

        if (empty($patterns)) {
            return false;
        }

        return request()->routeIs(...$routePatterns($patterns));
    };

    $hasActiveChild = function ($item) use ($routePatterns) {
        foreach (($item['children'] ?? []) as $child) {
            $patterns = $child['active'] ?? [];

            if (!empty($patterns) && request()->routeIs(...$routePatterns($patterns))) {
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

                    if (!empty($patterns) && request()->routeIs(...$routePatterns($patterns))) {
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

<aside class="admin-sidebar provider-admin-sidebar" id="providerSidebar">
    <div class="admin-sidebar-header">
        <a href="{{ provider_route('provider.dashboard') }}" class="admin-sidebar-brand">
            <span class="admin-brand-icon">
                <svg viewBox="0 0 24 24">
                    <path d="M4 4h16v16H4z"/>
                    <path d="M7 7h7l3 3v7H10l-3-3V7z"/>
                    <path d="M8 8l8 8"/>
                </svg>
            </span>

            <span class="admin-brand-text">JasaKu.</span>
        </a>

        <button class="admin-sidebar-toggle-btn" id="sidebarToggle" type="button" aria-label="Collapse provider sidebar">
            <svg viewBox="0 0 24 24">
                <path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>
            </svg>
        </button>
    </div>

    <div class="admin-sidebar-search" id="providerSidebarSearchBox">
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

        <button type="button" id="providerSidebarSearchClear" title="Clear search" aria-label="Clear search">
            &times;
        </button>
    </div>

    <div class="admin-current-open" id="providerSidebarCurrent">
        <p>Currently Open</p>

        <a href="{{ $currentItem['url'] ?? provider_route('provider.dashboard') }}"
           class="admin-current-link">
            <span class="admin-menu-icon">
                {!! $currentItem['icon'] !!}
            </span>

            <span class="admin-current-text">
                <strong>{{ $currentItem['label'] }}</strong>
                <small>{{ $currentItem['subtitle'] ?? 'Current menu' }}</small>
            </span>

            @if (array_key_exists('badge', $currentItem))
                <b class="admin-menu-badge {{ $chatUnreadCount > 0 ? '' : 'is-hidden' }}" data-sidebar-chat-badge>{{ $chatUnreadLabel }}</b>
            @endif
        </a>
    </div>

    <div class="admin-sidebar-scroll" id="providerSidebarMenuScroll">
        <nav class="admin-sidebar-menu" id="providerSidebarNav">
            @foreach ($sidebarSections as $section)
                <p class="admin-menu-title" data-section-title>{{ $section['title'] }}</p>

                @foreach ($section['items'] as $item)
                    @php
                        $itemType = $item['type'] ?? 'link';
                        $itemActive = $isActive($item);
                        $groupActive = $itemType === 'group' && ($itemActive || $hasActiveChild($item));
                    @endphp

                    @if ($itemType === 'group')
                        <div
                            class="admin-menu-group admin-menu-search-item {{ $groupActive ? 'open' : '' }}"
                            data-keywords="{{ $item['keywords'] ?? $item['label'] }}"
                        >
                            <button
                                type="button"
                                class="admin-menu-item admin-menu-parent {{ $groupActive ? 'active' : '' }}"
                                data-submenu-toggle
                            >
                                <span class="admin-menu-icon">
                                    {!! $item['icon'] !!}
                                </span>

                                <span class="admin-menu-label">{{ $item['label'] }}</span>
                                @if (array_key_exists('badge', $item))
                                    <b class="admin-menu-badge {{ $chatUnreadCount > 0 ? '' : 'is-hidden' }}" data-sidebar-chat-badge>{{ $chatUnreadLabel }}</b>
                                @endif
                                <span class="admin-menu-arrow">&rsaquo;</span>
                            </button>

                            <div class="admin-submenu">
                                @foreach (($item['children'] ?? []) as $child)
                                    @php
                                        $childPatterns = $child['active'] ?? [];
                                        $childActive = !empty($childPatterns) && request()->routeIs(...$routePatterns($childPatterns));
                                    @endphp

                                    <a
                                        href="{{ $child['url'] }}"
                                        class="{{ $childActive ? 'active' : '' }}"
                                        data-submenu-item
                                        data-keywords="{{ $child['keywords'] ?? $child['label'] }}"
                                    >
                                        {{ $child['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @else
                        <a
                            href="{{ $item['url'] }}"
                            class="admin-menu-item admin-menu-search-item {{ $itemActive ? 'active' : '' }}"
                            data-keywords="{{ $item['keywords'] ?? $item['label'] }}"
                        >
                            <span class="admin-menu-icon">
                                {!! $item['icon'] !!}
                            </span>

                            <span class="admin-menu-label">{{ $item['label'] }}</span>
                            @if (array_key_exists('badge', $item))
                                <b class="admin-menu-badge {{ $chatUnreadCount > 0 ? '' : 'is-hidden' }}" data-sidebar-chat-badge>{{ $chatUnreadLabel }}</b>
                            @endif
                        </a>
                    @endif
                @endforeach
            @endforeach

            <div class="admin-menu-empty" id="providerSidebarSearchEmpty">
                <strong>No menu found</strong>
                <span>Try another keyword.</span>
            </div>
        </nav>
    </div>

    <div class="admin-sidebar-footer">
        <div class="admin-sidebar-user">
            <div class="admin-sidebar-avatar">
                @if ($profileImageUrl)
                    <img src="{{ $profileImageUrl }}" alt="{{ $userName }}">
                @else
                    {{ $userInitials }}
                @endif
            </div>

            <div class="admin-sidebar-user-info">
                <strong>{{ $userName }}</strong>
                <span>{{ $userEmail }}</span>
            </div>

            <form action="{{ provider_route('provider.logout') }}" method="POST">
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
