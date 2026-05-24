@extends('admin.layouts.app')

@section('title', 'Coupons - JasaKu')
@section('page_title', 'Coupons')

@section('content')
@php
    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');

    $statusClass = function ($coupon) {
        if ($coupon->isExpired()) {
            return 'expired';
        }

        return $coupon->status === 'active' ? 'active' : 'inactive';
    };

    $statusLabel = function ($coupon) {
        if ($coupon->isExpired()) {
            return 'Expired';
        }

        return ucfirst($coupon->status ?: 'inactive');
    };

    $couponValue = function ($coupon) {
        return $coupon->coupon_type === 'percentage'
            ? number_format((float) $coupon->coupon_value, 0) . '%'
            : 'Rp ' . number_format((float) $coupon->coupon_value, 0, ',', '.');
    };
@endphp

<section class="admin-coupon-page">
    <div class="admin-coupon-heading">
        <div>
            <h1>Coupons</h1>

            <div class="admin-coupon-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>&rsaquo;</span>
                <strong>Coupons</strong>
            </div>
        </div>

        <a href="{{ route('admin.coupons.create') }}" class="admin-coupon-primary">
            <svg viewBox="0 0 24 24">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Add Coupon
        </a>
    </div>

    @if (session('success'))
        <div class="admin-coupon-alert success">
            {{ session('success') }}
        </div>
    @endif

    <div class="admin-coupon-card">
        <div class="admin-coupon-tabs">
            <a href="{{ route('admin.coupons.index', ['tab' => 'valid', 'per_page' => $perPage, 'search' => $search]) }}"
               class="{{ $tab === 'valid' ? 'active' : '' }}">
                Valid
            </a>

            <a href="{{ route('admin.coupons.index', ['tab' => 'expired', 'per_page' => $perPage, 'search' => $search]) }}"
               class="{{ $tab === 'expired' ? 'active' : '' }}">
                Expired
            </a>
        </div>

        <div class="admin-coupon-toolbar">
            <form method="GET" action="{{ route('admin.coupons.index') }}" class="admin-coupon-entries">
                <input type="hidden" name="tab" value="{{ $tab }}">

                @if ($search !== '')
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                <span>Show</span>

                <select name="per_page" onchange="this.form.submit()">
                    <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (int) $perPage === 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ (int) $perPage === 100 ? 'selected' : '' }}>100</option>
                </select>

                <span>entries</span>
            </form>

            <form method="GET" action="{{ route('admin.coupons.index') }}" class="admin-coupon-search">
                <input type="hidden" name="tab" value="{{ $tab }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <label for="couponSearchInput">Search:</label>

                <div class="admin-coupon-search-box">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>

                    <input id="couponSearchInput"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search coupon">
                </div>
            </form>
        </div>

        <div class="admin-coupon-table-wrap">
            <table class="admin-coupon-table">
                <thead>
                    <tr>
                        <th># <span>&varr;</span></th>
                        <th>Coupon Code <span>&varr;</span></th>
                        <th>Scope <span>&varr;</span></th>
                        <th>Type <span>&varr;</span></th>
                        <th>Value <span>&varr;</span></th>
                        <th>Usage <span>&varr;</span></th>
                        <th>Valid Until <span>&varr;</span></th>
                        <th>Status <span>&varr;</span></th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td>{{ $loop->iteration + ($coupons->firstItem() - 1) }}</td>

                            <td>
                                <div class="admin-coupon-code">
                                    <strong>{{ $coupon->code }}</strong>
                                    <small>Created {{ $coupon->created_at?->format('d M Y') }}</small>
                                </div>
                            </td>

                            <td>
                                <span class="admin-coupon-scope">
                                    {{ ucfirst($coupon->product_type ?: 'all') }}
                                </span>
                            </td>

                            <td>{{ ucfirst($coupon->coupon_type) }}</td>

                            <td>
                                <strong class="admin-coupon-value">{{ $couponValue($coupon) }}</strong>
                            </td>

                            <td>
                                {{ (int) $coupon->used_count }} / {{ $coupon->quantity ?? 'Unlimited' }}
                            </td>

                            <td>
                                {{ $coupon->end_date?->format('d M Y') }}
                            </td>

                            <td>
                                <span class="admin-coupon-badge {{ $statusClass($coupon) }}">
                                    <i></i>
                                    {{ $statusLabel($coupon) }}
                                </span>
                            </td>

                            <td>
                                <div class="admin-coupon-actions">
                                    <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="admin-coupon-action" title="Edit">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"></path>
                                            <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"></path>
                                        </svg>
                                    </a>

                                    <form action="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus coupon ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="admin-coupon-action danger" title="Delete">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18"></path>
                                                <path d="M8 6V4h8v2"></path>
                                                <path d="M6 6l1 15h10l1-15"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-coupon-empty">
                                <div>
                                    <span>
                                        <svg viewBox="0 0 24 24">
                                            <path d="M4 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 0 0 4v3a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2v-3a2 2 0 0 0 0-4V7z"></path>
                                        </svg>
                                    </span>

                                    <strong>Belum ada coupon.</strong>
                                    <p>Coupon promo yang kamu buat akan tampil di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-coupon-footer">
            <p>
                Showing {{ $coupons->firstItem() ?? 0 }} to {{ $coupons->lastItem() ?? 0 }} of {{ $coupons->total() }} entries
            </p>

            <div class="admin-coupon-pagination">
                {{ $coupons->links() }}
            </div>
        </div>
    </div>
</section>
@endsection
