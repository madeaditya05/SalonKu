@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Walk-in - JasaKu')
@section('page_title', 'Walk-in')

@section('content')
<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Input Walk-in</h1>
            <p>Customer offline masuk ke tabel booking yang sama, sehingga antrian dan jadwal tetap sinkron.</p>
        </div>
        <a class="ops-button" href="{{ provider_route('provider.queue.index') }}">Lihat Antrian</a>
    </div>

    @if ($errors->any())
        <div class="ops-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="ops-card">
        <form class="ops-form" method="POST" action="{{ provider_route('provider.walk-in.store') }}">
            @csrf

            <div class="ops-grid">
                <label class="ops-field">
                    Nama Customer
                    <input name="customer_name" value="{{ old('customer_name') }}" required>
                </label>
                <label class="ops-field">
                    Nomor HP
                    <input name="customer_phone" value="{{ old('customer_phone') }}">
                </label>
                <label class="ops-field">
                    Branch
                    <select name="branch_id" required>
                        <option value="">Pilih branch</option>
                        @foreach ($branches as $branch)
                            <option value="{{ $branch->id }}" @selected((int) old('branch_id') === $branch->id)>{{ $branch->branch_name }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="ops-field">
                    Staff
                    <select name="staff_id">
                        <option value="">Any Available Staff</option>
                        @foreach ($staffs as $staff)
                            <option value="{{ $staff->id }}" @selected((int) old('staff_id') === $staff->id)>{{ $staff->full_name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="ops-field">
                Pilih Service
                <div class="ops-check-grid">
                    @foreach ($services as $service)
                        <label class="ops-check">
                            <input type="checkbox" name="service_ids[]" value="{{ $service->id }}" @checked(in_array($service->id, old('service_ids', [])))>
                            <span>{{ $service->title }} - Rp{{ number_format((float) $service->price, 0, ',', '.') }}</span>
                        </label>
                    @endforeach
                </div>
            </label>

            <div class="ops-grid">
                <label class="ops-field">
                    Payment Type
                    <select name="payment_type">
                        <option value="pay_at_salon">Bayar di tempat</option>
                        <option value="dp">Bayar DP</option>
                        <option value="full_payment">Bayar penuh</option>
                    </select>
                </label>
                <label class="ops-field">
                    Catatan
                    <textarea name="notes">{{ old('notes') }}</textarea>
                </label>
            </div>

            <div class="ops-actions">
                <button class="ops-button dark" type="submit">Masukkan ke Antrian</button>
            </div>
        </form>
    </div>
</section>
@endsection
