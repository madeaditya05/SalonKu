<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Provider Dashboard - JasaKu')</title>

    <link rel="stylesheet" href="{{ asset('provider/css/provider-dashboard.css') }}">

    @stack('styles')
</head>
<body>
    <div class="provider-app-shell">
        @include('provider.partials.dashboard.sidebar')

        <div class="provider-main-area">
            @include('provider.partials.dashboard.topbar')

            <main class="provider-content-area">
                @yield('content')
            </main>
        </div>

        <div class="provider-sidebar-overlay" id="providerSidebarOverlay"></div>
    </div>

    <script src="{{ asset('provider/js/provider-dashboard.js') }}"></script>

    @stack('scripts')
</body>
</html>