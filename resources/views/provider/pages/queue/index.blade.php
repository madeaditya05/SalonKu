@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Antrian - JasaKu')
@section('page_title', 'Antrian')

@section('content')
@php
    $serviceNames = fn ($booking) => ($booking->services?->isNotEmpty() ? $booking->services->pluck('title')->join(', ') : ($booking->service->title ?? '-'));
@endphp

<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Antrian</h1>
            <p>Panggil customer, mulai layanan, dan pantau estimasi tunggu untuk queue serta walk-in.</p>
        </div>
        <form class="ops-actions" method="GET" action="{{ provider_route('provider.queue.index') }}">
            <input class="ops-button" type="date" name="date" value="{{ $date }}">
            <button class="ops-button dark" type="submit">Filter</button>
            <a class="ops-button" href="{{ provider_route('provider.walk-in.index') }}">Tambah Walk-in</a>
        </form>
    </div>

    @if (session('success'))
        <div class="ops-alert success">{{ session('success') }}</div>
    @endif

    <div class="ops-card">
        <div class="table-card-head">
            <h3>Antrian aktif</h3>
        </div>
        <div class="ops-table-wrap">
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Customer</th>
                        <th>Services</th>
                        <th>Staff</th>
                        <th>Estimasi</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($queueBookings as $booking)
                        <tr>
                            <td><span class="ops-chip info">#{{ $booking->queue_number }}</span></td>
                            <td>
                                <strong>{{ $booking->customer->name ?? $booking->customer_name ?? 'Walk-in' }}</strong>
                                <br><small>{{ $booking->branch->branch_name ?? '-' }}</small>
                            </td>
                            <td>{{ $serviceNames($booking) }}</td>
                            <td>{{ $booking->staff->full_name ?? 'Any Available' }}</td>
                            <td>{{ max(10, ($loop->iteration - 1) * (int) ($booking->total_duration ?: 30)) }} - {{ max(20, $loop->iteration * (int) ($booking->total_duration ?: 30)) }} menit</td>
                            <td><span class="ops-chip">{{ str_replace('_', ' ', $booking->status) }}</span></td>
                            <td>
                                <div class="ops-row-actions">
                                    @if ($booking->status === 'waiting')
                                        <form method="POST" action="{{ provider_route('provider.queue.call', $booking) }}">@csrf<button class="ops-button" type="submit">Panggil</button></form>
                                    @endif
                                    @if (in_array($booking->status, ['waiting', 'checked_in'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.start', $booking) }}">@csrf<button class="ops-button success" type="submit">Start</button></form>
                                    @endif
                                    @if (in_array($booking->status, ['in_progress', 'inprogress'], true))
                                        <form method="POST" action="{{ provider_route('provider.bookings.complete', $booking) }}">@csrf<button class="ops-button dark" type="submit">Complete</button></form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="ops-empty">Antrian masih kosong.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
