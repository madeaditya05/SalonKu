<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Provider Dashboard - JasaKu')</title>

    <link rel="stylesheet" href="{{ asset('provider/css/provider-dashboard.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/css/admin-dashboard.css') }}">

    @stack('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/colorful-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('provider/css/provider-admin-theme.css') }}">
    <link rel="stylesheet" href="{{ asset('provider/css/provider-sidebar-admin-parity.css') }}?v=admin-sidebar-parity-7">
</head>
<body class="admin-body provider-body">
    <div class="provider-app-shell admin-layout" id="providerLayout">
        @include('provider.partials.dashboard.sidebar')

        <div class="provider-main-area admin-main-wrapper">
            @include('provider.partials.dashboard.topbar')

            <main class="provider-content-area admin-main-content">
                @yield('content')
            </main>
        </div>

        <div class="provider-sidebar-overlay admin-sidebar-overlay" id="providerSidebarOverlay"></div>
    </div>

    <script src="https://js.pusher.com/8.4.0/pusher.min.js" defer></script>
    <script src="{{ asset('js/realtime-notifications.js') }}" defer></script>
    <script src="{{ asset('provider/js/provider-dashboard.js') }}" defer></script>

    @stack('scripts')
</body>
</html>
