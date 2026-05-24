@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Booking Hari Ini - JasaKu')
@section('page_title', 'Booking Hari Ini')

@section('content')
@php
    $statusClass = fn ($status) => match ($status) {
        'completed', 'order_completed' => 'success',
        'waiting', 'confirmed', 'pending', 'pending_payment' => 'warn',
        'checked_in', 'in_progress', 'inprogress' => 'info',
        'cancelled', 'customer_cancelled', 'provider_cancelled', 'no_show' => 'danger',
        default => '',
    };

    $serviceNames = function ($booking) {
        $services = $booking->services ?? collect();

        if ($services->isNotEmpty()) {
            return $services->pluck('title')->join(', ');
        }

        return $booking->service->title ?? '-';
    };
@endphp

<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Booking Hari Ini</h1>
            <p>Kelola check-in, start service, complete, cancel, dan no-show dari satu meja kerja.</p>
        </div>

        <form method="GET" action="{{ provider_route('provider.bookings.index') }}" class="ops-actions">
            <input class="ops-button" type="date" name="date" value="{{ $date }}">
            <select class="ops-button" name="status">
                @foreach (['all' => 'Semua', 'confirmed' => 'Confirmed', 'waiting' => 'Waiting', 'checked_in' => 'Checked In', 'in_progress' => 'In Progress', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $key => $label)
                    <option value="{{ $key }}" @selected($status === $key)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="ops-button dark" type="submit">Filter</button>
            <a class="ops-button" href="{{ provider_route('provider.walk-in.index') }}">Tambah Walk-in</a>
        </form>
    </div>

    @if (session('success'))
        <div class="ops-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="ops-alert error">{{ session('error') }}</div>
    @endif

    <div class="ops-metrics">
        <div class="ops-metric"><span>Total</span><strong>{{ $stats['total'] }}</strong></div>
        <div class="ops-metric"><span>Waiting</span><strong>{{ $stats['waiting'] }}</strong></div>
        <div class="ops-metric"><span>In Progress</span><strong>{{ $stats['in_progress'] }}</strong></div>
        <div class="ops-metric"><span>Completed</span><strong>{{ $stats['completed'] }}</strong></div>
    </div>

    <div class="ops-card">
        <div class="table-card-head">
            <h3>Daftar booking {{ \Carbon\Carbon::parse($date)->format('d M Y') }}</h3>
        </div>

        <div class="ops-table-wrap">
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Jam</th>
                        <th>Customer</th>
                        <th>Services</th>
                        <th>Staff</th>
                        <th>Mode</th>
                        <th>Status</th>
                        <th>Payment</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($bookings as $booking)
                        <tr>
                            <td>
                                <strong>{{ $booking->start_time ? substr($booking->start_time, 0, 5) : ($booking->booking_time ? substr($booking->booking_time, 0, 5) : '-') }}</strong>
                                @if ($booking->queue_number)
                                    <br><span class="ops-chip info">#{{ $booking->queue_number }}</span>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $booking->customer->name ?? $booking->customer_name ?? 'Walk-in' }}</strong>
                                <br><small>{{ $booking->customer_phone ?? optional($booking->customer?->customerProfile)->phone_number }}</small>
                            </td>
                            <td>
                                {{ $serviceNames($booking) }}
                                <br><small>{{ $booking->total_duration ?: 0 }} menit</small>
                            </td>
                            <td>{{ $booking->staff->full_name ?? 'Any Available' }}</td>
                            <td><span class="ops-chip">{{ str_replace('_', ' ', $booking->booking_type ?? 'scheduled') }}</span></td>
                            <td><span class="ops-chip {{ $statusClass($booking->status) }}">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td>
                                <span class="ops-chip {{ $booking->payment_status === 'paid' ? 'success' : 'warn' }}">{{ str_replace('_', ' ', $booking->payment_status ?? 'unpaid') }}</span>
                                <br><small>{{ $booking->payment ? str_replace('_', ' ', $booking->payment->payment_type) : '-' }}</small>
                            </td>
                            <td>
                                <div class="ops-row-actions">
                                    @if (in_array($booking->status, ['confirmed', 'waiting'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.check-in', $booking) }}">@csrf<button class="ops-button" type="submit">Check-in</button></form>
                                    @endif
                                    @if (in_array($booking->status, ['confirmed', 'waiting', 'checked_in'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.start', $booking) }}">@csrf<button class="ops-button success" type="submit">Start</button></form>
                                    @endif
                                    @if (in_array($booking->status, ['in_progress', 'inprogress'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.complete', $booking) }}">@csrf<button class="ops-button dark" type="submit">Complete</button></form>
                                    @endif
                                    @if (! in_array($booking->status, ['completed', 'cancelled', 'no_show'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.cancel', $booking) }}">@csrf<button class="ops-button danger" type="submit">Cancel</button></form>
                                        <form method="POST" action="{{ provider_route('provider.bookings.no-show', $booking) }}">@csrf<button class="ops-button danger" type="submit">No-show</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8"><div class="ops-empty">Belum ada booking untuk tanggal ini.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
