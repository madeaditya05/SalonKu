@extends('provider.layouts.dashboard')

@section('title', 'Bookings - JasaKu')
@section('page_title', 'Bookings')
@section('page_subtitle', 'Kelola check-in, start service, complete, cancel, dan no-show dari satu meja kerja.')

@section('content')
@php
    use Illuminate\Support\Str;

    $filters = $filters ?? [
        'status' => request('status', $status ?? 'all'),
        'search' => request('search', $search ?? ''),
        'per_page' => request('per_page', $perPage ?? 10),
        'payment_status' => request('payment_status', 'all'),
        'booking_type' => request('booking_type', 'all'),
        'date_from' => request('date_from'),
        'date_to' => request('date_to'),
        'sort_by' => request('sort_by', 'booking_date'),
        'sort_direction' => request('sort_direction', 'desc'),
    ];

    $statusTabs = $tabs ?? [
        'all' => 'All Bookings',
        'pending_payment' => 'Pending Payment',
        'confirmed' => 'Confirmed',
        'waiting' => 'Waiting',
        'checked_in' => 'Checked In',
        'in_progress' => 'In Progress',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'no_show' => 'No-show',
    ];

    $paymentStatuses = $paymentStatuses ?? [
        'all' => 'All Payments',
        'unpaid' => 'Unpaid',
        'pending' => 'Pending',
        'paid' => 'Paid',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
    ];

    $bookingTypes = $bookingTypes ?? [
        'all' => 'All Modes',
        'scheduled' => 'Scheduled',
        'queue' => 'Queue',
        'walk_in' => 'Walk In',
    ];

    $bookingCollection = $bookings ?? collect();
    $hasPaginator = is_object($bookingCollection)
        && method_exists($bookingCollection, 'links')
        && method_exists($bookingCollection, 'firstItem');
    $firstItem = $hasPaginator ? ($bookingCollection->firstItem() ?? 0) : 0;
    $lastItem = $hasPaginator ? ($bookingCollection->lastItem() ?? 0) : (is_countable($bookingCollection) ? count($bookingCollection) : 0);
    $totalItem = $hasPaginator ? $bookingCollection->total() : (is_countable($bookingCollection) ? count($bookingCollection) : 0);

    $currentStatus = $filters['status'] ?? 'all';
    $sortBy = $sortBy ?? ($filters['sort_by'] ?? 'booking_date');
    $sortDirection = $sortDirection ?? ($filters['sort_direction'] ?? 'desc');

    $cleanQuery = function (array $query) {
        return collect($query)
            ->reject(function ($value, $key) {
                if ($value === null || $value === '') {
                    return true;
                }

                if (in_array($key, ['status', 'payment_status', 'booking_type'], true) && $value === 'all') {
                    return true;
                }

                if ($key === 'sort_by' && $value === 'booking_date') {
                    return true;
                }

                if ($key === 'sort_direction' && $value === 'desc') {
                    return true;
                }

                if ($key === 'per_page' && (int) $value === 10) {
                    return true;
                }

                return false;
            })
            ->all();
    };

    $queryFor = fn (array $overrides = []) => $cleanQuery(array_merge($filters, $overrides));
    $sortQueryFor = function (string $key) use ($queryFor, $sortBy, $sortDirection) {
        $nextDirection = $sortBy === $key && $sortDirection === 'asc' ? 'desc' : 'asc';

        return $queryFor([
            'sort_by' => $key,
            'sort_direction' => $nextDirection,
        ]);
    };
    $sortIconClass = fn (string $key, string $direction) => $sortBy === $key && $sortDirection === $direction ? 'active' : '';

    $statusLabels = [
        'pending_payment' => 'Pending Pay',
        'order_completed' => 'Completed',
        'refund_completed' => 'Refunded',
        'checked_in' => 'Checked In',
        'in_progress' => 'In Progress',
        'inprogress' => 'In Progress',
        'provider_cancelled' => 'Provider Cancel',
        'customer_cancelled' => 'Customer Cancel',
        'no_show' => 'No Show',
        'walk_in' => 'Walk In',
        'pay_at_salon' => 'Pay at Salon',
        'full_payment' => 'Full Payment',
    ];

    $statusLabel = fn ($value) => $statusLabels[$value ?: 'pending'] ?? ucwords(str_replace('_', ' ', $value ?: 'pending'));

    $statusClass = function ($value) {
        return match ($value) {
            'completed', 'order_completed', 'refund_completed', 'paid' => 'success',
            'pending', 'pending_payment', 'waiting', 'confirmed', 'rescheduled', 'unpaid', 'dp' => 'warning',
            'checked_in', 'inprogress', 'in_progress', 'scheduled', 'queue', 'walk_in', 'pay_at_salon', 'full_payment' => 'info',
            'provider_cancelled', 'customer_cancelled', 'cancelled', 'no_show', 'rejected', 'failed' => 'danger',
            default => 'neutral',
        };
    };

    $formatTime = function ($value) {
        if (empty($value)) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($value)->format('H:i');
        } catch (\Throwable $exception) {
            return substr((string) $value, 0, 5) ?: null;
        }
    };

    $formatDate = function ($value) {
        if (empty($value)) {
            return '-';
        }

        try {
            return \Carbon\Carbon::parse($value)->format('d M Y');
        } catch (\Throwable $exception) {
            return '-';
        }
    };

    $formatMoney = fn ($value) => 'Rp' . number_format((float) ($value ?? 0), 0, ',', '.');

    $serviceNames = function ($booking) {
        $services = $booking->services ?? collect();

        if ($services->isNotEmpty()) {
            return $services->pluck('title')->join(', ');
        }

        return $booking->service->title ?? '-';
    };

    $bookingAmount = fn ($booking) => (float) ($booking->total_price ?: $booking->amount ?: 0);
    $bookingInitial = fn ($booking, $customerName) => strtoupper(substr((string) ($customerName ?: $booking->booking_code ?: 'B'), 0, 1));

    $summary = $summary ?? [
        'total' => $totalItem,
        'paid' => 0,
        'pending' => 0,
        'completed' => 0,
        'amount' => 0,
    ];

    $hasMobileAdvancedFilters = (($filters['payment_status'] ?? 'all') !== 'all')
        || (($filters['booking_type'] ?? 'all') !== 'all')
        || ! empty($filters['date_from'])
        || ! empty($filters['date_to'])
        || ((int) ($filters['per_page'] ?? 10) !== 10);
@endphp

<section class="admin-category-page admin-booking-page provider-booking-category-page">
    <div class="admin-booking-route admin-category-route provider-booking-route">
        <div class="admin-breadcrumb">
            <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <strong>Bookings</strong>
        </div>

        <div class="provider-booking-category-actions provider-booking-category-actions-desktop">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Queue
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.walk-in.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Tambah Walk-in
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
            <span>Total Booking</span>
            <strong>{{ number_format((int) $summary['total']) }}</strong>
            <small>Data sesuai filter aktif</small>
        </div>

        <div class="admin-booking-summary-card yellow">
            <span>Revenue</span>
            <strong>{{ $formatMoney($summary['amount']) }}</strong>
            <small>Total nilai booking</small>
        </div>

        <div class="admin-booking-summary-card blue">
            <span>Paid</span>
            <strong>{{ number_format((int) $summary['paid']) }}</strong>
            <small>Pembayaran selesai</small>
        </div>

        <div class="admin-booking-summary-card orange">
            <span>In Progress</span>
            <strong>{{ number_format((int) $summary['pending']) }}</strong>
            <small>Booking masih berjalan</small>
        </div>
    </div>

    <div class="admin-booking-card category-card provider-booking-category-card">
        <div class="admin-booking-tabs">
            @foreach ($statusTabs as $key => $label)
                <a href="{{ provider_route('provider.bookings.index', $queryFor(['status' => $key])) }}"
                   class="admin-booking-tab {{ ($currentStatus === $key || ($key === 'all' && empty($currentStatus))) ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ provider_route('provider.bookings.index') }}" class="admin-booking-filter-panel compact {{ $hasMobileAdvancedFilters ? 'is-expanded' : '' }}">
            @if (! empty($currentStatus) && $currentStatus !== 'all')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">

            <div class="admin-booking-filter-row provider-booking-category-filter-row" id="bookingFilterRow">
                <label class="admin-booking-field search">
                    <div class="admin-booking-search-box">
                        <svg viewBox="0 0 24 24">
                            <circle cx="11" cy="11" r="7"></circle>
                            <path d="m21 21-4.3-4.3"></path>
                        </svg>

                        <input id="bookingSearchInput"
                               type="text"
                               name="search"
                               value="{{ $filters['search'] ?? '' }}"
                               placeholder="Search booking">
                    </div>
                </label>

                <button type="submit" class="admin-booking-mobile-search-submit" aria-label="Search booking">
                    Cari
                </button>

                <button type="button"
                        class="admin-booking-mobile-filter-toggle {{ $hasMobileAdvancedFilters ? 'active' : '' }}"
                        aria-controls="bookingFilterRow"
                        aria-expanded="{{ $hasMobileAdvancedFilters ? 'true' : 'false' }}">
                    Filter
                </button>

                <label class="admin-booking-field mini">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" aria-label="Date from" title="Date from">
                </label>

                <label class="admin-booking-field mini">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" aria-label="Date to" title="Date to">
                </label>

                <label class="admin-booking-field mini">
                    <select name="payment_status" aria-label="Payment status" title="Payment status">
                        @foreach ($paymentStatuses as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['payment_status'] ?? 'all') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="admin-booking-field mini">
                    <select name="booking_type" aria-label="Booking mode" title="Booking mode">
                        @foreach ($bookingTypes as $key => $label)
                            <option value="{{ $key }}" {{ ($filters['booking_type'] ?? 'all') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="admin-booking-field count">
                    <select name="per_page" aria-label="Rows per page" title="Rows per page">
                        <option value="10" {{ (int) ($filters['per_page'] ?? 10) === 10 ? 'selected' : '' }}>10</option>
                        <option value="25" {{ (int) ($filters['per_page'] ?? 10) === 25 ? 'selected' : '' }}>25</option>
                        <option value="50" {{ (int) ($filters['per_page'] ?? 10) === 50 ? 'selected' : '' }}>50</option>
                        <option value="100" {{ (int) ($filters['per_page'] ?? 10) === 100 ? 'selected' : '' }}>100</option>
                    </select>
                </label>

                <div class="admin-booking-filter-buttons">
                    <button type="submit">Filter</button>
                    @if ($hasActiveFilters ?? false)
                        <a href="{{ provider_route('provider.bookings.index') }}">Reset</a>
                    @endif
                </div>
            </div>

            <div class="admin-booking-filter-meta">
                <span class="admin-booking-filter-count">{{ number_format($totalItem) }} booking</span>

                @if (($filters['search'] ?? '') !== '')
                    <span>Search: {{ $filters['search'] }}</span>
                @endif

                @if (($filters['status'] ?? 'all') !== 'all')
                    <span>Status: {{ $statusLabel($filters['status']) }}</span>
                @endif

                @if (($filters['payment_status'] ?? 'all') !== 'all')
                    <span>Payment: {{ $paymentStatuses[$filters['payment_status']] ?? $statusLabel($filters['payment_status']) }}</span>
                @endif

                @if (($filters['booking_type'] ?? 'all') !== 'all')
                    <span>Mode: {{ $bookingTypes[$filters['booking_type']] ?? $statusLabel($filters['booking_type']) }}</span>
                @endif

                @if (! empty($filters['date_from']))
                    <span>From: {{ $filters['date_from'] }}</span>
                @endif

                @if (! empty($filters['date_to']))
                    <span>To: {{ $filters['date_to'] }}</span>
                @endif
            </div>
        </form>

        <div class="admin-category-add-row provider-booking-category-actions provider-booking-category-actions-mobile">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Queue
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.walk-in.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 5v14"></path>
                    <path d="M5 12h14"></path>
                </svg>
                Tambah Walk-in
            </a>
        </div>

        <div class="admin-category-mobile-list admin-booking-mobile-list">
            @forelse ($bookingCollection as $booking)
                @php
                    $bookingStatus = $booking->status ?? 'pending';
                    $customerName = $booking->customer_name ?? $booking->customer->name ?? 'Walk-in';
                    $branchName = $booking->branch->branch_name ?? '-';
                    $staffName = $booking->staff?->full_name ?: 'Any Available';
                    $serviceName = $serviceNames($booking);
                    $bookingType = $booking->booking_type ?? 'scheduled';
                    $paymentStatus = $booking->payment_status ?? optional($booking->payment)->status ?? 'unpaid';
                    $paymentMethod = optional($booking->payment)->payment_method
                        ?? optional($booking->payment)->payment_channel
                        ?? optional($booking->payment)->payment_type
                        ?? null;
                    $amount = $bookingAmount($booking);
                    $dateValue = $booking->booking_date ?? $booking->created_at ?? null;
                    $startTime = $formatTime($booking->start_time ?? $booking->booking_time ?? null);
                    $canCheckIn = in_array($bookingStatus, ['confirmed', 'waiting'], true);
                    $canStart = in_array($bookingStatus, ['confirmed', 'waiting', 'checked_in'], true);
                    $canComplete = in_array($bookingStatus, ['in_progress', 'inprogress'], true);
                    $canCancel = ! in_array($bookingStatus, ['completed', 'order_completed', 'refund_completed', 'cancelled', 'provider_cancelled', 'customer_cancelled', 'no_show'], true);
                @endphp

                <article class="admin-category-mobile-card admin-booking-mobile-card provider-booking-mobile-card">
                    <header class="admin-category-mobile-head">
                        <div class="admin-category-mobile-title">
                            <span>{{ $bookingInitial($booking, $customerName) }}</span>

                            <div>
                                <strong>{{ $booking->booking_code ?? ('#' . $booking->id) }}</strong>
                                <span>{{ $formatDate($dateValue) }}@if ($startTime) &middot; {{ $startTime }}@endif</span>
                            </div>
                        </div>

                        <b>{{ $formatMoney($amount) }}</b>
                    </header>

                    <div class="admin-category-mobile-main admin-booking-mobile-main provider-booking-mobile-main">
                        <div>
                            <span>Customer</span>
                            <strong>{{ $customerName }}</strong>
                        </div>

                        <div>
                            <span>Branch</span>
                            <strong>{{ $branchName }}</strong>
                            <small>{{ $staffName }}</small>
                        </div>

                        <div>
                            <span>Service</span>
                            <strong>{{ Str::limit($serviceName, 28) }}</strong>
                        </div>

                        <div>
                            <span>Payment</span>
                            <strong>{{ $statusLabel($paymentStatus) }}</strong>
                            @if ($paymentMethod)
                                <small>{{ $statusLabel($paymentMethod) }}</small>
                            @endif
                        </div>
                    </div>

                    <p>{{ Str::limit($serviceName, 120) }}</p>

                    <footer class="admin-category-mobile-footer provider-booking-mobile-footer">
                        <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">
                            {{ $statusLabel($bookingStatus) }}
                        </span>

                        <span class="admin-booking-status {{ $statusClass($bookingType) }}">
                            {{ $statusLabel($bookingType) }}
                        </span>

                        @if ($canCheckIn || $canStart || $canComplete || $canCancel)
                            <div class="category-actions provider-booking-action-icons provider-booking-mobile-action-icons">
                                @if ($canCheckIn)
                                    <form method="POST" action="{{ provider_route('provider.bookings.check-in', $booking) }}">
                                        @csrf
                                        <button class="category-action-btn info" type="submit" title="Check-in" aria-label="Check-in {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M20 6 9 17l-5-5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if ($canStart)
                                    <form method="POST" action="{{ provider_route('provider.bookings.start', $booking) }}">
                                        @csrf
                                        <button class="category-action-btn success" type="submit" title="Start" aria-label="Start {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M8 5v14l11-7-11-7Z"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if ($canComplete)
                                    <form method="POST" action="{{ provider_route('provider.bookings.complete', $booking) }}">
                                        @csrf
                                        <button class="category-action-btn dark" type="submit" title="Complete" aria-label="Complete {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M4 12l5 5L20 6"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif

                                @if ($canCancel)
                                    <form method="POST" action="{{ provider_route('provider.bookings.cancel', $booking) }}">
                                        @csrf
                                        <button class="category-action-btn danger" type="submit" title="Cancel" aria-label="Cancel {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M18 6 6 18"></path>
                                                <path d="m6 6 12 12"></path>
                                            </svg>
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ provider_route('provider.bookings.no-show', $booking) }}">
                                        @csrf
                                        <button class="category-action-btn danger" type="submit" title="No-show" aria-label="No-show {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                            <svg viewBox="0 0 24 24" fill="none">
                                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                                <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path>
                                                <path d="m17 8 5 5"></path>
                                                <path d="m22 8-5 5"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endif
                    </footer>
                </article>
            @empty
                <div class="admin-category-mobile-empty admin-booking-mobile-empty">
                    <strong>No booking data found.</strong>
                    <p>Coba ubah keyword, filter tanggal, payment, mode, atau status booking.</p>
                </div>
            @endforelse
        </div>

        <div class="admin-booking-table-wrap category-table-wrap provider-booking-category-table-wrap">
            <table class="admin-booking-table detailed category-table provider-booking-category-table">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ provider_route('provider.bookings.index', $sortQueryFor('booking_code')) }}" class="admin-booking-sort {{ $sortBy === 'booking_code' ? 'active' : '' }}">
                                Booking
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_code', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_code', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ provider_route('provider.bookings.index', $sortQueryFor('booking_date')) }}" class="admin-booking-sort {{ $sortBy === 'booking_date' ? 'active' : '' }}">
                                Appointment
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_date', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_date', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>Customer</th>
                        <th>Service</th>
                        <th>
                            <a href="{{ provider_route('provider.bookings.index', $sortQueryFor('booking_type')) }}" class="admin-booking-sort {{ $sortBy === 'booking_type' ? 'active' : '' }}">
                                Mode
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_type', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_type', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ provider_route('provider.bookings.index', $sortQueryFor('payment_status')) }}" class="admin-booking-sort {{ $sortBy === 'payment_status' ? 'active' : '' }}">
                                Payment
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('payment_status', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('payment_status', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ provider_route('provider.bookings.index', $sortQueryFor('status')) }}" class="admin-booking-sort {{ $sortBy === 'status' ? 'active' : '' }}">
                                Status
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('status', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('status', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($bookingCollection as $booking)
                        @php
                            $bookingStatus = $booking->status ?? 'pending';
                            $customerName = $booking->customer_name ?? $booking->customer->name ?? 'Walk-in';
                            $customerPhone = $booking->customer_phone ?? optional($booking->customer?->customerProfile)->phone_number ?? null;
                            $serviceName = $serviceNames($booking);
                            $serviceCount = ($booking->services ?? collect())->isNotEmpty() ? $booking->services->count() : ($booking->service ? 1 : 0);
                            $branchName = $booking->branch->branch_name ?? '-';
                            $branchLocation = $booking->branch ? collect([$booking->branch->city_id, $booking->branch->state_id])->filter()->implode(', ') : '';
                            $staffName = $booking->staff?->full_name ?: 'Any Available';
                            $bookingType = $booking->booking_type ?? 'scheduled';
                            $paymentStatus = $booking->payment_status ?? optional($booking->payment)->status ?? 'unpaid';
                            $paymentMethod = optional($booking->payment)->payment_method
                                ?? optional($booking->payment)->payment_channel
                                ?? optional($booking->payment)->payment_type
                                ?? null;
                            $amount = $bookingAmount($booking);
                            $dateValue = $booking->booking_date ?? $booking->created_at ?? null;
                            $startTime = $formatTime($booking->start_time ?? $booking->booking_time ?? null);
                            $endTime = $formatTime($booking->estimated_end_time ?? null);
                            $canCheckIn = in_array($bookingStatus, ['confirmed', 'waiting'], true);
                            $canStart = in_array($bookingStatus, ['confirmed', 'waiting', 'checked_in'], true);
                            $canComplete = in_array($bookingStatus, ['in_progress', 'inprogress'], true);
                            $canCancel = ! in_array($bookingStatus, ['completed', 'order_completed', 'refund_completed', 'cancelled', 'provider_cancelled', 'customer_cancelled', 'no_show'], true);
                        @endphp

                        <tr>
                            <td>
                                <div class="category-name-box provider-booking-code-box">
                                    <span class="category-thumb-placeholder">{{ $bookingInitial($booking, $customerName) }}</span>

                                    <div class="category-name-text">
                                        <strong>{{ $booking->booking_code ?? ('#' . $booking->id) }}</strong>
                                        <small>ID #{{ $booking->id }}</small>
                                        @if ($booking->queue_number)
                                            <small>Queue #{{ $booking->queue_number }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-date">
                                    <strong>{{ $formatDate($dateValue) }}</strong>
                                    @if ($startTime)
                                        <small>{{ $startTime }}{{ $endTime ? ' - ' . $endTime : '' }}</small>
                                    @else
                                        <small>Time not set</small>
                                    @endif
                                    <small>Created {{ $booking->created_at?->format('d M H:i') ?? '-' }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-person">
                                    <span>{{ $bookingInitial($booking, $customerName) }}</span>
                                    <div>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customerPhone ?: 'No phone' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <p class="category-description-text">{{ Str::limit($serviceName, 92) }}</p>
                                <small class="provider-booking-description-meta">
                                    {{ $serviceCount > 1 ? $serviceCount . ' services' : 'Single service' }} &middot;
                                    {{ (int) ($booking->total_duration ?? 0) > 0 ? $booking->total_duration . ' min' : 'Duration -' }}
                                </small>
                                <small class="provider-booking-description-meta">
                                    {{ $branchName }} &middot; {{ $branchLocation ?: $staffName }}
                                </small>
                            </td>

                            <td>
                                <div class="admin-booking-mode-stack">
                                    <span class="admin-booking-status {{ $statusClass($bookingType) }}">
                                        {{ $statusLabel($bookingType) }}
                                    </span>
                                    @if ($booking->queue_number)
                                        <small>Queue #{{ $booking->queue_number }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-mode-stack">
                                    <span class="admin-booking-status {{ $statusClass($paymentStatus) }}">
                                        {{ $statusLabel($paymentStatus) }}
                                    </span>
                                    <small>{{ $formatMoney($amount) }}</small>
                                    @if ($paymentMethod)
                                        <small>{{ $statusLabel($paymentMethod) }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">
                                    {{ $statusLabel($bookingStatus) }}
                                </span>
                            </td>

                            <td>
                                @if ($canCheckIn || $canStart || $canComplete || $canCancel)
                                    <div class="category-actions provider-booking-action-icons provider-booking-row-actions">
                                        @if ($canCheckIn)
                                            <form method="POST" action="{{ provider_route('provider.bookings.check-in', $booking) }}">
                                                @csrf
                                                <button class="category-action-btn info" type="submit" title="Check-in" aria-label="Check-in {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M20 6 9 17l-5-5"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($canStart)
                                            <form method="POST" action="{{ provider_route('provider.bookings.start', $booking) }}">
                                                @csrf
                                                <button class="category-action-btn success" type="submit" title="Start" aria-label="Start {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M8 5v14l11-7-11-7Z"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($canComplete)
                                            <form method="POST" action="{{ provider_route('provider.bookings.complete', $booking) }}">
                                                @csrf
                                                <button class="category-action-btn dark" type="submit" title="Complete" aria-label="Complete {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M4 12l5 5L20 6"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif

                                        @if ($canCancel)
                                            <form method="POST" action="{{ provider_route('provider.bookings.cancel', $booking) }}">
                                                @csrf
                                                <button class="category-action-btn danger" type="submit" title="Cancel" aria-label="Cancel {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M18 6 6 18"></path>
                                                        <path d="m6 6 12 12"></path>
                                                    </svg>
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ provider_route('provider.bookings.no-show', $booking) }}">
                                                @csrf
                                                <button class="category-action-btn danger" type="submit" title="No-show" aria-label="No-show {{ $booking->booking_code ?? ('#' . $booking->id) }}">
                                                    <svg viewBox="0 0 24 24" fill="none">
                                                        <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path>
                                                        <path d="M9 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z"></path>
                                                        <path d="m17 8 5 5"></path>
                                                        <path d="m22 8-5 5"></path>
                                                    </svg>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @else
                                    <span class="admin-booking-status neutral">Locked</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="admin-booking-empty">
                                <div>
                                    <span>
                                        <svg viewBox="0 0 24 24">
                                            <path d="M8 2v4"></path>
                                            <path d="M16 2v4"></path>
                                            <path d="M5 5h14v16H5z"></path>
                                            <path d="M3 10h18"></path>
                                        </svg>
                                    </span>

                                    <strong>No booking data found.</strong>
                                    <p>Coba ubah keyword, filter tanggal, payment, mode, atau status booking.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-booking-footer category-footer">
            <p class="admin-booking-showing">
                <strong>{{ number_format($firstItem) }}-{{ number_format($lastItem) }}</strong>
                <span>/ {{ number_format($totalItem) }}</span>
            </p>

            @if ($hasPaginator)
                <div class="admin-booking-pagination category-pagination">
                    @if ($bookingCollection->onFirstPage())
                        <span class="disabled">&lsaquo;</span>
                    @else
                        <a href="{{ $bookingCollection->previousPageUrl() }}" aria-label="Previous page">&lsaquo;</a>
                    @endif

                    <span class="active">{{ $bookingCollection->currentPage() }}</span>

                    @if ($bookingCollection->hasMorePages())
                        <a href="{{ $bookingCollection->nextPageUrl() }}" aria-label="Next page">&rsaquo;</a>
                    @else
                        <span class="disabled">&rsaquo;</span>
                    @endif
                </div>
            @else
                <div class="admin-booking-pagination category-pagination static">
                    <span class="active">1</span>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
