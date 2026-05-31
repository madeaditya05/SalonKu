@extends('admin.layouts.app')

@section('title', 'Bookings - JasaKu')
@section('page_title', 'Bookings')

@section('content')
@php
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
        'open' => 'Open',
        'pending' => 'Pending',
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
        'expired' => 'Expired',
    ];

    $bookingTypes = $bookingTypes ?? [
        'all' => 'All Modes',
        'scheduled' => 'Scheduled',
        'queue' => 'Queue',
        'walk_in' => 'Walk In',
    ];

    $sortOptions = $sortOptions ?? [
        'booking_date' => 'Appointment Date',
        'created_at' => 'Created Date',
        'amount' => 'Total Amount',
        'payment_status' => 'Payment Status',
        'status' => 'Booking Status',
        'booking_type' => 'Mode',
        'booking_code' => 'Booking Code',
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
    ];

    $statusLabel = fn ($value) => $statusLabels[$value ?: 'pending'] ?? ucwords(str_replace('_', ' ', $value ?: 'pending'));

    $statusClass = function ($value) {
        return match ($value) {
            'completed', 'order_completed', 'refund_completed', 'paid' => 'success',
            'pending', 'pending_payment', 'waiting', 'confirmed', 'rescheduled', 'unpaid' => 'warning',
            'checked_in', 'inprogress', 'in_progress', 'scheduled', 'queue', 'walk_in' => 'info',
            'provider_cancelled', 'customer_cancelled', 'cancelled', 'no_show', 'rejected', 'failed', 'expired' => 'danger',
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
            return null;
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

<section class="admin-booking-page">
    <div class="admin-booking-route">
        <div class="admin-breadcrumb">
            <a href="{{ route('admin.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <strong>Bookings</strong>
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

    <div class="admin-booking-summary-grid">
        <div class="admin-booking-summary-card pink">
            <span>Total Bookings</span>
            <strong>{{ number_format((int) $summary['total']) }}</strong>
            <small>All booking data</small>
        </div>

        <div class="admin-booking-summary-card yellow">
            <span>Revenue</span>
            <strong>Rp{{ number_format((float) $summary['amount'], 0, ',', '.') }}</strong>
            <small>Total booking value</small>
        </div>

        <div class="admin-booking-summary-card blue">
            <span>Paid</span>
            <strong>{{ number_format((int) $summary['paid']) }}</strong>
            <small>Completed payments</small>
        </div>

        <div class="admin-booking-summary-card orange">
            <span>In Progress</span>
            <strong>{{ number_format((int) $summary['pending']) }}</strong>
            <small>Still in progress</small>
        </div>
    </div>

    <div class="admin-booking-card">
        <div class="admin-booking-tabs">
            @foreach ($statusTabs as $key => $label)
                <a href="{{ route('admin.bookings.index', $queryFor(['status' => $key])) }}"
                   class="admin-booking-tab {{ ($currentStatus === $key || ($key === 'all' && empty($currentStatus))) ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <form method="GET" action="{{ route('admin.bookings.index') }}" class="admin-booking-filter-panel compact {{ $hasMobileAdvancedFilters ? 'is-expanded' : '' }}">
            @if (!empty($currentStatus) && $currentStatus !== 'all')
                <input type="hidden" name="status" value="{{ $currentStatus }}">
            @endif

            <input type="hidden" name="sort_by" value="{{ $sortBy }}">
            <input type="hidden" name="sort_direction" value="{{ $sortDirection }}">

            <div class="admin-booking-filter-row" id="bookingFilterRow">
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
                    Search
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
                        <a href="{{ route('admin.bookings.index') }}">Reset</a>
                    @endif
                </div>
            </div>

            <div class="admin-booking-filter-meta">
                <span class="admin-booking-filter-count">{{ number_format($totalItem) }} booking</span>

                @if (($filters['search'] ?? '') !== '')
                    <span>Search: {{ $filters['search'] }}</span>
                @endif

                @if (($filters['payment_status'] ?? 'all') !== 'all')
                    <span>Payment: {{ $paymentStatuses[$filters['payment_status']] ?? $statusLabel($filters['payment_status']) }}</span>
                @endif

                @if (($filters['booking_type'] ?? 'all') !== 'all')
                    <span>Mode: {{ $bookingTypes[$filters['booking_type']] ?? $statusLabel($filters['booking_type']) }}</span>
                @endif

                @if (!empty($filters['date_from']))
                    <span>From: {{ $filters['date_from'] }}</span>
                @endif

                @if (!empty($filters['date_to']))
                    <span>To: {{ $filters['date_to'] }}</span>
                @endif
            </div>
        </form>

        <div class="admin-booking-mobile-list">
            @forelse ($bookingCollection as $booking)
                @php
                    $bookingStatus = $booking->status ?? 'pending';
                    $providerName = $booking->provider->name
                        ?? $booking->provider_name
                        ?? $booking->providerName
                        ?? '-';
                    $customerName = $booking->customer_name
                        ?? $booking->customer->name
                        ?? $booking->user_name
                        ?? '-';
                    $multiServices = $booking->services ?? collect();
                    $serviceName = $multiServices->isNotEmpty()
                        ? $multiServices->pluck('title')->join(', ')
                        : ($booking->service->title
                            ?? $booking->service->name
                            ?? $booking->service_name
                            ?? '-');
                    $bookingType = $booking->booking_type ?? 'scheduled';
                    $paymentStatus = $booking->payment_status ?? optional($booking->payment)->status ?? 'unpaid';
                    $amount = $booking->total_price
                        ?? $booking->amount
                        ?? $booking->total_amount
                        ?? $booking->price
                        ?? 0;
                    $dateValue = $booking->booking_date
                        ?? $booking->date
                        ?? $booking->created_at
                        ?? null;
                    $startTime = $formatTime($booking->start_time ?? $booking->booking_time ?? null);
                @endphp

                <article class="admin-booking-mobile-card">
                    <header>
                        <div>
                            <strong>{{ $booking->booking_code ?? ('#' . $booking->id) }}</strong>
                            <span>{{ $formatDate($dateValue) }}@if ($startTime) &middot; {{ $startTime }} @endif</span>
                        </div>

                        <b>Rp{{ number_format((float) $amount, 0, ',', '.') }}</b>
                    </header>

                    <div class="admin-booking-mobile-main">
                        <div>
                            <span>Customer</span>
                            <strong>{{ $customerName }}</strong>
                        </div>

                        <div>
                            <span>Provider</span>
                            <strong>{{ $providerName }}</strong>
                        </div>
                    </div>

                    <p>{{ $serviceName }}</p>

                    <footer>
                        <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">
                            {{ $statusLabel($bookingStatus) }}
                        </span>
                        <span class="admin-booking-status {{ $statusClass($paymentStatus) }}">
                            {{ $statusLabel($paymentStatus) }}
                        </span>
                        <span class="admin-booking-status {{ $statusClass($bookingType) }}">
                            {{ $statusLabel($bookingType) }}
                        </span>
                    </footer>
                </article>
            @empty
                <div class="admin-booking-mobile-empty">
                    <strong>No booking data found.</strong>
                    <p>Try changing the keyword, date filter, payment status, or booking status.</p>
                </div>
            @endforelse
        </div>

        <div class="admin-booking-table-wrap">
            <table class="admin-booking-table detailed">
                <thead>
                    <tr>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('booking_code')) }}" class="admin-booking-sort {{ $sortBy === 'booking_code' ? 'active' : '' }}">
                                Booking
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_code', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_code', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('booking_date')) }}" class="admin-booking-sort {{ $sortBy === 'booking_date' ? 'active' : '' }}">
                                Appointment
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_date', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_date', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>Customer</th>
                        <th>Provider</th>
                        <th>Service</th>
                        <th>Branch & Staff</th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('booking_type')) }}" class="admin-booking-sort {{ $sortBy === 'booking_type' ? 'active' : '' }}">
                                Mode
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('booking_type', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('booking_type', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('payment_status')) }}" class="admin-booking-sort {{ $sortBy === 'payment_status' ? 'active' : '' }}">
                                Payment
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('payment_status', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('payment_status', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('status')) }}" class="admin-booking-sort {{ $sortBy === 'status' ? 'active' : '' }}">
                                Status
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('status', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('status', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('amount')) }}" class="admin-booking-sort {{ $sortBy === 'amount' ? 'active' : '' }}">
                                Total
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('amount', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('amount', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                        <th>
                            <a href="{{ route('admin.bookings.index', $sortQueryFor('created_at')) }}" class="admin-booking-sort {{ $sortBy === 'created_at' ? 'active' : '' }}">
                                Timeline
                                <span class="admin-booking-sort-icons" aria-hidden="true">
                                    <span class="{{ $sortIconClass('created_at', 'asc') }}">&uarr;</span>
                                    <span class="{{ $sortIconClass('created_at', 'desc') }}">&darr;</span>
                                </span>
                            </a>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($bookingCollection as $booking)
                        @php
                            $bookingStatus = $booking->status ?? 'pending';

                            $providerName = $booking->provider->name
                                ?? $booking->provider_name
                                ?? $booking->providerName
                                ?? '-';

                            $providerEmail = $booking->provider->email ?? null;

                            $customerName = $booking->customer_name
                                ?? $booking->customer->name
                                ?? $booking->user_name
                                ?? $booking->customer_name
                                ?? '-';

                            $customerPhone = $booking->customer_phone
                                ?? $booking->customer?->customerProfile?->phone_number
                                ?? null;

                            $multiServices = $booking->services ?? collect();
                            $serviceName = $multiServices->isNotEmpty()
                                ? $multiServices->pluck('title')->join(', ')
                                : ($booking->service->title
                                    ?? $booking->service->name
                                    ?? $booking->service_name
                                    ?? '-');

                            $serviceCount = $multiServices->isNotEmpty() ? $multiServices->count() : ($booking->service ? 1 : 0);

                            $branchName = $booking->branch->branch_name
                                ?? $booking->branch_name
                                ?? '-';

                            $branchLocation = $booking->branch
                                ? collect([$booking->branch->city_id, $booking->branch->state_id])->filter()->implode(', ')
                                : '';

                            $staffName = $booking->staff->full_name ?: 'Any Available';
                            $bookingType = $booking->booking_type ?? 'scheduled';
                            $paymentStatus = $booking->payment_status ?? optional($booking->payment)->status ?? 'unpaid';
                            $paymentMethod = optional($booking->payment)->payment_method
                                ?? optional($booking->payment)->payment_channel
                                ?? optional($booking->payment)->payment_type
                                ?? null;

                            $amount = $booking->total_price
                                ?? $booking->amount
                                ?? $booking->total_amount
                                ?? $booking->price
                                ?? 0;

                            $dateValue = $booking->booking_date
                                ?? $booking->date
                                ?? $booking->created_at
                                ?? null;

                            $startTime = $formatTime($booking->start_time ?? $booking->booking_time ?? null);
                            $endTime = $formatTime($booking->estimated_end_time ?? null);
                        @endphp

                        <tr>
                            <td>
                                <div class="admin-booking-code-cell">
                                    <strong>{{ $booking->booking_code ?? ('#' . $booking->id) }}</strong>
                                    <small>ID {{ $booking->id }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-date">
                                    <strong>{{ $formatDate($dateValue) }}</strong>

                                    @if ($startTime)
                                        <small>
                                            {{ $startTime }}{{ $endTime ? ' - ' . $endTime : '' }}
                                        </small>
                                    @else
                                        <small>Time not set</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-person">
                                    <span>{{ strtoupper(substr($customerName !== '-' ? $customerName : 'C', 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $customerName }}</strong>
                                        <small>{{ $customerPhone ?: 'No phone' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-person">
                                    <span>{{ strtoupper(substr($providerName !== '-' ? $providerName : 'P', 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $providerName }}</strong>
                                        <small>{{ $providerEmail ?: 'Provider account' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-service-cell">
                                    <strong>{{ $serviceName }}</strong>
                                    <small>{{ $serviceCount > 1 ? $serviceCount . ' services' : 'Single service' }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-date">
                                    <strong>{{ $branchName }}</strong>
                                    <small>{{ $branchLocation ?: $staffName }}</small>
                                    @if ($branchLocation)
                                        <small>Staff: {{ $staffName }}</small>
                                    @endif
                                </div>
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
                                <div class="admin-booking-total-cell">
                                    <strong>Rp{{ number_format((float) $amount, 0, ',', '.') }}</strong>
                                    <small>{{ (int) ($booking->total_duration ?? 0) > 0 ? $booking->total_duration . ' min' : 'Duration -' }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-timeline">
                                    <span>Created {{ $booking->created_at?->format('d M H:i') ?? '-' }}</span>
                                    @if ($booking->checked_in_at)
                                        <small>Check-in {{ $booking->checked_in_at->format('d M H:i') }}</small>
                                    @endif
                                    @if ($booking->completed_at)
                                        <small>Done {{ $booking->completed_at->format('d M H:i') }}</small>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="admin-booking-empty">
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
                                    <p>Try changing the keyword, date filter, payment status, or booking status.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-booking-footer">
            <p class="admin-booking-showing">
                <strong>{{ number_format($firstItem) }}-{{ number_format($lastItem) }}</strong>
                <span>/ {{ number_format($totalItem) }}</span>
            </p>

            @if ($hasPaginator)
                <div class="admin-booking-pagination">
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
                <div class="admin-booking-pagination static">
                    <span class="active">1</span>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
(() => {
    const page = document.querySelector('.admin-booking-page');

    if (!page || !window.fetch || !window.DOMParser || !window.history) {
        return;
    }

    const card = page.querySelector('.admin-booking-card');
    const replaceSelectors = [
        '.admin-booking-tabs',
        '.admin-booking-filter-panel',
        '.admin-booking-mobile-list',
        '.admin-booking-table-wrap',
        '.admin-booking-footer',
    ];
    let activeRequest = null;

    const applyLoading = (isLoading) => {
        if (!card) {
            return;
        }

        card.classList.toggle('is-loading', isLoading);
        card.setAttribute('aria-busy', isLoading ? 'true' : 'false');
    };

    const shouldKeepParam = (key, value) => {
        if (value === null || value === '') {
            return false;
        }

        if (['status', 'payment_status', 'booking_type'].includes(key) && value === 'all') {
            return false;
        }

        if (key === 'sort_by' && value === 'booking_date') {
            return false;
        }

        if (key === 'sort_direction' && value === 'desc') {
            return false;
        }

        if (key === 'per_page' && value === '10') {
            return false;
        }

        return true;
    };

    const buildFilterUrl = (form) => {
        const url = new URL(form.action || window.location.href, window.location.origin);
        const formData = new FormData(form);

        url.search = '';

        formData.forEach((value, key) => {
            const normalized = String(value).trim();

            if (shouldKeepParam(key, normalized)) {
                url.searchParams.set(key, normalized);
            }
        });

        return url;
    };

    const closestFromEvent = (event, selector) => {
        return event.target instanceof Element ? event.target.closest(selector) : null;
    };

    const syncMobileFilterToggle = (form) => {
        const toggle = form.querySelector('.admin-booking-mobile-filter-toggle');

        if (!toggle) {
            return;
        }

        const isExpanded = form.classList.contains('is-expanded');
        toggle.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        toggle.classList.toggle('active', isExpanded);
    };

    const replaceBookingParts = (html) => {
        const doc = new DOMParser().parseFromString(html, 'text/html');

        replaceSelectors.forEach((selector) => {
            const currentNode = page.querySelector(selector);
            const nextNode = doc.querySelector(selector);

            if (currentNode && nextNode) {
                currentNode.replaceWith(nextNode);
            }
        });

        const nextTitle = doc.querySelector('title');

        if (nextTitle) {
            document.title = nextTitle.textContent;
        }
    };

    const loadBookings = async (url, options = {}) => {
        const shouldPush = options.push !== false;
        const controller = new AbortController();

        if (activeRequest) {
            activeRequest.abort();
        }

        activeRequest = controller;

        applyLoading(true);

        try {
            const response = await fetch(url, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'text/html',
                },
                credentials: 'same-origin',
                signal: controller.signal,
            });

            if (!response.ok) {
                throw new Error(`Booking filter failed with status ${response.status}`);
            }

            const html = await response.text();

            if (controller !== activeRequest) {
                return;
            }

            replaceBookingParts(html);

            if (shouldPush) {
                window.history.pushState({ adminBookingsAjax: true }, '', response.url);
            }
        } catch (error) {
            if (error.name === 'AbortError') {
                return;
            }

            console.error(error);
            window.location.href = url.toString();
        } finally {
            if (controller === activeRequest) {
                activeRequest = null;
                applyLoading(false);
            }
        }
    };

    page.addEventListener('submit', (event) => {
        const form = closestFromEvent(event, '.admin-booking-filter-panel');

        if (!form) {
            return;
        }

        event.preventDefault();
        loadBookings(buildFilterUrl(form));
    });

    page.addEventListener('click', (event) => {
        const toggle = closestFromEvent(event, '.admin-booking-mobile-filter-toggle');

        if (toggle) {
            const form = toggle.closest('.admin-booking-filter-panel');

            if (form) {
                event.preventDefault();
                form.classList.toggle('is-expanded');
                syncMobileFilterToggle(form);
            }

            return;
        }

        const link = closestFromEvent(event, '.admin-booking-tabs a, .admin-booking-sort, .admin-booking-pagination a, .admin-booking-filter-buttons a');

        if (!link) {
            return;
        }

        const url = new URL(link.href, window.location.origin);

        if (url.origin !== window.location.origin) {
            return;
        }

        event.preventDefault();
        loadBookings(url);
    });

    window.addEventListener('popstate', () => {
        loadBookings(new URL(window.location.href), { push: false });
    });
})();
</script>
@endpush
