@php
    $countryCodes = [
        ['name' => 'Afghanistan', 'code' => '+93', 'flag' => '🇦🇫'],
        ['name' => 'Albania', 'code' => '+355', 'flag' => '🇦🇱'],
        ['name' => 'Algeria', 'code' => '+213', 'flag' => '🇩🇿'],
        ['name' => 'Andorra', 'code' => '+376', 'flag' => '🇦🇩'],
        ['name' => 'Angola', 'code' => '+244', 'flag' => '🇦🇴'],
        ['name' => 'Argentina', 'code' => '+54', 'flag' => '🇦🇷'],
        ['name' => 'Armenia', 'code' => '+374', 'flag' => '🇦🇲'],
        ['name' => 'Australia', 'code' => '+61', 'flag' => '🇦🇺'],
        ['name' => 'Austria', 'code' => '+43', 'flag' => '🇦🇹'],
        ['name' => 'Azerbaijan', 'code' => '+994', 'flag' => '🇦🇿'],
        ['name' => 'Bahrain', 'code' => '+973', 'flag' => '🇧🇭'],
        ['name' => 'Bangladesh', 'code' => '+880', 'flag' => '🇧🇩'],
        ['name' => 'Belgium', 'code' => '+32', 'flag' => '🇧🇪'],
        ['name' => 'Brazil', 'code' => '+55', 'flag' => '🇧🇷'],
        ['name' => 'Brunei', 'code' => '+673', 'flag' => '🇧🇳'],
        ['name' => 'Cambodia', 'code' => '+855', 'flag' => '🇰🇭'],
        ['name' => 'Canada', 'code' => '+1', 'flag' => '🇨🇦'],
        ['name' => 'China', 'code' => '+86', 'flag' => '🇨🇳'],
        ['name' => 'Denmark', 'code' => '+45', 'flag' => '🇩🇰'],
        ['name' => 'Egypt', 'code' => '+20', 'flag' => '🇪🇬'],
        ['name' => 'France', 'code' => '+33', 'flag' => '🇫🇷'],
        ['name' => 'Germany', 'code' => '+49', 'flag' => '🇩🇪'],
        ['name' => 'Hong Kong', 'code' => '+852', 'flag' => '🇭🇰'],
        ['name' => 'India', 'code' => '+91', 'flag' => '🇮🇳'],
        ['name' => 'Indonesia', 'code' => '+62', 'flag' => '🇮🇩'],
        ['name' => 'Ireland', 'code' => '+353', 'flag' => '🇮🇪'],
        ['name' => 'Italy', 'code' => '+39', 'flag' => '🇮🇹'],
        ['name' => 'Japan', 'code' => '+81', 'flag' => '🇯🇵'],
        ['name' => 'Malaysia', 'code' => '+60', 'flag' => '🇲🇾'],
        ['name' => 'Netherlands', 'code' => '+31', 'flag' => '🇳🇱'],
        ['name' => 'New Zealand', 'code' => '+64', 'flag' => '🇳🇿'],
        ['name' => 'Philippines', 'code' => '+63', 'flag' => '🇵🇭'],
        ['name' => 'Qatar', 'code' => '+974', 'flag' => '🇶🇦'],
        ['name' => 'Russia', 'code' => '+7', 'flag' => '🇷🇺'],
        ['name' => 'Saudi Arabia', 'code' => '+966', 'flag' => '🇸🇦'],
        ['name' => 'Singapore', 'code' => '+65', 'flag' => '🇸🇬'],
        ['name' => 'South Korea', 'code' => '+82', 'flag' => '🇰🇷'],
        ['name' => 'Spain', 'code' => '+34', 'flag' => '🇪🇸'],
        ['name' => 'Thailand', 'code' => '+66', 'flag' => '🇹🇭'],
        ['name' => 'Turkey', 'code' => '+90', 'flag' => '🇹🇷'],
        ['name' => 'United Arab Emirates', 'code' => '+971', 'flag' => '🇦🇪'],
        ['name' => 'United Kingdom', 'code' => '+44', 'flag' => '🇬🇧'],
        ['name' => 'United States', 'code' => '+1', 'flag' => '🇺🇸'],
        ['name' => 'Vietnam', 'code' => '+84', 'flag' => '🇻🇳'],
    ];

    $selectedCountryCode = old('country_code', '+62');

    $selectedCountry = collect($countryCodes)->firstWhere('code', $selectedCountryCode);

    if (! $selectedCountry) {
        $selectedCountry = [
            'name' => 'Indonesia',
            'code' => '+62',
            'flag' => '🇮🇩',
        ];
    }
@endphp

<div class="modal-overlay" id="registerModal">
    <div class="register-modal">
        <button class="modal-close" type="button" data-close-modal>×</button>

        <div class="modal-heading">
            <h2>Registration</h2>
            <p>Enter your credentials to access your account</p>
        </div>

        @if (session('register_success'))
            <div class="form-alert success">
                {{ session('register_success') }}
            </div>
        @endif

        @if ($errors->register->any())
            <div class="form-alert error">
                {{ $errors->register->first() }}
            </div>
        @endif

        <form action="{{ route('provider.register') }}" method="POST">
            @csrf

            <div class="form-group">
                <label>First Name</label>
                <input type="text"
                       name="first_name"
                       value="{{ old('first_name') }}"
                       placeholder="Enter First Name">
            </div>

            <div class="form-group">
                <label>Last Name</label>
                <input type="text"
                       name="last_name"
                       value="{{ old('last_name') }}"
                       placeholder="Enter Last Name">
            </div>

            <div class="form-group">
                <label>User Name</label>
                <input type="text"
                       name="username"
                       value="{{ old('username') }}"
                       placeholder="Enter Name">
            </div>

            <div class="form-group">
                <label>Email</label>
                <input type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="Enter Email">
            </div>

            <div class="form-group">
                <label>Phone Number</label>

                <div class="phone-input custom-phone-input">
                    <input type="hidden"
                           name="country_code"
                           id="countryCodeInput"
                           value="{{ $selectedCountry['code'] }}">

                    <div class="phone-country-picker" id="phoneCountryPicker">
                        <button type="button" class="country-selected-btn" id="countrySelectedBtn">
                            <span id="selectedCountryFlag">{{ $selectedCountry['flag'] }}</span>
                            <span id="selectedCountryCode">{{ $selectedCountry['code'] }}</span>
                            <span class="country-caret">▾</span>
                        </button>

                        <div class="country-dropdown" id="countryDropdown">
                            <div class="country-search-wrap">
                                <input type="text"
                                       id="countrySearchInput"
                                       placeholder="Search country / code">
                            </div>

                            <div class="country-list" id="countryList">
                                @foreach ($countryCodes as $country)
                                    <button type="button"
                                            class="country-option {{ $selectedCountry['code'] === $country['code'] ? 'active' : '' }}"
                                            data-code="{{ $country['code'] }}"
                                            data-flag="{{ $country['flag'] }}"
                                            data-name="{{ $country['name'] }}">
                                        <span>{{ $country['flag'] }}</span>
                                        <strong>{{ $country['code'] }}</strong>
                                        <small>{{ $country['name'] }}</small>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    <input type="text"
                           name="phone_number"
                           value="{{ old('phone_number') }}"
                           placeholder="Enter Phone Number">
                </div>
            </div>

            <div class="form-group">
                <label>Password</label>

                <div class="password-input">
                    <input type="password"
                           name="password"
                           id="registerPasswordInput"
                           placeholder="Enter Password">

                    <button type="button" data-toggle-password="registerPasswordInput">
                        ⌧
                    </button>
                </div>
            </div>

            <label class="terms-check">
                <input type="checkbox"
                       name="terms"
                       value="1"
                       {{ old('terms') ? 'checked' : '' }}>

                <span>
                    I agree to
                    <a href="#">Terms and Conditions</a>
                    &
                    <a href="#">Privacy Policy</a>
                </span>
            </label>

            <button type="submit" class="signup-submit">
                Sign up
            </button>

            <div class="signin-text">
                Already have an account?
                <a href="#" data-switch-modal="signinModal">Sign in</a>
            </div>
        </form>
    </div>
</div>