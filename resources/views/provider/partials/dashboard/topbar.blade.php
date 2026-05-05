@php
    $authUser = auth()->user();

    $providerName = $authUser->name ?? 'Provider User';
    $providerEmail = $authUser->email ?? 'provider@mail.com';

    $nameParts = collect(explode(' ', trim($providerName)))->filter()->values();

    $initials = $nameParts->count() >= 2
        ? strtoupper(substr($nameParts[0], 0, 1) . substr($nameParts[1], 0, 1))
        : strtoupper(substr($providerName, 0, 1));

    $profile = $authUser?->providerProfile;
    $documentStatus = optional($profile)->document_status ?? 'pending';

    $profileImage = optional($profile)->image;
    $profileImageUrl = null;

    if (!empty($profileImage)) {
        $profileImage = ltrim($profileImage, '/');

        if (\Illuminate\Support\Str::startsWith($profileImage, ['http://', 'https://'])) {
            $profileImageUrl = $profileImage;
        } elseif (\Illuminate\Support\Str::startsWith($profileImage, 'storage/')) {
            $profileImageUrl = asset($profileImage);
        } else {
            $profileImageUrl = asset('storage/' . $profileImage);
        }
    }
@endphp

<header class="provider-topbar">
    <div class="topbar-left">
        <button class="topbar-sidebar-btn" id="mobileSidebarToggle" type="button" aria-label="Open sidebar">
            <svg viewBox="0 0 24 24">
                <path d="M4 7h16M4 12h16M4 17h16"/>
            </svg>
        </button>

        <div class="topbar-title-group">
            <h1>@yield('page_title', 'Dashboard')</h1>
            <p>@yield('page_subtitle', 'Welcome back, manage your provider activity here.')</p>
        </div>
    </div>

    <div class="topbar-right">
        <a href="javascript:void(0)" class="topbar-help-btn">
            <svg viewBox="0 0 24 24">
                <path d="M9.09 9a3 3 0 1 1 5.82 1c0 2-3 2-3 4"/>
                <path d="M12 17h.01"/>
                <circle cx="12" cy="12" r="9"/>
            </svg>
            <span>Need help</span>
        </a>

        <button class="topbar-icon-btn notification-btn" type="button" title="Notifications">
            <svg viewBox="0 0 24 24">
                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/>
                <path d="M13.73 21a2 2 0 0 1-3.46 0"/>
            </svg>
            <span class="notification-badge">3</span>
        </button>

        <a href="{{ url('/') }}" class="visit-site-btn">
            <svg viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="9"/>
                <path d="M3 12h18"/>
                <path d="M12 3a14 14 0 0 1 0 18"/>
                <path d="M12 3a14 14 0 0 0 0 18"/>
            </svg>
            <span>Visit Website</span>
        </a>

        <div class="profile-dropdown" id="profileDropdown">
            <button class="profile-avatar-btn" id="profileToggle" type="button">
                <span class="profile-avatar-circle">
                    @if ($profileImageUrl)
                        <img src="{{ $profileImageUrl }}" alt="{{ $providerName }}">
                    @else
                        {{ $initials }}
                    @endif
                </span>

                <svg viewBox="0 0 24 24">
                    <path d="m6 9 6 6 6-6"/>
                </svg>
            </button>

            <div class="profile-menu" id="profileMenu">
                <div class="profile-menu-head">
                    <div class="profile-menu-avatar">
                        @if ($profileImageUrl)
                            <img src="{{ $profileImageUrl }}" alt="{{ $providerName }}">
                        @else
                            {{ $initials }}
                        @endif
                    </div>

                    <div>
                        <strong>{{ $providerName }}</strong>
                        <span>{{ $providerEmail }}</span>

                        <small class="profile-status-pill {{ $documentStatus }}">
                            {{ ucfirst($documentStatus) }}
                        </small>
                    </div>
                </div>

                <a href="{{ route('provider.dashboard') }}">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 13h8V3H3v10Zm10 8h8V3h-8v18ZM3 21h8v-6H3v6Z"/>
                    </svg>
                    Dashboard
                </a>

                <a href="{{ route('provider.profile') }}">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 21a8 8 0 1 0-16 0"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    My Profile
                </a>

                <form action="{{ route('provider.logout') }}" method="POST">
                    @csrf

                    <button type="submit">
                        <svg viewBox="0 0 24 24">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                            <path d="m16 17 5-5-5-5"/>
                            <path d="M21 12H9"/>
                        </svg>
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>