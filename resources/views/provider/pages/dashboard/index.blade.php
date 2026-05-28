@extends('provider.layouts.dashboard')

@section('title', 'Provider Dashboard - JasaKu')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Analytics performa booking, pembayaran, layanan, dan staff.')

@section('content')
@php
    $rupiah = fn ($amount) => 'Rp' . number_format((float) $amount, 0, ',', '.');
    $number = fn ($value) => number_format((float) $value, 0, ',', '.');
    $summaryCards = collect($summaryCards ?? []);
    $chartBuckets = collect($revenueChart['buckets'] ?? [])->values();
    $miniBuckets = $chartBuckets->count() > 6 ? $chartBuckets->slice(-6)->values() : $chartBuckets;
    $visibleRevenueSeries = collect($revenueChart['series'] ?? [])
        ->filter(fn ($series) => ! empty($series['visible']) && ! empty($series['path']))
        ->values();
    $latestBucket = $chartBuckets->last() ?: [];
    $chartWidth = (int) ($revenueChart['width'] ?? 760);
    $chartHeight = (int) ($revenueChart['height'] ?? 260);
    $chartBottom = 218;
    $lineTone = fn ($index) => ['purple', 'orange', 'blue'][$index % 3];
    $lineClass = fn ($index) => $lineTone($index) . '-line';
    $pointClass = fn ($index) => $lineTone($index);
    $paymentItems = collect($paymentStatus['items'] ?? [])->values();
    $serviceItems = collect($bestSellingServices['items'] ?? [])->values();
    $bookingBuckets = collect($bookingSummary['buckets'] ?? [])->values();
    $staffItems = collect($topStaffPerformance['items'] ?? [])->values();

    $summaryIcon = function ($icon) {
        return match ($icon) {
            'revenue' => '<svg viewBox="0 0 24 24"><path d="M12 1v22"></path><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7H14a3.5 3.5 0 0 1 0 7H6"></path></svg>',
            'booking' => '<svg viewBox="0 0 24 24"><path d="M8 2v4M16 2v4M3 10h18"></path><path d="M5 4h14a2 2 0 0 1 2 2v13a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z"></path></svg>',
            'completed' => '<svg viewBox="0 0 24 24"><path d="M20 7 10 17l-5-5"></path><path d="M21 12a9 9 0 1 1-3.2-6.9"></path></svg>',
            default => '<svg viewBox="0 0 24 24"><path d="M12 8v5l3 2"></path><path d="M21 12a9 9 0 1 1-9-9 9 9 0 0 1 9 9Z"></path></svg>',
        };
    };

    $bucketValueFor = function (array $bucket, string $icon): float {
        return match ($icon) {
            'revenue' => (float) ($bucket['paid_revenue'] ?? 0),
            'booking' => (float) (($bucket['completed_booking'] ?? 0) + ($bucket['pending_booking'] ?? 0) + ($bucket['cancelled_booking'] ?? 0)),
            'completed' => (float) ($bucket['completed_booking'] ?? 0),
            default => (float) ($bucket['pending_payment'] ?? 0),
        };
    };

    $bucketValueLabel = function (float $value, string $icon) use ($rupiah, $number): string {
        return in_array($icon, ['revenue', 'pending'], true) ? $rupiah($value) : $number($value);
    };

    $areaPathFor = function (?array $series) use ($chartBottom): string {
        $points = collect($series['points'] ?? [])->values();

        if ($points->isEmpty()) {
            return '';
        }

        $firstPoint = $points->first();
        $lastPoint = $points->last();
        $linePoints = $points
            ->map(fn ($point) => ($point['x'] ?? 0) . ' ' . ($point['y'] ?? $chartBottom))
            ->implode(' L ');

        return 'M ' . ($firstPoint['x'] ?? 0) . ' ' . $chartBottom
            . ' L ' . $linePoints
            . ' L ' . ($lastPoint['x'] ?? 0) . ' ' . $chartBottom . ' Z';
    };

    $firstSeries = $visibleRevenueSeries->get(0);
    $secondSeries = $visibleRevenueSeries->get(1);
    $latestPoint = collect($firstSeries['points'] ?? [])->last() ?: [];
    $markerX = (float) ($latestPoint['x'] ?? 0);
    $tooltipLeft = max(12, min($chartWidth - 190, $markerX - 86));

    $insights = [
        [
            'label' => 'Branches',
            'value' => $number($stats['branches_count'] ?? 0),
            'note' => 'Lokasi layanan aktif',
        ],
        [
            'label' => 'Services',
            'value' => $number($stats['services_count'] ?? 0),
            'note' => 'Menu layanan tersedia',
        ],
        [
            'label' => 'Staff',
            'value' => $number($stats['staff_count'] ?? 0),
            'note' => 'Tim operasional',
        ],
        [
            'label' => 'Plan',
            'value' => $currentPlan['name'] ?? 'Life Time',
            'note' => $currentPlan['description'] ?? 'Subscription aktif',
        ],
    ];
@endphp

<section class="admin-dashboard-page provider-admin-dashboard-page">
    <div class="admin-dashboard-tabs-row">
        <div class="admin-dashboard-tabs" aria-label="Dashboard period">
            @foreach ($periodOptions as $key => $label)
                <a href="{{ request()->fullUrlWithQuery(['period' => $key]) }}"
                    class="admin-dashboard-tab {{ $selectedPeriod === $key ? 'active' : '' }}">
                    {{ $label }}
                </a>
            @endforeach
        </div>

        <div class="admin-dashboard-actions">
            <a href="{{ provider_route('provider.bookings.index') }}" class="admin-dashboard-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Bookings
            </a>

            <a href="{{ provider_route('provider.services.index') }}" class="admin-dashboard-action-btn dark">
                <svg viewBox="0 0 24 24">
                    <path d="M4 7h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 17h10"></path>
                </svg>
                Services
            </a>
        </div>
    </div>

    <div class="admin-dashboard-metric-grid provider-dashboard-metric-grid">
        @foreach ($summaryCards as $card)
            @php
                $icon = $card['icon'] ?? 'pending';
                $metricBuckets = $miniBuckets->map(fn ($bucket) => [
                    'label' => $bucket['label'] ?? '-',
                    'value' => $bucketValueFor($bucket, $icon),
                ]);
                $metricMax = max(1, (float) ($metricBuckets->max('value') ?: 0));
            @endphp

            <article class="admin-metric-card">
                <div class="admin-metric-head">
                    <h3>{{ $card['title'] ?? '-' }}</h3>
                    <span class="provider-metric-icon">{!! $summaryIcon($icon) !!}</span>
                </div>

                <div class="admin-metric-value">
                    <strong>{{ $card['value'] ?? '-' }}</strong>
                    <span class="{{ ($card['change']['direction'] ?? 'flat') === 'down' ? 'negative' : 'positive' }}">
                        &bull; {{ $card['change']['label'] ?? '0% vs previous' }}
                    </span>
                </div>

                <div class="admin-mini-chart">
                    @foreach ($metricBuckets as $bucket)
                        @php
                            $barHeight = (float) $bucket['value'] > 0
                                ? max(8, (int) round(((float) $bucket['value'] / $metricMax) * 44))
                                : 5;
                        @endphp
                        <span class="{{ $loop->last ? 'active' : '' }}"
                            style="height: {{ $barHeight }}px"
                            title="{{ $bucket['label'] }}: {{ $bucketValueLabel((float) $bucket['value'], $icon) }}"></span>
                    @endforeach
                </div>

                <div class="admin-mini-months">
                    @foreach ($metricBuckets as $bucket)
                        <span>{{ $bucket['label'] }}</span>
                    @endforeach
                </div>
            </article>
        @endforeach
    </div>

    <div class="admin-dashboard-chart-grid">
        <div class="admin-chart-card">
            <div class="admin-chart-head">
                <h3>Revenue forecast</h3>

                <div class="admin-chart-actions">
                    <button type="button">
                        <svg viewBox="0 0 24 24">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <path d="M5 5h14v16H5z"></path>
                            <path d="M3 10h18"></path>
                        </svg>
                        {{ $periodLabel }}
                    </button>
                </div>
            </div>

            @if (! ($revenueChart['has_data'] ?? false))
                <div class="analytics-empty-state compact">
                    <strong>No revenue data yet</strong>
                    <span>Revenue lines will appear after paid or booked transactions are recorded.</span>
                </div>
            @else
                <div class="admin-revenue-chart-wrap provider-revenue-chart-wrap">
                    <svg class="admin-revenue-chart" viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" preserveAspectRatio="none" role="img" aria-label="Provider revenue trend">
                        <defs>
                            <linearGradient id="adminPurpleArea" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#a855f7" stop-opacity=".24"></stop>
                                <stop offset="100%" stop-color="#a855f7" stop-opacity="0"></stop>
                            </linearGradient>

                            <linearGradient id="adminOrangeArea" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#f97316" stop-opacity=".18"></stop>
                                <stop offset="100%" stop-color="#f97316" stop-opacity="0"></stop>
                            </linearGradient>
                        </defs>

                        <g class="chart-grid-lines">
                            <line x1="0" y1="30" x2="{{ $chartWidth }}" y2="30"></line>
                            <line x1="0" y1="92" x2="{{ $chartWidth }}" y2="92"></line>
                            <line x1="0" y1="155" x2="{{ $chartWidth }}" y2="155"></line>
                            <line x1="0" y1="{{ $chartBottom }}" x2="{{ $chartWidth }}" y2="{{ $chartBottom }}"></line>
                        </g>

                        <path class="area purple-area" d="{{ $areaPathFor($firstSeries) }}"></path>
                        <path class="area orange-area" d="{{ $areaPathFor($secondSeries) }}"></path>

                        @foreach ($visibleRevenueSeries as $series)
                            <path class="line {{ $lineClass($loop->index) }}" d="{{ $series['path'] }}"></path>

                            @foreach ($series['points'] ?? [] as $point)
                                <circle class="chart-point {{ $pointClass($loop->parent->index) }}"
                                    cx="{{ $point['x'] }}"
                                    cy="{{ $point['y'] }}"
                                    r="4.5"></circle>
                            @endforeach
                        @endforeach

                        <line class="chart-marker"
                            x1="{{ $markerX }}"
                            y1="30"
                            x2="{{ $markerX }}"
                            y2="{{ $chartBottom }}"></line>
                    </svg>

                    @foreach ($chartBuckets as $index => $bucket)
                        @php
                            $sidePaddingPercent = (14 / max(1, $chartWidth)) * 100;
                            $left = $chartBuckets->count() === 1
                                ? 50
                                : $sidePaddingPercent + (($index / max(1, $chartBuckets->count() - 1)) * (100 - ($sidePaddingPercent * 2)));
                            $tooltip = ($bucket['label'] ?? '-') . "\n"
                                . 'Paid Revenue: ' . $rupiah($bucket['paid_revenue'] ?? 0) . "\n"
                                . 'Booked Revenue: ' . $rupiah($bucket['booked_revenue'] ?? 0);

                            if ($revenueChart['show_pending'] ?? false) {
                                $tooltip .= "\n" . 'Pending Payment: ' . $rupiah($bucket['pending_payment'] ?? 0);
                            }
                        @endphp

                        <button type="button"
                            class="provider-chart-hover-zone"
                            style="left: {{ $left }}%"
                            aria-label="Revenue detail {{ $bucket['label'] ?? '-' }}"
                            data-tooltip="{{ $tooltip }}"></button>
                    @endforeach

                    <div class="admin-chart-tooltip provider-static-chart-tooltip" style="left: {{ $tooltipLeft }}px; top: 52px;">
                        <strong>{{ $latestBucket['label'] ?? $periodLabel }}</strong>

                        @foreach ($visibleRevenueSeries as $series)
                            @php
                                $tone = $lineTone($loop->index);
                                $seriesKey = $series['key'] ?? '';
                            @endphp
                            <p>
                                <span class="dot {{ $tone }}"></span>
                                {{ $rupiah($latestBucket[$seriesKey] ?? 0) }}
                                <b>{{ strtolower($series['label'] ?? 'value') }}</b>
                            </p>
                        @endforeach
                    </div>
                </div>

                <div class="admin-chart-legend">
                    @foreach ($visibleRevenueSeries as $series)
                        <span><i class="{{ $lineTone($loop->index) }}"></i> {{ $series['label'] }}</span>
                    @endforeach
                </div>

                <div class="admin-mobile-summary-list">
                    @foreach ($visibleRevenueSeries as $series)
                        @php
                            $seriesKey = $series['key'] ?? '';
                        @endphp
                        <div>
                            <span>{{ $series['label'] }}</span>
                            <strong>{{ $rupiah($latestBucket[$seriesKey] ?? 0) }}</strong>
                            <small>{{ $latestBucket['label'] ?? $periodLabel }}</small>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="admin-chart-card admin-status-card">
            <div class="admin-chart-head">
                <h3>Payment Status</h3>

                <div class="admin-chart-actions">
                    <a href="{{ provider_route('provider.payments.index') }}">Payments</a>
                </div>
            </div>

            @if (! ($paymentStatus['has_data'] ?? false))
                <div class="analytics-empty-state compact">
                    <strong>No payment data</strong>
                    <span>Payment status will show after customer checkout activity.</span>
                </div>
            @else
                <div class="admin-gauge-wrap">
                    <div class="admin-gauge">
                        <span class="gauge-orange"></span>
                        <span class="gauge-purple"></span>
                        <span class="gauge-blue"></span>
                        <span class="gauge-green"></span>
                    </div>

                    <div class="admin-gauge-center">
                        <span>Total</span>
                        <strong>{{ $paymentStatus['total_label'] ?? '0' }}</strong>
                    </div>
                </div>

                <div class="admin-status-list">
                    @foreach ($paymentItems as $item)
                        <div>
                            <span><i class="{{ $lineTone($loop->index) }}"></i> {{ $item['name'] ?? '-' }}</span>
                            <strong>{{ $number($item['total_booking'] ?? 0) }}</strong>
                            <em>{{ $item['percentage_label'] ?? '0%' }}</em>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="admin-dashboard-panel">
        <div class="admin-insight-grid">
            @foreach ($insights as $insight)
                <article class="admin-insight-card">
                    <span>{{ $insight['label'] }}</span>
                    <strong>{{ $insight['value'] }}</strong>
                    <small>{{ $insight['note'] }}</small>
                </article>
            @endforeach
        </div>
    </div>

    <div class="admin-dashboard-split-grid">
        <div class="admin-dashboard-table-card provider-booking-summary-card">
            <div class="admin-table-card-head">
                <h3>Booking Summary</h3>

                <div class="admin-table-tools">
                    <a href="{{ provider_route('provider.bookings.index') }}">View all</a>
                </div>
            </div>

            @if (! ($bookingSummary['has_data'] ?? false))
                <div class="analytics-empty-state compact">
                    <strong>No booking activity</strong>
                    <span>Completed, pending, and cancelled booking bars will appear here.</span>
                </div>
            @else
                <div class="admin-mobile-summary-list">
                    @foreach ($bookingBuckets->slice(-4) as $bucket)
                        @php
                            $completed = (int) ($bucket['completed_booking'] ?? 0);
                            $pending = (int) ($bucket['pending_booking'] ?? 0);
                            $cancelled = (int) ($bucket['cancelled_booking'] ?? 0);
                        @endphp
                        <div>
                            <span>{{ $bucket['label'] ?? '-' }}</span>
                            <strong>{{ $number($completed + $pending + $cancelled) }}</strong>
                            <small>{{ $completed }} completed, {{ $pending }} pending, {{ $cancelled }} cancelled</small>
                        </div>
                    @endforeach
                </div>

                <div class="booking-bar-chart provider-booking-bar-chart">
                    @foreach ($bookingBuckets as $bucket)
                        <div class="booking-bar-group">
                            <div class="booking-bars">
                                @foreach ($bucket['bars'] ?? [] as $key => $bar)
                                    <span class="booking-bar is-{{ $key }}"
                                        style="height: {{ $bar['height'] ?? 2 }}%"
                                        title="{{ $bar['label'] ?? '-' }}: {{ $bar['value'] ?? 0 }}"></span>
                                @endforeach
                            </div>
                            <small>{{ $bucket['label'] ?? '-' }}</small>
                        </div>
                    @endforeach
                </div>

                <div class="admin-chart-legend">
                    <span><i class="green"></i> Completed Booking</span>
                    <span><i class="purple"></i> Pending Booking</span>
                    <span><i class="orange"></i> Cancelled Booking</span>
                </div>
            @endif
        </div>

        <div class="admin-chart-card admin-panel-card">
            <div class="admin-chart-head">
                <h3>Best Selling Services</h3>

                <div class="admin-chart-actions">
                    <a href="{{ provider_route('provider.services.index') }}">Services</a>
                </div>
            </div>

            @if (! ($bestSellingServices['has_data'] ?? false))
                <p class="admin-empty-inline">No service data yet.</p>
            @else
                <div class="donut-layout provider-admin-donut-layout">
                    <div class="analytics-donut" style="--donut-bg: {{ $bestSellingServices['gradient'] }}">
                        <div>
                            <strong>{{ $bestSellingServices['total_label'] }}</strong>
                            <span>Bookings</span>
                        </div>
                    </div>
                </div>

                <div class="admin-compact-list">
                    @foreach ($serviceItems as $item)
                        <div>
                            <span>{{ $item['name'] ?? '-' }}</span>
                            <strong>{{ $number($item['total_booking'] ?? 0) }}</strong>
                            <em>{{ $item['percentage_label'] ?? '0%' }}</em>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <div class="admin-dashboard-table-card">
        <div class="admin-table-card-head">
            <h3>Top Staff Performance</h3>

            <div class="admin-table-tools">
                <a href="{{ provider_route('provider.staffs.index') }}">Staff</a>
                <a href="{{ provider_route('provider.bookings.index') }}">Bookings</a>
            </div>
        </div>

        <div class="admin-mobile-summary-list">
            @forelse ($staffItems as $staff)
                <div>
                    <span>{{ $staff['name'] }}</span>
                    <strong>{{ $number($staff['total_booking'] ?? 0) }}</strong>
                    <small>{{ $staff['revenue_label'] ?? $rupiah(0) }} revenue, rating {{ $staff['rating_label'] ?? '-' }}</small>
                </div>
            @empty
                <div>
                    <span>No staff performance yet.</span>
                    <strong>0</strong>
                    <small>Staff ranking will show after bookings are assigned.</small>
                </div>
            @endforelse
        </div>

        <div class="admin-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Staff name</th>
                        <th>Bookings</th>
                        <th>Rating</th>
                        <th>Revenue</th>
                        <th>Performance</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($staffItems as $staff)
                        @php
                            $initial = strtoupper(substr(trim((string) ($staff['name'] ?? '')), 0, 1)) ?: 'S';
                            $performanceWidth = (int) ($staff['width'] ?? 0);
                            $performanceClass = $performanceWidth >= 70 ? 'active' : ($performanceWidth >= 35 ? 'pending' : 'inactive');
                            $performanceLabel = $performanceWidth >= 70 ? 'Top performer' : ($performanceWidth >= 35 ? 'Growing' : 'Needs activity');
                        @endphp
                        <tr>
                            <td>
                                <div class="admin-table-name">
                                    <span>{{ $initial }}</span>
                                    <div>
                                        <strong>{{ $staff['name'] }}</strong>
                                        <small>{{ $staff['revenue_label'] ?? $rupiah(0) }} generated</small>
                                    </div>
                                </div>
                            </td>
                            <td>{{ $number($staff['total_booking'] ?? 0) }}</td>
                            <td>{{ $staff['rating_label'] ?? '-' }}</td>
                            <td>{{ $staff['revenue_label'] ?? $rupiah(0) }}</td>
                            <td><span class="table-status {{ $performanceClass }}">{{ $performanceLabel }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">No staff performance yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="analytics-tooltip" data-dashboard-tooltip role="tooltip"></div>
</section>
@endsection
