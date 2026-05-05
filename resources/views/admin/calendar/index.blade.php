@extends('admin.layouts.app')

@section('content')
<section class="calendar-page">
    <div class="page-header">
        <h1>Calendar</h1>

        <div class="breadcrumb">
            Dashboard <span>/</span> Application <span>/</span> Calendar
        </div>
    </div>

    <div class="calendar-card">
        <div class="calendar-toolbar">
            <div class="calendar-left-actions">
                <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => $prevDate->format('Y-m-d')]) }}"
                   class="calendar-nav-btn">‹</a>

                <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => $nextDate->format('Y-m-d')]) }}"
                   class="calendar-nav-btn">›</a>

                <a href="{{ route('admin.calendar.index', ['view' => $view, 'date' => now()->format('Y-m-d')]) }}"
                   class="calendar-today-btn">Today</a>
            </div>

            <h2 class="calendar-title">{{ $calendarTitle }}</h2>

            <div class="calendar-view-actions">
                <a href="{{ route('admin.calendar.index', ['view' => 'month', 'date' => $baseDate->format('Y-m-d')]) }}"
                   class="calendar-view-btn {{ $view === 'month' ? 'active' : '' }}">
                    Month
                </a>

                <a href="{{ route('admin.calendar.index', ['view' => 'week', 'date' => $baseDate->format('Y-m-d')]) }}"
                   class="calendar-view-btn {{ $view === 'week' ? 'active' : '' }}">
                    Week
                </a>

                <a href="{{ route('admin.calendar.index', ['view' => 'day', 'date' => $baseDate->format('Y-m-d')]) }}"
                   class="calendar-view-btn {{ $view === 'day' ? 'active' : '' }}">
                    Day
                </a>
            </div>
        </div>

        {{-- MONTH VIEW --}}
        @if ($view === 'month')
            <div class="calendar-table-wrap">
                <table class="calendar-table">
                    <thead>
                        <tr>
                            <th>Sun</th>
                            <th>Mon</th>
                            <th>Tue</th>
                            <th>Wed</th>
                            <th>Thu</th>
                            <th>Fri</th>
                            <th>Sat</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($monthWeeks as $week)
                            <tr>
                                @foreach ($week as $day)
                                    @php
                                        $dateKey = $day['date']->format('Y-m-d');
                                        $dayBookings = $eventsByDate[$dateKey] ?? collect();
                                    @endphp

                                    <td class="calendar-day-cell {{ !$day['is_current_month'] ? 'calendar-day-muted' : '' }} {{ $day['is_today'] ? 'calendar-day-today' : '' }}">
                                        <div class="calendar-day-number">
                                            {{ $day['day'] }}
                                        </div>

                                        @if ($dayBookings->count() > 0)
                                            <div class="calendar-events">
                                                @foreach ($dayBookings->take(2) as $booking)
                                                    <a href="{{ route('admin.bookings.index', ['search' => $booking->booking_code]) }}"
                                                       class="calendar-event status-{{ $booking->status }}">
                                                        {{ $booking->booking_code }}
                                                    </a>
                                                @endforeach

                                                @if ($dayBookings->count() > 2)
                                                    <span class="calendar-more-events">
                                                        +{{ $dayBookings->count() - 2 }} more
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- WEEK VIEW --}}
        @if ($view === 'week')
            <div class="calendar-schedule-wrap">
                <table class="calendar-schedule-table">
                    <thead>
                        <tr>
                            <th class="time-col-head"></th>
                            @foreach ($weekDays as $day)
                                <th class="{{ $day['is_today'] ? 'schedule-head-today' : '' }}">
                                    {{ $day['label_full'] }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        <tr class="all-day-row">
                            <td class="time-label all-day-label">all-day</td>

                            @foreach ($weekDays as $day)
                                @php
                                    $dateKey = $day['date']->format('Y-m-d');
                                    $dayBookings = $eventsByDate[$dateKey] ?? collect();
                                @endphp

                                <td class="schedule-cell schedule-all-day-cell {{ $day['is_today'] ? 'schedule-today-col' : '' }}">
                                    @foreach ($dayBookings->take(3) as $booking)
                                        <a href="{{ route('admin.bookings.index', ['search' => $booking->booking_code]) }}"
                                           class="schedule-event status-{{ $booking->status }}">
                                            {{ $booking->booking_code }}
                                        </a>
                                    @endforeach
                                </td>
                            @endforeach
                        </tr>

                        @foreach ($timeSlots as $slot)
                            <tr>
                                <td class="time-label">{{ $slot }}</td>

                                @foreach ($weekDays as $day)
                                    <td class="schedule-cell {{ $day['is_today'] ? 'schedule-today-col' : '' }}"></td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        {{-- DAY VIEW --}}
        @if ($view === 'day')
            <div class="calendar-schedule-wrap">
                <table class="calendar-schedule-table day-view-table">
                    <thead>
                        <tr>
                            <th class="time-col-head"></th>
                            <th class="{{ $dayData['is_today'] ? 'schedule-head-today' : '' }}">
                                {{ $dayData['label'] }}
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @php
                            $dateKey = $dayData['date']->format('Y-m-d');
                            $dayBookings = $eventsByDate[$dateKey] ?? collect();
                        @endphp

                        <tr class="all-day-row">
                            <td class="time-label all-day-label">all-day</td>
                            <td class="schedule-cell schedule-all-day-cell {{ $dayData['is_today'] ? 'schedule-today-col' : '' }}">
                                @foreach ($dayBookings->take(5) as $booking)
                                    <a href="{{ route('admin.bookings.index', ['search' => $booking->booking_code]) }}"
                                       class="schedule-event status-{{ $booking->status }}">
                                        {{ $booking->booking_code }}
                                    </a>
                                @endforeach
                            </td>
                        </tr>

                        @foreach ($timeSlots as $slot)
                            <tr>
                                <td class="time-label">{{ $slot }}</td>
                                <td class="schedule-cell {{ $dayData['is_today'] ? 'schedule-today-col' : '' }}"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</section>
@endsection