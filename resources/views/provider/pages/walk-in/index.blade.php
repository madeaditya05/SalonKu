@extends('provider.layouts.dashboard')

@section('title', 'Walk-in - JasaKu')
@section('page_title', 'Walk-in')
@section('page_subtitle', 'Register offline customers and add them directly to the provider queue.')

@section('content')
@php
    use Illuminate\Support\Str;

    $branches = $branches ?? collect();
    $services = $services ?? collect();
    $staffs = $staffs ?? collect();

    $todayDate = now()->toDateString();
    $selectedBranchId = (string) old('branch_id', '');
    $selectedStaffId = (string) old('staff_id', '');
    $selectedServiceIds = collect(old('service_ids', []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedPaymentType = old('payment_type', 'pay_at_salon');

    $formatMoney = fn ($value) => 'Rp' . number_format((float) ($value ?? 0), 0, ',', '.');
    $formatDuration = fn ($value) => (int) ($value ?? 0) > 0 ? (int) $value . ' minutes' : 'Duration -';

    $paymentTypes = [
        'pay_at_salon' => 'Pay at salon',
        'dp' => 'Down payment',
        'full_payment' => 'Full payment',
    ];
@endphp

<section class="admin-category-page admin-booking-page provider-booking-category-page provider-walkin-category-page">
    <div class="admin-booking-route admin-category-route provider-booking-route provider-walkin-route">
        <div class="admin-breadcrumb">
            <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <strong>Walk-in</strong>
        </div>

        <div class="provider-booking-category-actions provider-walkin-actions provider-walkin-actions-desktop">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Queue
            </a>

            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Today Bookings
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.calendar.index', ['date' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 8h18"></path>
                    <path d="M8 3v3"></path>
                    <path d="M16 3v3"></path>
                    <path d="M5 6h14v15H5z"></path>
                </svg>
                Calendar
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-booking-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="admin-booking-alert danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="admin-booking-alert danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="admin-booking-summary-grid">
        <div class="admin-booking-summary-card pink">
            <span>Active Branches</span>
            <strong>{{ number_format($branches->count()) }}</strong>
            <small>Locations that can accept walk-in customers</small>
        </div>

        <div class="admin-booking-summary-card yellow">
            <span>Available Services</span>
            <strong>{{ number_format($services->count()) }}</strong>
            <small>Services that can be added to the queue</small>
        </div>

        <div class="admin-booking-summary-card blue">
            <span>Active Staff</span>
            <strong>{{ number_format($staffs->count()) }}</strong>
            <small>Can be selected manually or assigned automatically</small>
        </div>

        <div class="admin-booking-summary-card orange">
            <span>Selected Services</span>
            <strong>{{ number_format(count($selectedServiceIds)) }}</strong>
            <small>Filled after form validation</small>
        </div>
    </div>

    <div class="admin-booking-card category-card provider-booking-category-card provider-walkin-category-card">
        <div class="admin-booking-tabs provider-walkin-tabs">
            <a href="{{ provider_route('provider.walk-in.index') }}" class="admin-booking-tab active">
                Input Walk-in
            </a>
            <a href="{{ provider_route('provider.queue.index') }}" class="admin-booking-tab">
                Active Queue
            </a>
            <a href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}" class="admin-booking-tab">
                Bookings
            </a>
            <a href="{{ provider_route('provider.calendar.index', ['date' => $todayDate]) }}" class="admin-booking-tab">
                Staff Calendar
            </a>
        </div>

        <div class="admin-category-add-row provider-booking-category-actions provider-walkin-actions provider-walkin-actions-mobile">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Queue
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Bookings
            </a>
        </div>

        <form class="provider-walkin-form" method="POST" action="{{ provider_route('provider.walk-in.store') }}">
            @csrf

            <fieldset class="provider-walkin-section">
                <legend>Customer</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Customer Name <b>*</b></span>
                        <input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required>
                        @error('customer_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Phone Number</span>
                        <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" autocomplete="tel">
                        @error('customer_phone') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section">
                <legend>Location & Staff</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Branch <b>*</b></span>
                        <select name="branch_id" required>
                            <option value="">Select branch</option>
                            @forelse ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === (string) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @empty
                                <option value="" disabled>No active branch yet</option>
                            @endforelse
                        </select>
                        @error('branch_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Staff</span>
                        <select name="staff_id">
                            <option value="">Any Available Staff</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}" @selected($selectedStaffId === (string) $staff->id)>
                                    {{ $staff->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('staff_id') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section provider-walkin-service-section">
                <legend>Service</legend>

                <div class="provider-walkin-section-head">
                    <div>
                        <strong>Select Services</strong>
                        <span>{{ number_format($services->count()) }} services available</span>
                    </div>

                    <span class="admin-booking-status info">{{ number_format(count($selectedServiceIds)) }} selected</span>
                </div>

                @error('service_ids') <small class="provider-walkin-error">{{ $message }}</small> @enderror
                @error('service_ids.*') <small class="provider-walkin-error">{{ $message }}</small> @enderror

                <div class="provider-walkin-service-grid">
                    @forelse ($services as $service)
                        @php
                            $serviceId = (string) $service->id;
                            $isSelected = in_array($serviceId, $selectedServiceIds, true);
                            $categoryName = $service->serviceCategory->name ?? $service->category ?? 'Service';
                        @endphp

                        <label class="provider-walkin-service-card {{ $isSelected ? 'is-selected' : '' }}">
                            <input
                                type="checkbox"
                                name="service_ids[]"
                                value="{{ $service->id }}"
                                @checked($isSelected)
                            >

                            <span class="provider-walkin-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                            </span>

                            <span class="provider-walkin-service-copy">
                                <strong>{{ $service->title }}</strong>
                                <small>{{ Str::limit($categoryName, 36) }}</small>
                            </span>

                            <span class="provider-walkin-service-meta">
                                <b>{{ $formatMoney($service->price ?? 0) }}</b>
                                <small>{{ $formatDuration($service->estimated_duration ?? $service->duration ?? null) }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="admin-category-mobile-empty admin-booking-mobile-empty provider-walkin-empty">
                            <strong>No active services yet.</strong>
                            <p>Add a service first before creating a walk-in queue entry.</p>
                        </div>
                    @endforelse
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section">
                <legend>Payment</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Payment Type</span>
                        <select name="payment_type">
                            @foreach ($paymentTypes as $key => $label)
                                <option value="{{ $key }}" @selected($selectedPaymentType === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_type') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Notes</span>
                        <textarea name="notes">{{ old('notes') }}</textarea>
                        @error('notes') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <div class="provider-walkin-submit-bar">
                <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Back
                </a>

                <button class="admin-category-add-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                    Add to Queue
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
