@extends('admin.layouts.app')

@section('title', 'Booking List - JasaKu')
@section('page_title', 'Bookings')

@section('content')
@php
    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');
    $currentStatus = request('status', $status ?? 'all');

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

    $bookingCollection = $bookings ?? collect();

    $hasPaginator = is_object($bookingCollection)
        && method_exists($bookingCollection, 'links')
        && method_exists($bookingCollection, 'firstItem');

    $firstItem = $hasPaginator ? ($bookingCollection->firstItem() ?? 0) : 0;
    $lastItem = $hasPaginator ? ($bookingCollection->lastItem() ?? 0) : (is_countable($bookingCollection) ? count($bookingCollection) : 0);
    $totalItem = $hasPaginator ? $bookingCollection->total() : (is_countable($bookingCollection) ? count($bookingCollection) : 0);

    function adminBookingStatusLabel($statusValue) {
        return ucwords(str_replace('_', ' ', $statusValue ?: 'pending'));
    }

    function adminBookingStatusClass($statusValue) {
        return match ($statusValue) {
            'completed', 'order_completed', 'refund_completed' => 'success',
            'pending', 'pending_payment', 'waiting', 'confirmed', 'rescheduled' => 'warning',
            'checked_in', 'inprogress', 'in_progress' => 'info',
            'provider_cancelled', 'customer_cancelled', 'cancelled', 'no_show', 'rejected' => 'danger',
            default => 'neutral',
        };
    }
@endphp

<section class="admin-booking-page">
    <div class="admin-page-heading">
        <div>
            <h1>Booking List</h1>

            <div class="admin-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>/</span>
                <strong>Booking List</strong>
            </div>
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

    <div class="admin-booking-card">
        <div class="admin-booking-tabs">
            @foreach ($statusTabs as $key => $label)
                <a href="{{ route('admin.bookings.index', array_filter([
                    'status' => $key === 'all' ? null : $key,
                    'per_page' => $perPage,
                    'search' => $search,
                ])) }}"
                   class="admin-booking-tab {{ ($currentStatus === $key || ($key === 'all' && empty($currentStatus))) ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="admin-booking-toolbar">
            <form method="GET" action="{{ route('admin.bookings.index') }}" class="admin-booking-entries">
                @if (!empty($currentStatus) && $currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif

                @if (!empty($search))
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                <span>Show</span>

                <select name="per_page" onchange="this.form.submit()">
                    <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (int) $perPage === 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ (int) $perPage === 100 ? 'selected' : '' }}>100</option>
                </select>

                <span>entries</span>
            </form>

            <form method="GET" action="{{ route('admin.bookings.index') }}" class="admin-booking-search">
                @if (!empty($currentStatus) && $currentStatus !== 'all')
                    <input type="hidden" name="status" value="{{ $currentStatus }}">
                @endif

                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <label for="bookingSearchInput">Search:</label>

                <div class="admin-booking-search-box">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>

                    <input id="bookingSearchInput"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search booking">
                </div>
            </form>
        </div>

        <div class="admin-booking-table-wrap">
            <table class="admin-booking-table">
                <thead>
                    <tr>
                        <th># <span>sort</span></th>
                        <th>Appointment <span>sort</span></th>
                        <th>Provider <span>sort</span></th>
                        <th>User <span>sort</span></th>
                        <th>Service <span>sort</span></th>
                        <th>Branch <span>sort</span></th>
                        <th>Staff <span>sort</span></th>
                        <th>Mode <span>sort</span></th>
                        <th>Total <span>sort</span></th>
                        <th>Payment <span>sort</span></th>
                        <th>Status <span>sort</span></th>
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

                            $userName = $booking->user->name
                                ?? $booking->customer->name
                                ?? $booking->user_name
                                ?? $booking->customer_name
                                ?? '-';

                            $multiServices = $booking->services ?? collect();
                            $serviceName = $multiServices->isNotEmpty()
                                ? $multiServices->pluck('title')->join(', ')
                                : ($booking->service->title
                                    ?? $booking->service->name
                                    ?? $booking->service_name
                                    ?? '-');

                            $branchName = $booking->branch->branch_name
                                ?? $booking->branch_name
                                ?? '-';

                            $branchLocation = $booking->branch
                                ? collect([$booking->branch->city_id, $booking->branch->state_id])->filter()->implode(', ')
                                : '';

                            $staffName = $booking->staff->full_name ?? 'Any Available';
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

                            $timeValue = $booking->start_time ?? $booking->booking_time ?? null;
                        @endphp

                        <tr>
                            <td>
                                {{ $hasPaginator ? $loop->iteration + ($bookingCollection->firstItem() - 1) : $loop->iteration }}
                            </td>

                            <td>
                                <div class="admin-booking-date">
                                    <strong>
                                        @if ($dateValue)
                                            {{ \Carbon\Carbon::parse($dateValue)->format('d M Y') }}
                                        @else
                                            -
                                        @endif
                                    </strong>

                                    @if (!empty($timeValue))
                                        <small>{{ \Carbon\Carbon::parse($timeValue)->format('H:i') }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-person">
                                    <span>{{ strtoupper(substr($providerName, 0, 1)) }}</span>
                                    <strong>{{ $providerName }}</strong>
                                </div>
                            </td>

                            <td>
                                <div class="admin-booking-person">
                                    <span>{{ strtoupper(substr($userName, 0, 1)) }}</span>
                                    <strong>{{ $userName }}</strong>
                                </div>
                            </td>

                            <td>{{ $serviceName }}</td>

                            <td>
                                <div class="admin-booking-date">
                                    <strong>{{ $branchName }}</strong>
                                    @if ($branchLocation)
                                        <small>{{ $branchLocation }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>{{ $staffName }}</td>

                            <td>
                                <span class="admin-booking-status neutral">
                                    {{ adminBookingStatusLabel($bookingType) }}
                                </span>
                            </td>

                            <td>
                                <strong class="admin-booking-amount">
                                    Rp{{ number_format((float) $amount, 0, ',', '.') }}
                                </strong>
                            </td>

                            <td>
                                <span class="admin-booking-status {{ adminBookingStatusClass($paymentStatus) }}">
                                    {{ adminBookingStatusLabel($paymentStatus) }}
                                </span>
                            </td>

                            <td>
                                <span class="admin-booking-status {{ adminBookingStatusClass($bookingStatus) }}">
                                    {{ adminBookingStatusLabel($bookingStatus) }}
                                </span>
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

                                    <strong>No booking data yet.</strong>
                                    <p>Booking data will appear here after a customer creates a reservation.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-booking-footer">
            <p>
                Showing {{ $firstItem }} to {{ $lastItem }} of {{ $totalItem }} entries
            </p>

            @if ($hasPaginator)
                <div class="admin-booking-pagination">
                    {{ $bookingCollection->links() }}
                </div>
            @else
                <div class="admin-booking-pagination static">
                    <span class="disabled">First</span>
                    <span class="disabled">Previous</span>
                    <span class="active">1</span>
                    <span class="disabled">Next</span>
                    <span class="disabled">Last</span>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
