<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'JasaKu - Customer')</title>

    <link rel="stylesheet" href="{{ asset('customer/css/customer-landing.css') }}?v={{ time() }}">
</head>
<body>
    @include('customer.partials.topbar')

    @yield('content')

    @include('customer.partials.footer')
    @include('customer.partials.auth-modal')

    <script src="{{ asset('customer/js/customer-landing.js') }}?v={{ time() }}"></script>
</body>
</html>