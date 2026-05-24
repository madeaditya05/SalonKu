@extends('admin.layouts.app')

@section('title', 'Create Coupon - JasaKu')
@section('page_title', 'Create Coupon')

@section('content')
<section class="admin-coupon-page">
    <form action="{{ route('admin.coupons.store') }}" method="POST" id="couponForm">
        @csrf

        <div class="admin-coupon-heading">
            <div>
                <h1>Create Coupon</h1>

                <div class="admin-coupon-breadcrumb">
                    <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                    <span>&rsaquo;</span>
                    <a href="{{ route('admin.coupons.index') }}">Coupons</a>
                    <span>&rsaquo;</span>
                    <strong>Create Coupon</strong>
                </div>
            </div>

            <button type="submit" class="admin-coupon-primary">
                Save
            </button>
        </div>

        @if ($errors->any())
            <div class="admin-alert error">
                {{ $errors->first() }}
            </div>
        @endif

        @include('admin.coupons.partials.form', [
            'coupon' => $coupon,
            'services' => $services,
            'categories' => $categories,
        ])
    </form>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/coupons.js') }}"></script>
@endpush
