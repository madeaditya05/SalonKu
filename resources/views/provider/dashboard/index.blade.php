@extends('provider.layouts.dashboard')

@section('title', 'Provider Dashboard - JasaKu')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Overview aktivitas provider kamu hari ini.')

@section('content')
@php
    $stats = array_merge([
        'upcoming_bookings' => 0,
        'completed_bookings' => 0,
        'order_completed' => 0,
        'canceled_bookings' => 0,
        'total_earnings' => 0,
        'total_income' => 0,
        'total_due' => 0,
    ], $stats ?? []);

    $providerProfile = auth()->user()?->providerProfile;
    $documentStatus = optional($providerProfile)->document_status ?? 'pending';

    $totalBookings = $stats['upcoming_bookings'] + $stats['completed_bookings'] + $stats['canceled_bookings'];
    $completedPercent = $totalBookings > 0 ? round(($stats['completed_bookings'] / $totalBookings) * 100) : 0;
@endphp

<section class="provider-dashboard-page">
    <div class="dashboard-tabs-row">
        <div class="dashboard-tabs">
            <button type="button" class="dashboard-tab active">Overview</button>
            <button type="button" class="dashboard-tab">Sales</button>
            <button type="button" class="dashboard-tab">Order</button>
            <button type="button" class="dashboard-tab">Report</button>
        </div>

        <div class="dashboard-actions">
            <button type="button" class="dash-action-btn light">
                <svg viewBox="0 0 24 24">
                    <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/>
                    <path d="M1 14h6M9 8h6M17 16h6"/>
                </svg>
                Filter
            </button>

            <button type="button" class="dash-action-btn dark">
                <svg viewBox="0 0 24 24">
                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                    <path d="M7 10l5 5 5-5"/>
                    <path d="M12 15V3"/>
                </svg>
                Export all
            </button>
        </div>
    </div>

    <div class="dashboard-metric-grid">
        <div class="metric-card">
            <div class="metric-card-head">
                <h3>Upcoming Bookings</h3>
                <button type="button">⋮</button>
            </div>

            <div class="metric-value-row">
                <strong>{{ number_format($stats['upcoming_bookings']) }}</strong>
                <span class="metric-change positive">● 10.2% vs last month</span>
            </div>

            <div class="mini-bars">
                <span style="height: 42%"></span>
                <span style="height: 33%"></span>
                <span style="height: 48%"></span>
                <span style="height: 30%"></span>
                <span style="height: 39%"></span>
                <span class="active" style="height: 55%"></span>
            </div>

            <div class="mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-card-head">
                <h3>Completed Bookings</h3>
                <button type="button">⋮</button>
            </div>

            <div class="metric-value-row">
                <strong>{{ number_format($stats['completed_bookings']) }}</strong>
                <span class="metric-change negative">● 5.75% vs last month</span>
            </div>

            <div class="mini-bars">
                <span style="height: 35%"></span>
                <span style="height: 38%"></span>
                <span style="height: 45%"></span>
                <span style="height: 36%"></span>
                <span style="height: 48%"></span>
                <span class="active" style="height: 40%"></span>
            </div>

            <div class="mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-card-head">
                <h3>Total Earnings</h3>
                <button type="button">⋮</button>
            </div>

            <div class="metric-value-row">
                <strong>${{ number_format($stats['total_earnings'], 2) }}</strong>
                <span class="metric-change positive">● 8.55% vs last month</span>
            </div>

            <div class="mini-bars">
                <span style="height: 36%"></span>
                <span style="height: 44%"></span>
                <span style="height: 37%"></span>
                <span style="height: 42%"></span>
                <span style="height: 50%"></span>
                <span class="active" style="height: 58%"></span>
            </div>

            <div class="mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>
    </div>

    <div class="dashboard-analytics-grid">
        <div class="analytics-card revenue-card">
            <div class="analytics-head">
                <h3>Revenue forecast</h3>

                <div class="analytics-tools">
                    <button type="button">
                        <svg viewBox="0 0 24 24">
                            <path d="M8 2v4M16 2v4"/>
                            <path d="M3 10h18"/>
                            <path d="M5 5h14v16H5z"/>
                        </svg>
                        Monthly
                    </button>

                    <button type="button" class="square-btn">⋮</button>
                </div>
            </div>

            <div class="chart-wrap">
                <svg class="revenue-chart" viewBox="0 0 760 270" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="purpleArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#a855f7" stop-opacity=".18"/>
                            <stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>
                        </linearGradient>
                        <linearGradient id="orangeArea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0%" stop-color="#f97316" stop-opacity=".13"/>
                            <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    <g class="chart-grid">
                        <line x1="0" y1="30" x2="760" y2="30"/>
                        <line x1="0" y1="80" x2="760" y2="80"/>
                        <line x1="0" y1="130" x2="760" y2="130"/>
                        <line x1="0" y1="180" x2="760" y2="180"/>
                        <line x1="0" y1="230" x2="760" y2="230"/>
                    </g>

                    <path class="area-purple" d="M20 185 C80 165,120 140,170 120 C230 80,290 70,350 95 C410 120,450 75,500 90 C560 105,590 40,640 55 C700 70,720 155,750 145 L750 250 L20 250 Z"/>
                    <path class="area-orange" d="M20 220 C80 210,120 205,170 145 C230 110,280 105,330 120 C390 135,420 115,470 135 C530 155,580 90,630 110 C690 135,700 90,750 75 L750 250 L20 250 Z"/>

                    <path class="line-purple" d="M20 185 C80 165,120 140,170 120 C230 80,290 70,350 95 C410 120,450 75,500 90 C560 105,590 40,640 55 C700 70,720 155,750 145"/>
                    <path class="line-orange" d="M20 220 C80 210,120 205,170 145 C230 110,280 105,330 120 C390 135,420 115,470 135 C530 155,580 90,630 110 C690 135,700 90,750 75"/>
                    <path class="line-blue" d="M20 205 C80 195,120 205,170 165 C230 145,285 138,335 150 C390 160,430 150,480 165 C540 180,590 160,640 172 C690 184,720 174,750 168"/>

                    <line class="chart-marker" x1="380" y1="30" x2="380" y2="230"/>
                    <circle class="point purple" cx="380" cy="105" r="5"/>
                    <circle class="point orange" cx="380" cy="132" r="5"/>
                    <circle class="point blue" cx="380" cy="160" r="5"/>
                </svg>

                <div class="chart-tooltip">
                    <strong>July, 2026</strong>
                    <p><span class="dot purple"></span> ${{ number_format($stats['total_earnings'], 2) }} <b>+8.5%</b></p>
                    <p><span class="dot orange"></span> ${{ number_format($stats['total_income'], 2) }} <b>+6.0%</b></p>
                    <p><span class="dot blue"></span> ${{ number_format($stats['total_due'], 2) }} <b>12.0%</b></p>
                </div>
            </div>

            <div class="chart-legend">
                <span><i class="purple"></i> Profit margin</span>
                <span><i class="orange"></i> Operating expenses</span>
                <span><i class="blue"></i> Sales revenue</span>
            </div>
        </div>

        <div class="analytics-card source-card">
            <div class="analytics-head">
                <h3>Provider Status</h3>

                <div class="analytics-tools">
                    <button type="button">Documents</button>
                    <button type="button" class="square-btn">⋮</button>
                </div>
            </div>

            <div class="source-gauge">
                <div class="gauge-arc">
                    <span class="arc orange"></span>
                    <span class="arc purple"></span>
                    <span class="arc blue"></span>
                    <span class="arc green"></span>
                </div>

                <div class="gauge-center">
                    <span>Total</span>
                    <strong>{{ number_format($totalBookings) }}</strong>
                </div>
            </div>

            <div class="source-list">
                <div>
                    <span><i class="orange"></i> Completed</span>
                    <strong>{{ $stats['completed_bookings'] }}</strong>
                    <em>{{ $completedPercent }}%</em>
                </div>

                <div>
                    <span><i class="purple"></i> Upcoming</span>
                    <strong>{{ $stats['upcoming_bookings'] }}</strong>
                    <em>{{ $totalBookings > 0 ? round(($stats['upcoming_bookings'] / $totalBookings) * 100) : 0 }}%</em>
                </div>

                <div>
                    <span><i class="blue"></i> Canceled</span>
                    <strong>{{ $stats['canceled_bookings'] }}</strong>
                    <em>{{ $totalBookings > 0 ? round(($stats['canceled_bookings'] / $totalBookings) * 100) : 0 }}%</em>
                </div>

                <div>
                    <span><i class="green"></i> Document</span>
                    <strong>{{ ucfirst($documentStatus) }}</strong>
                    <em>{{ $documentStatus === 'verified' ? '100%' : '0%' }}</em>
                </div>
            </div>

            <a href="{{ route('provider.profile') }}" class="view-details-btn">
                View details
            </a>
        </div>
    </div>

    <div class="dashboard-table-card">
        <div class="table-card-head">
            <h3>Table data services</h3>

            <div class="table-tools">
                <div class="table-search">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="m21 21-4.3-4.3"/>
                    </svg>
                    <input type="text" placeholder="Search data">
                </div>

                <button type="button">
                    <svg viewBox="0 0 24 24">
                        <path d="M3 7h18M6 12h12M10 17h4"/>
                    </svg>
                    Sort by
                </button>

                <button type="button">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/>
                        <path d="M1 14h6M9 8h6M17 16h6"/>
                    </svg>
                    Filter
                </button>
            </div>
        </div>

        <div class="dashboard-table-wrap">
            <table class="dashboard-service-table">
                <thead>
                    <tr>
                        <th>Service name</th>
                        <th>Code</th>
                        <th>Price</th>
                        <th>Status</th>
                        <th>Verify</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse($topServices ?? [] as $service)
                        <tr>
                            <td>
                                <div class="service-name-cell">
                                    <span>{{ strtoupper(substr($service->title ?? 'S', 0, 1)) }}</span>
                                    <div>
                                        <strong>{{ $service->title ?? 'Untitled Service' }}</strong>
                                        <small>{{ $service->category ?? 'General' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $service->code ?? 'No Code' }}</td>

                            <td>${{ number_format((float) ($service->price ?? 0), 2) }}</td>

                            <td>
                                <span class="table-status {{ $service->status ?? 'active' }}">
                                    {{ ucfirst($service->status ?? 'active') }}
                                </span>
                            </td>

                            <td>
                                <span class="table-status {{ $documentStatus }}">
                                    {{ ucfirst($documentStatus) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="dashboard-empty-state">
                                Belum ada data service.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection