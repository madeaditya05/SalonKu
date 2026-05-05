@extends('admin.layouts.app')

@section('content')
<section class="coupon-form-page">
    <form action="{{ route('admin.coupons.store') }}" method="POST" id="couponForm">
        @csrf

        <div class="page-header coupon-form-header">
            <div>
                <h1>Create Coupon</h1>

                <div class="breadcrumb">
                    Dashboard <span>/</span> Application <span>/</span> Create Coupon
                </div>
            </div>

            <button type="submit" class="coupon-save-btn">
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
            'subCategories' => $subCategories,
        ])
    </form>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/coupons.js') }}"></script>
@endpush