@extends('provider.layouts.dashboard')

@section('title', 'Kalender Staff - JasaKu')
@section('page_title', 'Kalender Staff')
@section('page_subtitle', 'Pantau jadwal aktif setiap staff, queue, dan walk-in dalam satu tampilan.')

@section('content')
@php
    use Carbon\Carbon;
    use Illuminate\Support\Str;

    $staffs = $staffs ?? collect();
    $unassignedQueue = $unassignedQueue ?? collect();

    try {
        $activeDate = Carbon::parse($date);
    } catch (\Throwable $exception) {
        $activeDate = now();
    }

    $dateValue = $activeDate->toDateString();
    $previousDate = $activeDate->copy()->subDay()->toDateString();
    $nextDate = $activeDate->copy()->addDay()->toDateString();
    $todayDate = now()->toDateString();

    $assignedBookings = $staffs->flatMap(fn ($staff) => $staff->bookings ?? collect());
    $activeStaffCount = $staffs->count();
    $busyStaffCount = $staffs->filter(fn ($staff) => ($staff->bookings ?? collect())->isNotEmpty())->count();
    $availableStaffCount = max(0, $activeStaffCount - $busyStaffCount);
    $assignedBookingCount = $assignedBookings->count();
    $unassignedQueueCount = $unassignedQueue->count();

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
            'completed', 'order_completed', 'refund_completed', 'paid', 'available', 'active' => 'success',
            'pending', 'pending_payment', 'waiting', 'confirmed', 'rescheduled', 'unpaid', 'dp' => 'warning',
            'checked_in', 'inprogress', 'in_progress', 'scheduled', 'queue', 'walk_in', 'pay_at_salon', 'full_payment' => 'info',
            'provider_cancelled', 'customer_cancelled', 'cancelled', 'no_show', 'rejected', 'failed', 'inactive' => 'danger',
            default => 'neutral',
        };
    };

    $formatTime = function ($value) {
        if (empty($value)) {
            return null;
        }

        try {
            return Carbon::parse($value)->format('H:i');
        } catch (\Throwable $exception) {
            return substr((string) $value, 0, 5) ?: null;
        }
    };

    $serviceNames = function ($booking) {
        $services = $booking->services ?? collect();

        if ($services->isNotEmpty()) {
            return $services->pluck('title')->join(', ');
        }

        return $booking->service->title ?? '-';
    };

    $bookingInitial = fn ($booking, $customerName) => strtoupper(substr((string) ($customerName ?: $booking->booking_code ?: 'B'), 0, 1));
    $staffInitial = fn ($staff) => strtoupper(substr((string) ($staff->full_name ?: $staff->first_name ?: 'S'), 0, 1));
@endphp

<section class="admin-category-page admin-booking-page provider-booking-category-page provider-calendar-category-page">
    <div class="admin-booking-route admin-category-route provider-booking-route provider-calendar-route">
        <div class="admin-breadcrumb">
            <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <strong>Kalender Staff</strong>
        </div>

        <div class="provider-booking-category-actions provider-calendar-actions provider-calendar-actions-desktop">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.calendar.index', ['date' => $previousDate]) }}" aria-label="Tanggal sebelumnya">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Sebelumnya
            </a>

            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.calendar.index', ['date' => $todayDate]) }}">
                Hari ini
            </a>

            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.calendar.index', ['date' => $nextDate]) }}" aria-label="Tanggal berikutnya">
                Berikutnya
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.bookings.index', ['date_from' => $dateValue, 'date_to' => $dateValue]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Bookings
            </a>
        </div>
    </div>

    <div class="admin-booking-summary-grid">
        <div class="admin-booking-summary-card pink">
            <span>Staff Aktif</span>
            <strong>{{ number_format($activeStaffCount) }}</strong>
            <small>{{ number_format($availableStaffCount) }} staff tanpa booking aktif</small>
        </div>

        <div class="admin-booking-summary-card yellow">
            <span>Staff Terisi</span>
            <strong>{{ number_format($busyStaffCount) }}</strong>
            <small>Staff punya jadwal di tanggal ini</small>
        </div>

        <div class="admin-booking-summary-card blue">
            <span>Booking Staff</span>
            <strong>{{ number_format($assignedBookingCount) }}</strong>
            <small>Booking aktif yang sudah punya staff</small>
        </div>

        <div class="admin-booking-summary-card orange">
            <span>Any Staff</span>
            <strong>{{ number_format($unassignedQueueCount) }}</strong>
            <small>Queue dan walk-in belum ditugaskan</small>
        </div>
    </div>

    <div class="admin-booking-card category-card provider-booking-category-card provider-calendar-category-card">
        <div class="admin-booking-tabs provider-calendar-tabs">
            <a href="#staff-calendar" class="admin-booking-tab active">Jadwal Staff</a>
            <a href="#any-staff-queue" class="admin-booking-tab">Antrian Any Staff</a>
            <a href="{{ provider_route('provider.bookings.index', ['date_from' => $dateValue, 'date_to' => $dateValue]) }}" class="admin-booking-tab">Detail Bookings</a>
        </div>

        <form method="GET" action="{{ provider_route('provider.calendar.index') }}" class="admin-booking-filter-panel compact provider-calendar-filter-panel">
            <div class="admin-booking-filter-row provider-calendar-filter-row">
                <label class="admin-booking-field mini">
                    <input type="date" name="date" value="{{ $dateValue }}" aria-label="Tanggal kalender staff" title="Tanggal kalender staff">
                </label>

                <div class="admin-booking-filter-buttons">
                    <button type="submit">Lihat</button>
                    @if ($dateValue !== $todayDate)
                        <a href="{{ provider_route('provider.calendar.index') }}">Reset</a>
                    @endif
                </div>
            </div>

            <div class="admin-booking-filter-meta">
                <span class="admin-booking-filter-count">{{ number_format($assignedBookingCount + $unassignedQueueCount) }} booking aktif</span>
                <span>Tanggal: {{ $activeDate->format('d M Y') }}</span>
                <span>{{ number_format($busyStaffCount) }} dari {{ number_format($activeStaffCount) }} staff terisi</span>
            </div>
        </form>

        <div class="admin-category-add-row provider-booking-category-actions provider-calendar-actions provider-calendar-actions-mobile">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.calendar.index', ['date' => $previousDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m15 18-6-6 6-6"></path>
                </svg>
                Sebelumnya
            </a>

            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.calendar.index', ['date' => $nextDate]) }}">
                Berikutnya
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="m9 18 6-6-6-6"></path>
                </svg>
            </a>
        </div>

        <div class="provider-calendar-board" id="staff-calendar">
            @forelse ($staffs as $staff)
                @php
                    $staffBookings = $staff->bookings ?? collect();
                    $staffStatus = $staff->current_status ?? $staff->status ?? 'available';
                @endphp

                <article class="provider-calendar-staff-card">
                    <header class="provider-calendar-staff-head">
                        <div class="admin-category-mobile-title provider-calendar-staff-title">
                            <span>{{ $staffInitial($staff) }}</span>

                            <div>
                                <strong>{{ $staff->full_name }}</strong>
                                <small>{{ number_format($staffBookings->count()) }} booking aktif</small>
                            </div>
                        </div>

                        <span class="admin-booking-status {{ $statusClass($staffStatus) }}">
                            {{ $statusLabel($staffStatus) }}
                        </span>
                    </header>

                    <div class="provider-calendar-event-list">
                        @forelse ($staffBookings as $booking)
                            @php
                                $customerName = $booking->customer->name ?? $booking->customer_name ?? 'Walk-in';
                                $bookingType = $booking->booking_type ?? 'scheduled';
                                $bookingStatus = $booking->status ?? 'pending';
                                $startTime = $formatTime($booking->start_time ?? $booking->booking_time ?? null);
                                $endTime = $formatTime($booking->estimated_end_time ?? null);
                                $queueLabel = $booking->queue_number ? '#' . $booking->queue_number : 'No queue';
                            @endphp

                            <article class="provider-calendar-event-card">
                                <div class="provider-calendar-event-time">
                                    <strong>{{ $startTime ?: $queueLabel }}</strong>
                                    @if ($endTime)
                                        <small>{{ $endTime }}</small>
                                    @else
                                        <small>{{ $statusLabel($bookingType) }}</small>
                                    @endif
                                </div>

                                <div class="provider-calendar-event-copy">
                                    <div class="provider-calendar-event-title">
                                        <span>{{ $bookingInitial($booking, $customerName) }}</span>
                                        <div>
                                            <strong>{{ $customerName }}</strong>
                                            <small>{{ $booking->booking_code ?? ('#' . $booking->id) }}</small>
                                        </div>
                                    </div>

                                    <p>{{ Str::limit($serviceNames($booking), 72) }}</p>

                                    <div class="provider-calendar-event-meta">
                                        <span class="admin-booking-status {{ $statusClass($bookingType) }}">
                                            {{ $statusLabel($bookingType) }}
                                        </span>
                                        <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">
                                            {{ $statusLabel($bookingStatus) }}
                                        </span>
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="provider-calendar-empty-inline">
                                <strong>Slot staff kosong.</strong>
                                <span>Belum ada booking aktif untuk tanggal ini.</span>
                            </div>
                        @endforelse
                    </div>
                </article>
            @empty
                <div class="admin-category-mobile-empty admin-booking-mobile-empty provider-calendar-empty-state">
                    <strong>Belum ada staff aktif.</strong>
                    <p>Tambahkan staff atau cek filter cabang provider.</p>
                </div>
            @endforelse
        </div>

        <section class="provider-calendar-queue-section" id="any-staff-queue">
            <div class="provider-calendar-section-head">
                <div>
                    <h3>Antrian Any Staff</h3>
                    <p>Queue dan walk-in yang belum dikunci ke staff tertentu.</p>
                </div>

                <span class="admin-booking-status info">{{ number_format($unassignedQueueCount) }} item</span>
            </div>

            <div class="admin-category-mobile-list admin-booking-mobile-list provider-calendar-queue-mobile">
                @forelse ($unassignedQueue as $booking)
                    @php
                        $customerName = $booking->customer->name ?? $booking->customer_name ?? 'Walk-in';
                        $bookingType = $booking->booking_type ?? 'queue';
                        $bookingStatus = $booking->status ?? 'pending';
                    @endphp

                    <article class="admin-category-mobile-card admin-booking-mobile-card provider-calendar-mobile-card">
                        <header class="admin-category-mobile-head">
                            <div class="admin-category-mobile-title">
                                <span>{{ $bookingInitial($booking, $customerName) }}</span>

                                <div>
                                    <strong>{{ $customerName }}</strong>
                                    <span>{{ $booking->booking_code ?? ('#' . $booking->id) }}</span>
                                </div>
                            </div>

                            <b>#{{ $booking->queue_number ?: '-' }}</b>
                        </header>

                        <div class="admin-category-mobile-main admin-booking-mobile-main provider-calendar-mobile-main">
                            <div>
                                <span>Service</span>
                                <strong>{{ Str::limit($serviceNames($booking), 32) }}</strong>
                            </div>

                            <div>
                                <span>Branch</span>
                                <strong>{{ $booking->branch->branch_name ?? '-' }}</strong>
                            </div>
                        </div>

                        <footer class="admin-category-mobile-footer provider-booking-mobile-footer">
                            <span class="admin-booking-status {{ $statusClass($bookingType) }}">{{ $statusLabel($bookingType) }}</span>
                            <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">{{ $statusLabel($bookingStatus) }}</span>
                        </footer>
                    </article>
                @empty
                    <div class="admin-category-mobile-empty admin-booking-mobile-empty">
                        <strong>Tidak ada antrian Any Staff.</strong>
                        <p>Semua queue aktif sudah punya staff atau belum ada queue di tanggal ini.</p>
                    </div>
                @endforelse
            </div>

            <div class="admin-booking-table-wrap category-table-wrap provider-calendar-queue-table-wrap">
                <table class="admin-booking-table detailed category-table provider-calendar-queue-table">
                    <thead>
                        <tr>
                            <th>Queue</th>
                            <th>Customer</th>
                            <th>Service</th>
                            <th>Branch</th>
                            <th>Mode</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($unassignedQueue as $booking)
                            @php
                                $customerName = $booking->customer->name ?? $booking->customer_name ?? 'Walk-in';
                                $bookingType = $booking->booking_type ?? 'queue';
                                $bookingStatus = $booking->status ?? 'pending';
                            @endphp

                            <tr>
                                <td>
                                    <div class="category-name-box provider-booking-code-box">
                                        <span class="category-thumb-placeholder">{{ $bookingInitial($booking, $customerName) }}</span>

                                        <div class="category-name-text">
                                            <strong>#{{ $booking->queue_number ?: '-' }}</strong>
                                            <small>{{ $booking->booking_code ?? ('ID #' . $booking->id) }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <div class="admin-booking-person">
                                        <span>{{ $bookingInitial($booking, $customerName) }}</span>
                                        <div>
                                            <strong>{{ $customerName }}</strong>
                                            <small>{{ $booking->customer_phone ?: 'No phone' }}</small>
                                        </div>
                                    </div>
                                </td>

                                <td>
                                    <p class="category-description-text">{{ Str::limit($serviceNames($booking), 92) }}</p>
                                </td>

                                <td>{{ $booking->branch->branch_name ?? '-' }}</td>

                                <td>
                                    <span class="admin-booking-status {{ $statusClass($bookingType) }}">
                                        {{ $statusLabel($bookingType) }}
                                    </span>
                                </td>

                                <td>
                                    <span class="admin-booking-status {{ $statusClass($bookingStatus) }}">
                                        {{ $statusLabel($bookingStatus) }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="admin-booking-empty">
                                    <div>
                                        <span>
                                            <svg viewBox="0 0 24 24">
                                                <path d="M4 6h16"></path>
                                                <path d="M4 12h16"></path>
                                                <path d="M4 18h10"></path>
                                            </svg>
                                        </span>

                                        <strong>Tidak ada antrian Any Staff.</strong>
                                        <p>Semua queue aktif sudah punya staff atau belum ada queue di tanggal ini.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</section>
@endsection
