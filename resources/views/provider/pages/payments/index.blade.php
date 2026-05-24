@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Pembayaran - JasaKu')
@section('page_title', 'Pembayaran')

@section('content')
<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Pembayaran</h1>
            <p>Payment simulasi MVP untuk DP, full payment, dan bayar di tempat.</p>
        </div>
    </div>

    <div class="ops-card">
        <div class="ops-table-wrap">
            <table class="ops-table">
                <thead>
                    <tr>
                        <th>Booking</th>
                        <th>Customer</th>
                        <th>Branch</th>
                        <th>Services</th>
                        <th>Type</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payments as $payment)
                        @php
                            $booking = $payment->booking;
                            $services = $booking?->services?->pluck('title')->join(', ') ?: ($booking?->service->title ?? '-');
                        @endphp
                        <tr>
                            <td><strong>{{ $booking->booking_code ?? '-' }}</strong></td>
                            <td>{{ $booking->customer->name ?? $booking->customer_name ?? 'Walk-in' }}</td>
                            <td>{{ $booking->branch->branch_name ?? '-' }}</td>
                            <td>{{ $services }}</td>
                            <td><span class="ops-chip">{{ str_replace('_', ' ', $payment->payment_type) }}</span></td>
                            <td>Rp{{ number_format((float) $payment->amount, 0, ',', '.') }}</td>
                            <td><span class="ops-chip {{ $payment->status === 'paid' ? 'success' : 'warn' }}">{{ $payment->status }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="7"><div class="ops-empty">Belum ada pembayaran.</div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div style="margin-top: 12px;">
            {{ $payments->links() }}
        </div>
    </div>
</section>
@endsection
