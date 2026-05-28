@extends('provider.layouts.dashboard')

@section('title', 'Walk-in - JasaKu')
@section('page_title', 'Walk-in')
@section('page_subtitle', 'Input customer offline dan masukkan langsung ke antrian provider.')

@section('content')
@php
    use Illuminate\Support\Str;

    $branches = $branches ?? collect();
    $services = $services ?? collect();
    $staffs = $staffs ?? collect();

    $todayDate = now()->toDateString();
    $selectedBranchId = (string) old('branch_id', '');
    $selectedStaffId = (string) old('staff_id', '');
    $selectedServiceIds = collect(old('service_ids', []))
        ->map(fn ($id) => (string) $id)
        ->all();
    $selectedPaymentType = old('payment_type', 'pay_at_salon');

    $formatMoney = fn ($value) => 'Rp' . number_format((float) ($value ?? 0), 0, ',', '.');
    $formatDuration = fn ($value) => (int) ($value ?? 0) > 0 ? (int) $value . ' menit' : 'Durasi -';

    $paymentTypes = [
        'pay_at_salon' => 'Bayar di tempat',
        'dp' => 'Bayar DP',
        'full_payment' => 'Bayar penuh',
    ];
@endphp

<section class="admin-category-page admin-booking-page provider-booking-category-page provider-walkin-category-page">
    <div class="admin-booking-route admin-category-route provider-booking-route provider-walkin-route">
        <div class="admin-breadcrumb">
            <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <strong>Walk-in</strong>
        </div>

        <div class="provider-booking-category-actions provider-walkin-actions provider-walkin-actions-desktop">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Antrian
            </a>

            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Bookings Hari Ini
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.calendar.index', ['date' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M3 8h18"></path>
                    <path d="M8 3v3"></path>
                    <path d="M16 3v3"></path>
                    <path d="M5 6h14v15H5z"></path>
                </svg>
                Kalender
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-booking-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="admin-booking-alert danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="admin-booking-alert danger">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="admin-booking-summary-grid">
        <div class="admin-booking-summary-card pink">
            <span>Branch Aktif</span>
            <strong>{{ number_format($branches->count()) }}</strong>
            <small>Lokasi penerima walk-in</small>
        </div>

        <div class="admin-booking-summary-card yellow">
            <span>Service Tersedia</span>
            <strong>{{ number_format($services->count()) }}</strong>
            <small>Pilihan yang bisa masuk antrian</small>
        </div>

        <div class="admin-booking-summary-card blue">
            <span>Staff Aktif</span>
            <strong>{{ number_format($staffs->count()) }}</strong>
            <small>Bisa dipilih atau any available</small>
        </div>

        <div class="admin-booking-summary-card orange">
            <span>Service Dipilih</span>
            <strong>{{ number_format(count($selectedServiceIds)) }}</strong>
            <small>Terisi setelah validasi form</small>
        </div>
    </div>

    <div class="admin-booking-card category-card provider-booking-category-card provider-walkin-category-card">
        <div class="admin-booking-tabs provider-walkin-tabs">
            <a href="{{ provider_route('provider.walk-in.index') }}" class="admin-booking-tab active">
                Input Walk-in
            </a>
            <a href="{{ provider_route('provider.queue.index') }}" class="admin-booking-tab">
                Antrian Aktif
            </a>
            <a href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}" class="admin-booking-tab">
                Bookings
            </a>
            <a href="{{ provider_route('provider.calendar.index', ['date' => $todayDate]) }}" class="admin-booking-tab">
                Kalender Staff
            </a>
        </div>

        <div class="admin-category-add-row provider-booking-category-actions provider-walkin-actions provider-walkin-actions-mobile">
            <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h10"></path>
                </svg>
                Antrian
            </a>

            <a class="admin-category-add-button" href="{{ provider_route('provider.bookings.index', ['date_from' => $todayDate, 'date_to' => $todayDate]) }}">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M8 2v4"></path>
                    <path d="M16 2v4"></path>
                    <path d="M5 5h14v16H5z"></path>
                    <path d="M3 10h18"></path>
                </svg>
                Bookings
            </a>
        </div>

        <form class="provider-walkin-form" method="POST" action="{{ provider_route('provider.walk-in.store') }}">
            @csrf

            <fieldset class="provider-walkin-section">
                <legend>Customer</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Nama Customer <b>*</b></span>
                        <input name="customer_name" value="{{ old('customer_name') }}" autocomplete="name" required>
                        @error('customer_name') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Nomor HP</span>
                        <input name="customer_phone" value="{{ old('customer_phone') }}" inputmode="tel" autocomplete="tel">
                        @error('customer_phone') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section">
                <legend>Lokasi & Staff</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Branch <b>*</b></span>
                        <select name="branch_id" required>
                            <option value="">Pilih branch</option>
                            @forelse ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected($selectedBranchId === (string) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @empty
                                <option value="" disabled>Belum ada branch aktif</option>
                            @endforelse
                        </select>
                        @error('branch_id') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Staff</span>
                        <select name="staff_id">
                            <option value="">Any Available Staff</option>
                            @foreach ($staffs as $staff)
                                <option value="{{ $staff->id }}" @selected($selectedStaffId === (string) $staff->id)>
                                    {{ $staff->full_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('staff_id') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section provider-walkin-service-section">
                <legend>Service</legend>

                <div class="provider-walkin-section-head">
                    <div>
                        <strong>Pilih Service</strong>
                        <span>{{ number_format($services->count()) }} service tersedia</span>
                    </div>

                    <span class="admin-booking-status info">{{ number_format(count($selectedServiceIds)) }} dipilih</span>
                </div>

                @error('service_ids') <small class="provider-walkin-error">{{ $message }}</small> @enderror
                @error('service_ids.*') <small class="provider-walkin-error">{{ $message }}</small> @enderror

                <div class="provider-walkin-service-grid">
                    @forelse ($services as $service)
                        @php
                            $serviceId = (string) $service->id;
                            $isSelected = in_array($serviceId, $selectedServiceIds, true);
                            $categoryName = $service->serviceCategory->name ?? $service->category ?? 'Service';
                        @endphp

                        <label class="provider-walkin-service-card {{ $isSelected ? 'is-selected' : '' }}">
                            <input
                                type="checkbox"
                                name="service_ids[]"
                                value="{{ $service->id }}"
                                @checked($isSelected)
                            >

                            <span class="provider-walkin-check" aria-hidden="true">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M20 6 9 17l-5-5"></path>
                                </svg>
                            </span>

                            <span class="provider-walkin-service-copy">
                                <strong>{{ $service->title }}</strong>
                                <small>{{ Str::limit($categoryName, 36) }}</small>
                            </span>

                            <span class="provider-walkin-service-meta">
                                <b>{{ $formatMoney($service->price ?? 0) }}</b>
                                <small>{{ $formatDuration($service->estimated_duration ?? $service->duration ?? null) }}</small>
                            </span>
                        </label>
                    @empty
                        <div class="admin-category-mobile-empty admin-booking-mobile-empty provider-walkin-empty">
                            <strong>Belum ada service aktif.</strong>
                            <p>Tambahkan service terlebih dahulu sebelum membuat walk-in.</p>
                        </div>
                    @endforelse
                </div>
            </fieldset>

            <fieldset class="provider-walkin-section">
                <legend>Pembayaran</legend>

                <div class="provider-walkin-form-grid two">
                    <label class="provider-walkin-field">
                        <span>Payment Type</span>
                        <select name="payment_type">
                            @foreach ($paymentTypes as $key => $label)
                                <option value="{{ $key }}" @selected($selectedPaymentType === $key)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        @error('payment_type') <small>{{ $message }}</small> @enderror
                    </label>

                    <label class="provider-walkin-field">
                        <span>Catatan</span>
                        <textarea name="notes">{{ old('notes') }}</textarea>
                        @error('notes') <small>{{ $message }}</small> @enderror
                    </label>
                </div>
            </fieldset>

            <div class="provider-walkin-submit-bar">
                <a class="admin-category-add-button secondary" href="{{ provider_route('provider.queue.index') }}">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="m12 19-7-7 7-7"></path>
                        <path d="M19 12H5"></path>
                    </svg>
                    Kembali
                </a>

                <button class="admin-category-add-button" type="submit">
                    <svg viewBox="0 0 24 24" fill="none">
                        <path d="M12 5v14"></path>
                        <path d="M5 12h14"></path>
                    </svg>
                    Masukkan ke Antrian
                </button>
            </div>
        </form>
    </div>
</section>
@endsection
