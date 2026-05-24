@extends('provider.layouts.dashboard')

@section('title', 'Provider Analytics Dashboard - JasaKu')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Analytics performa booking, pembayaran, layanan, dan staff.')

@section('content')
@php
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $summaryIcon = function ($icon) {
        return match ($icon) {
            'revenue' => '<svg viewBox="0 0 24 24"><path d="M12 1v22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"/></svg>',
            'booking' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18"/><path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"/></svg>',
            'completed' => '<svg viewBox="0 0 24 24"><path d="M20 7 10 17l-5-5"/><path d="M21 12a9 9 0 1 1-3.2-6.9"/></svg>',
            default => '<svg viewBox="0 0 24 24"><path d="M12 8v5l3 2"/><path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z"/></svg>',
        };
    };
@endphp

<section class="analytics-dashboard-page">
    <div class="analytics-hero">
        <div class="analytics-hero-copy">
            <span class="analytics-kicker">Provider Analytics</span>
            <h1>Business Dashboard</h1>
            <p>Track revenue, booking flow, payment status, best-selling services, and staff performance from real salon data.</p>
        </div>

        <div class="dashboard-filter">
            @foreach ($periodOptions as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
                    class="dashboard-filter-option {{ $selectedPeriod === $key ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>
    </div>

    <div class="analytics-summary-grid">
        @foreach ($summaryCards as $card)
            <article class="analytics-summary-card">
                <div class="summary-card-top">
                    <span class="summary-card-icon">{!! $summaryIcon($card['icon']) !!}</span>
                    <span class="summary-change is-{{ $card['change']['direction'] }}">
                        {{ $card['change']['label'] }}
                    </span>
                </div>
                <h2>{{ $card['title'] }}</h2>
                <strong>{{ $card['value'] }}</strong>
            </article>
        @endforeach
    </div>

    <div class="analytics-grid-primary">
        <article class="analytics-card revenue-trend-card">
            <div class="analytics-card-header">
                <div>
                    <span class="analytics-card-kicker">Revenue</span>
                    <h2>Revenue Trend</h2>
                </div>
                <span class="analytics-period-pill">{{ $periodLabel }}</span>
            </div>

            @if (! $revenueChart['has_data'])
                <div class="analytics-empty-state">
                    <strong>No revenue data yet</strong>
                    <span>Revenue lines will appear after paid or booked transactions are recorded.</span>
                </div>
            @else
                <div class="line-chart-shell">
                    <span class="axis-label axis-top">{{ $revenueChart['max_label'] }}</span>
                    <span class="axis-label axis-mid">{{ $revenueChart['mid_label'] }}</span>

                    <svg class="revenue-line-chart" viewBox="0 0 {{ $revenueChart['width'] }} {{ $revenueChart['height'] }}" preserveAspectRatio="none" role="img" aria-label="Revenue trend chart">
                        <path class="chart-grid-line" d="M0 30 H{{ $revenueChart['width'] }}" />
                        <path class="chart-grid-line" d="M0 124 H{{ $revenueChart['width'] }}" />
                        <path class="chart-grid-line" d="M0 218 H{{ $revenueChart['width'] }}" />

                        @foreach ($revenueChart['series'] as $series)
                            @if ($series['visible'] && $series['path'])
                                <path class="chart-line" d="{{ $series['path'] }}" style="--line-color: {{ $series['color'] }}" />
                                @foreach ($series['points'] as $point)
                                    <circle class="chart-point" cx="{{ $point['x'] }}" cy="{{ $point['y'] }}" r="4.5" style="--line-color: {{ $series['color'] }}" />
                                @endforeach
                            @endif
                        @endforeach
                    </svg>

                    @foreach ($revenueChart['buckets'] as $index => $bucket)
                        @php
                            $sidePaddingPercent = (14 / max(1, $revenueChart['width'])) * 100;
                            $left = count($revenueChart['buckets']) === 1
                                ? 50
                                : $sidePaddingPercent + (($index / max(1, count($revenueChart['buckets']) - 1)) * (100 - ($sidePaddingPercent * 2)));
                            $tooltip = $bucket['label'] . "\n"
                                . 'Paid Revenue: ' . $rupiah($bucket['paid_revenue']) . "\n"
                                . 'Booked Revenue: ' . $rupiah($bucket['booked_revenue']);

                            if ($revenueChart['show_pending']) {
                                $tooltip .= "\n" . 'Pending Payment: ' . $rupiah($bucket['pending_payment']);
                            }
                        @endphp
                        <button type="button"
                            class="chart-hover-zone"
                            style="left: {{ $left }}%"
                            aria-label="Revenue detail {{ $bucket['label'] }}"
                            data-tooltip="{{ $tooltip }}"></button>
                    @endforeach
                </div>

                <div class="chart-label-row">
                    @foreach ($revenueChart['buckets'] as $bucket)
                        <span>{{ $bucket['label'] }}</span>
                    @endforeach
                </div>

                <div class="chart-legend">
                    @foreach ($revenueChart['series'] as $series)
                        @if ($series['visible'])
                            <span><i style="background: {{ $series['color'] }}"></i>{{ $series['label'] }}</span>
                        @endif
                    @endforeach
                </div>
            @endif
        </article>

        <article class="analytics-card donut-card">
            <div class="analytics-card-header">
                <div>
                    <span class="analytics-card-kicker">Payments</span>
                    <h2>Payment Status</h2>
                </div>
            </div>

            @if (! $paymentStatus['has_data'])
                <div class="analytics-empty-state compact">
                    <strong>No payment data</strong>
                    <span>Payment status will show after customer checkout activity.</span>
                </div>
            @else
                <div class="donut-layout">
                    <div class="analytics-donut" style="--donut-bg: {{ $paymentStatus['gradient'] }}">
                        <div>
                            <strong>{{ $paymentStatus['total_label'] }}</strong>
                            <span>Payments</span>
                        </div>
                    </div>

                    <div class="donut-legend">
                        @foreach ($paymentStatus['items'] as $item)
                            <div>
                                <span><i style="background: {{ $item['color'] }}"></i>{{ $item['name'] }}</span>
                                <strong>{{ number_format($item['total_booking'], 0, ',', '.') }} <small>{{ $item['percentage_label'] }}</small></strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </div>

    <div class="analytics-grid-secondary">
        <article class="analytics-card">
            <div class="analytics-card-header">
                <div>
                    <span class="analytics-card-kicker">Bookings</span>
                    <h2>Booking Summary</h2>
                </div>
            </div>

            @if (! $bookingSummary['has_data'])
                <div class="analytics-empty-state compact">
                    <strong>No booking activity</strong>
                    <span>Completed, pending, and cancelled booking bars will appear here.</span>
                </div>
            @else
                <div class="booking-bar-chart">
                    @foreach ($bookingSummary['buckets'] as $bucket)
                        <div class="booking-bar-group">
                            <div class="booking-bars">
                                @foreach ($bucket['bars'] as $key => $bar)
                                    <span class="booking-bar is-{{ $key }}"
                                        style="height: {{ $bar['height'] }}%"
                                        title="{{ $bar['label'] }}: {{ $bar['value'] }}"></span>
                                @endforeach
                            </div>
                            <small>{{ $bucket['label'] }}</small>
                        </div>
                    @endforeach
                </div>

                <div class="chart-legend">
                    <span><i class="legend-completed"></i>Completed Booking</span>
                    <span><i class="legend-pending"></i>Pending Booking</span>
                    <span><i class="legend-cancelled"></i>Cancelled Booking</span>
                </div>
            @endif
        </article>

        <article class="analytics-card donut-card">
            <div class="analytics-card-header">
                <div>
                    <span class="analytics-card-kicker">Services</span>
                    <h2>Best Selling Services</h2>
                </div>
            </div>

            @if (! $bestSellingServices['has_data'])
                <div class="analytics-empty-state compact">
                    <strong>No service data</strong>
                    <span>Top services will appear after bookings are created.</span>
                </div>
            @else
                <div class="donut-layout">
                    <div class="analytics-donut" style="--donut-bg: {{ $bestSellingServices['gradient'] }}">
                        <div>
                            <strong>{{ $bestSellingServices['total_label'] }}</strong>
                            <span>Bookings</span>
                        </div>
                    </div>

                    <div class="donut-legend">
                        @foreach ($bestSellingServices['items'] as $item)
                            <div>
                                <span><i style="background: {{ $item['color'] }}"></i>{{ $item['name'] }}</span>
                                <strong>{{ number_format($item['total_booking'], 0, ',', '.') }} <small>{{ $item['percentage_label'] }}</small></strong>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </article>
    </div>

    <article class="analytics-card staff-performance-card">
        <div class="analytics-card-header">
            <div>
                <span class="analytics-card-kicker">Staff</span>
                <h2>Top Staff Performance</h2>
            </div>
            <span class="analytics-period-pill">{{ $periodLabel }}</span>
        </div>

        @if (! $topStaffPerformance['has_data'])
            <div class="analytics-empty-state compact">
                <strong>No staff performance yet</strong>
                <span>Staff ranking will show after bookings are assigned to stylists.</span>
            </div>
        @else
            <div class="staff-performance-list">
                @foreach ($topStaffPerformance['items'] as $staff)
                    <div class="staff-performance-row">
                        <div class="staff-performance-name">
                            <strong>{{ $staff['name'] }}</strong>
                            <span>{{ number_format($staff['total_booking'], 0, ',', '.') }} booking &middot; Rating {{ $staff['rating_label'] }}</span>
                        </div>
                        <div class="staff-performance-bar" aria-hidden="true">
                            <span style="width: {{ $staff['width'] }}%"></span>
                        </div>
                        <strong class="staff-revenue">{{ $staff['revenue_label'] }}</strong>
                    </div>
                @endforeach
            </div>
        @endif
    </article>

    <div class="analytics-tooltip" data-dashboard-tooltip role="tooltip"></div>
</section>
@endsection
