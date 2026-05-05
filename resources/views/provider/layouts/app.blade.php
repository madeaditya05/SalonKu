<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>@yield('title', 'Welcome to Truely Sell')</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="{{ asset('provider/css/provider-landing.css') }}">

    @stack('styles')
</head>
<body
    data-open-register="{{ $errors->register->any() || session('register_success') ? 'true' : 'false' }}"
    data-open-signin="{{ $errors->signin->any() ? 'true' : 'false' }}"
>
    @include('provider.partials.topbar')

    <main>
        @yield('content')
    </main>

    @include('provider.partials.footer')

    @include('provider.partials.auth.register-modal')
    @include('provider.partials.auth.signin-modal')

    <script src="{{ asset('provider/js/provider-landing.js') }}"></script>

    @stack('scripts')
</body>
</html>