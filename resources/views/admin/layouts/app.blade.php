<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Admin Dashboard - JasaKu')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('admin/css/admin-dashboard.css') }}">
    @stack('styles')
</head>
<body class="admin-body">

    <div class="admin-layout" id="adminLayout">
        @include('admin.partials.sidebar')

        <div class="admin-main-wrapper">
            @include('admin.partials.topbar')

            <main class="admin-main-content">
                @yield('content')
            </main>
        </div>
    </div>

    <div class="admin-sidebar-overlay" id="sidebarOverlay"></div>

    <script src="{{ asset('admin/js/admin-layout.js') }}"></script>
    @stack('scripts')
</body>
</html>