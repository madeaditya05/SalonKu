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
@endphp

<header class="admin-topbar">
    <div class="admin-topbar-left">
        <button class="admin-mobile-menu-btn" id="mobileSidebarToggle" type="button">
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16"></path>
                <path d="M4 12h16"></path>
                <path d="M4 17h16"></path>
            </svg>
        </button>

        <h1>@yield('page_title', 'Dashboard')</h1>
    </div>

    <div class="admin-topbar-right">
        <a href="javascript:void(0)" class="admin-help-btn">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"></path>
                <path d="M12 17h.01"></path>
            </svg>
            Need help
        </a>

        <button class="admin-topbar-icon notification-btn" type="button">
            <svg viewBox="0 0 24 24">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"></path>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
            </svg>
            <span class="notification-dot">3</span>
        </button>

        <a href="{{ url('/') }}" class="admin-visit-btn">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"></circle>
                <path d="M3 12h18"></path>
                <path d="M12 3a14 14 0 0 1 0 18"></path>
                <path d="M12 3a14 14 0 0 0 0 18"></path>
            </svg>
            Visit Website
        </a>

        <div class="admin-profile-dropdown" id="profileDropdown">
            <button class="admin-profile-btn" id="profileToggle" type="button">
                <span class="admin-profile-avatar">
                    @if ($imageUrl)
                        <img src="{{ $imageUrl }}" alt="{{ $adminName }}">
                    @else
                        {{ $initials }}
                    @endif
                </span>

                <svg viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </button>

            <div class="admin-profile-menu" id="profileMenu">
                <div class="admin-profile-head">
                    <div class="admin-profile-head-avatar">
                        @if ($imageUrl)
                            <img src="{{ $imageUrl }}" alt="{{ $adminName }}">
                        @else
                            {{ $initials }}
                        @endif
                    </div>

                    <div>
                        <strong>{{ $adminName }}</strong>
                        <span>{{ $adminEmail }}</span>
                    </div>
                </div>

                @if (\Illuminate\Support\Facades\Route::has('admin.profile'))
                    <a href="{{ route('admin.profile') }}">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 21a8 8 0 1 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        My Profile
                    </a>
                @endif

                @if (\Illuminate\Support\Facades\Route::has('admin.logout'))
                    <form action="{{ route('admin.logout') }}" method="POST">
                        @csrf
                        <button type="submit">
                            <svg viewBox="0 0 24 24">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                                <path d="m16 17 5-5-5-5"></path>
                                <path d="M21 12H9"></path>
                            </svg>
                            Logout
                        </button>
                    </form>
                @endif
            </div>
        </div>
    </div>
</header>