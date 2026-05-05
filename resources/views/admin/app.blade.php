<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - JasaKu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('admin/css/admin-dashboard.css') }}">
</head>
<body>

    <div class="admin-layout">
        @include('admin.partials.sidebar')

        <div class="main-wrapper">
            @include('admin.partials.topbar')

            <main class="main-content">
                @yield('content')
            </main>
        </div>
    </div>

</body>
</html>