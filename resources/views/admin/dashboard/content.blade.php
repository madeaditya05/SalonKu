@php
    $stats = $stats ?? [];

    $totalProviders = $stats['total_providers'] ?? 0;
    $activeProviders = $stats['active_providers'] ?? 0;
    $inactiveProviders = $stats['inactive_providers'] ?? 0;

    $totalServices = $stats['total_services'] ?? 0;
    $activeServices = $stats['active_services'] ?? 0;
    $inactiveServices = $stats['inactive_services'] ?? 0;

    $totalBookings = $stats['total_bookings'] ?? 0;
    $completedBookings = $stats['completed_bookings'] ?? 0;
    $pendingBookings = $stats['pending_bookings'] ?? 0;

    $totalAmount = $stats['total_amount'] ?? 0;
    $completedAmount = $stats['completed_amount'] ?? 0;
    $pendingAmount = $stats['pending_amount'] ?? 0;

    $adminName = auth()->user()->name ?? 'Demo Admin';
@endphp

<section class="admin-dashboard-page">
    <div class="admin-dashboard-tabs-row">
        <div class="admin-dashboard-tabs">
            <button type="button" class="admin-dashboard-tab active">Overview</button>
            <button type="button" class="admin-dashboard-tab">Sales</button>
            <button type="button" class="admin-dashboard-tab">Order</button>
            <button type="button" class="admin-dashboard-tab">Report</button>
        </div>

        <div class="admin-dashboard-actions">
            <button type="button" class="admin-dashboard-action-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M4 21v-7"></path>
                    <path d="M4 10V3"></path>
                    <path d="M12 21v-9"></path>
                    <path d="M12 8V3"></path>
                    <path d="M20 21v-5"></path>
                    <path d="M20 12V3"></path>
                    <path d="M1 14h6"></path>
                    <path d="M9 8h6"></path>
                    <path d="M17 16h6"></path>
                </svg>
                Filter
            </button>

            <button type="button" class="admin-dashboard-action-btn dark">
                <svg viewBox="0 0 24 24">
                    <path d="M12 3v12"></path>
                    <path d="m7 10 5 5 5-5"></path>
                    <path d="M5 21h14"></path>
                </svg>
                Export all
            </button>
        </div>
    </div>

    <div class="admin-dashboard-metric-grid">
        <div class="admin-metric-card">
            <div class="admin-metric-head">
                <h3>Total Providers</h3>
                <button type="button">⋮</button>
            </div>

            <div class="admin-metric-value">
                <strong>{{ $totalProviders }}</strong>
                <span class="positive">• {{ $activeProviders }} active</span>
            </div>

            <div class="admin-mini-chart">
                <span style="height: 20px"></span>
                <span style="height: 16px"></span>
                <span style="height: 24px"></span>
                <span style="height: 14px"></span>
                <span style="height: 19px"></span>
                <span class="active" style="height: 29px"></span>
            </div>

            <div class="admin-mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>

        <div class="admin-metric-card">
            <div class="admin-metric-head">
                <h3>Total Services</h3>
                <button type="button">⋮</button>
            </div>

            <div class="admin-metric-value">
                <strong>{{ $totalServices }}</strong>
                <span class="positive">• {{ $activeServices }} active</span>
            </div>

            <div class="admin-mini-chart">
                <span style="height: 18px"></span>
                <span style="height: 20px"></span>
                <span style="height: 25px"></span>
                <span style="height: 17px"></span>
                <span style="height: 28px"></span>
                <span class="active" style="height: 22px"></span>
            </div>

            <div class="admin-mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>

        <div class="admin-metric-card">
            <div class="admin-metric-head">
                <h3>Total Amount</h3>
                <button type="button">⋮</button>
            </div>

            <div class="admin-metric-value">
                <strong>${{ number_format((float) $totalAmount, 2) }}</strong>
                <span class="positive">• revenue</span>
            </div>

            <div class="admin-mini-chart">
                <span style="height: 18px"></span>
                <span style="height: 23px"></span>
                <span style="height: 17px"></span>
                <span style="height: 22px"></span>
                <span style="height: 30px"></span>
                <span class="active" style="height: 36px"></span>
            </div>

            <div class="admin-mini-months">
                <span>Jul</span><span>Aug</span><span>Sep</span><span>Oct</span><span>Nov</span><span>Dec</span>
            </div>
        </div>
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
                        Monthly
                    </button>

                    <button type="button" class="square">⋮</button>
                </div>
            </div>

            <div class="admin-revenue-chart-wrap">
                <svg class="admin-revenue-chart" viewBox="0 0 760 300" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="adminPurpleArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#a855f7" stop-opacity=".24"/>
                            <stop offset="100%" stop-color="#a855f7" stop-opacity="0"/>
                        </linearGradient>

                        <linearGradient id="adminOrangeArea" x1="0" y1="0" x2="0" y2="1">
                            <stop offset="0%" stop-color="#f97316" stop-opacity=".18"/>
                            <stop offset="100%" stop-color="#f97316" stop-opacity="0"/>
                        </linearGradient>
                    </defs>

                    <g class="chart-grid-lines">
                        <line x1="0" y1="52" x2="760" y2="52"></line>
                        <line x1="0" y1="108" x2="760" y2="108"></line>
                        <line x1="0" y1="164" x2="760" y2="164"></line>
                        <line x1="0" y1="220" x2="760" y2="220"></line>
                    </g>

                    <path class="area purple-area" d="M0 260 C80 230 130 195 175 160 C220 115 275 90 335 105 C395 120 430 155 485 135 C535 112 575 130 615 85 C650 42 705 60 740 230 L760 260 L0 260 Z"></path>
                    <path class="area orange-area" d="M0 285 C70 275 110 250 150 205 C190 155 260 125 330 130 C395 137 430 180 490 160 C540 140 575 160 610 145 C660 125 700 100 760 78 L760 260 L0 260 Z"></path>

                    <path class="line purple-line" d="M0 260 C80 230 130 195 175 160 C220 115 275 90 335 105 C395 120 430 155 485 135 C535 112 575 130 615 85 C650 42 705 60 740 230"></path>
                    <path class="line orange-line" d="M0 285 C70 275 110 250 150 205 C190 155 260 125 330 130 C395 137 430 180 490 160 C540 140 575 160 610 145 C660 125 700 100 760 78"></path>
                    <path class="line blue-line" d="M0 285 C100 270 120 255 160 230 C210 198 270 200 330 195 C390 192 430 225 495 215 C555 207 610 215 670 228 C720 240 750 220 760 215"></path>

                    <line class="chart-marker" x1="380" y1="52" x2="380" y2="260"></line>

                    <circle class="chart-point purple" cx="380" cy="115" r="5"></circle>
                    <circle class="chart-point orange" cx="380" cy="135" r="5"></circle>
                    <circle class="chart-point blue" cx="380" cy="195" r="5"></circle>
                </svg>

                <div class="admin-chart-tooltip">
                    <strong>July, 2026</strong>

                    <p>
                        <span class="dot purple"></span>
                        ${{ number_format((float) $completedAmount, 2) }}
                        <b>+8.5%</b>
                    </p>

                    <p>
                        <span class="dot orange"></span>
                        ${{ number_format((float) $pendingAmount, 2) }}
                        <b>+6.0%</b>
                    </p>

                    <p>
                        <span class="dot blue"></span>
                        {{ $totalBookings }} bookings
                        <b>12.0%</b>
                    </p>
                </div>
            </div>

            <div class="admin-chart-legend">
                <span><i class="purple"></i> Providers</span>
                <span><i class="orange"></i> Services</span>
                <span><i class="blue"></i> Bookings</span>
            </div>
        </div>

        <div class="admin-chart-card admin-status-card">
            <div class="admin-chart-head">
                <h3>Platform Status</h3>

                <div class="admin-chart-actions">
                    <button type="button">Documents</button>
                    <button type="button" class="square">⋮</button>
                </div>
            </div>

            <div class="admin-gauge-wrap">
                <div class="admin-gauge">
                    <span class="gauge-orange"></span>
                    <span class="gauge-purple"></span>
                    <span class="gauge-blue"></span>
                    <span class="gauge-green"></span>
                </div>

                <div class="admin-gauge-center">
                    <span>Total</span>
                    <strong>{{ $totalProviders + $totalServices + $totalBookings }}</strong>
                </div>
            </div>

            <div class="admin-status-list">
                <div>
                    <span><i class="orange"></i> Providers</span>
                    <strong>{{ $totalProviders }}</strong>
                    <em>{{ $totalProviders > 0 ? '100%' : '0%' }}</em>
                </div>

                <div>
                    <span><i class="purple"></i> Services</span>
                    <strong>{{ $totalServices }}</strong>
                    <em>{{ $totalServices > 0 ? '100%' : '0%' }}</em>
                </div>

                <div>
                    <span><i class="blue"></i> Bookings</span>
                    <strong>{{ $totalBookings }}</strong>
                    <em>{{ $totalBookings > 0 ? '100%' : '0%' }}</em>
                </div>

                <div>
                    <span><i class="green"></i> Amount</span>
                    <strong>${{ number_format((float) $totalAmount, 2) }}</strong>
                    <em>100%</em>
                </div>
            </div>
        </div>
    </div>

    <div class="admin-dashboard-table-card">
        <div class="admin-table-card-head">
            <h3>Recent platform data</h3>

            <div class="admin-table-tools">
                <label>
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>
                    <input type="text" placeholder="Search data">
                </label>

                <button type="button">Sort by</button>
                <button type="button">Filter</button>
            </div>
        </div>

        <div class="admin-table-responsive">
            <table>
                <thead>
                    <tr>
                        <th>Data name</th>
                        <th>Type</th>
                        <th>Total</th>
                        <th>Status</th>
                        <th>Updated</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>
                            <div class="admin-table-name">
                                <span>P</span>
                                <div>
                                    <strong>Providers</strong>
                                    <small>Registered providers</small>
                                </div>
                            </div>
                        </td>
                        <td>People</td>
                        <td>{{ $totalProviders }}</td>
                        <td><span class="table-status active">Active</span></td>
                        <td>{{ now()->format('d M Y') }}</td>
                    </tr>

                    <tr>
                        <td>
                            <div class="admin-table-name">
                                <span>S</span>
                                <div>
                                    <strong>Services</strong>
                                    <small>Available services</small>
                                </div>
                            </div>
                        </td>
                        <td>Business</td>
                        <td>{{ $totalServices }}</td>
                        <td><span class="table-status active">Active</span></td>
                        <td>{{ now()->format('d M Y') }}</td>
                    </tr>

                    <tr>
                        <td>
                            <div class="admin-table-name">
                                <span>B</span>
                                <div>
                                    <strong>Bookings</strong>
                                    <small>Customer orders</small>
                                </div>
                            </div>
                        </td>
                        <td>Order</td>
                        <td>{{ $totalBookings }}</td>
                        <td><span class="table-status pending">Monitoring</span></td>
                        <td>{{ now()->format('d M Y') }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>