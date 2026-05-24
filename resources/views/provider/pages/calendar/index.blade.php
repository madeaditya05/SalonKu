@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Kalender Staff - JasaKu')
@section('page_title', 'Kalender Staff')

@section('content')
@php
    $serviceNames = fn ($booking) => ($booking->services?->isNotEmpty() ? $booking->services->pluck('title')->join(', ') : ($booking->service->title ?? '-'));
@endphp

<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Kalender Staff</h1>
            <p>Lihat booking scheduled, queue, dan walk-in per staff untuk tanggal operasional.</p>
        </div>
        <form class="ops-actions" method="GET" action="{{ provider_route('provider.calendar.index') }}">
            <input class="ops-button" type="date" name="date" value="{{ $date }}">
            <button class="ops-button dark" type="submit">Lihat</button>
        </form>
    </div>

    <div class="ops-staff-grid">
        @foreach ($staffs as $staff)
            <article class="ops-staff-card">
                <h3>{{ $staff->full_name }}</h3>
                <p>{{ $staff->current_status ?? 'available' }}</p>
                @forelse ($staff->bookings as $booking)
                    <div class="ops-card" style="padding: 12px; margin-top: 10px; box-shadow: none;">
                        <span class="ops-chip {{ $booking->booking_type === 'scheduled' ? 'info' : 'warn' }}">{{ str_replace('_', ' ', $booking->booking_type) }}</span>
                        <h3 style="margin-top: 10px;">{{ $booking->start_time ? substr($booking->start_time, 0, 5) : ('#' . $booking->queue_number) }}</h3>
                        <p>{{ $booking->customer->name ?? $booking->customer_name ?? 'Walk-in' }}</p>
                        <small>{{ $serviceNames($booking) }}</small>
                    </div>
                @empty
                    <div class="ops-empty">Slot staff masih kosong.</div>
                @endforelse
            </article>
        @endforeach
    </div>

    @if ($unassignedQueue->isNotEmpty())
        <div class="ops-card">
            <div class="table-card-head"><h3>Antrian Any Staff</h3></div>
            <div class="ops-table-wrap">
                <table class="ops-table">
                    <thead><tr><th>No.</th><th>Customer</th><th>Services</th><th>Status</th></tr></thead>
                    <tbody>
                        @foreach ($unassignedQueue as $booking)
                            <tr>
                                <td>#{{ $booking->queue_number }}</td>
                                <td>{{ $booking->customer->name ?? $booking->customer_name ?? 'Walk-in' }}</td>
                                <td>{{ $serviceNames($booking) }}</td>
                                <td><span class="ops-chip">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</section>
@endsection
