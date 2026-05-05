@extends('admin.layouts.app')

@section('content')
<section class="coupon-page">
    <div class="page-header coupon-page-header">
        <div>
            <h1>Coupons</h1>

            <div class="breadcrumb">
                Dashboard <span>/</span> Application <span>/</span> Coupons
            </div>
        </div>

        <a href="{{ route('admin.coupons.create') }}" class="add-coupon-btn">
            <span>+</span>
            Add Coupon
        </a>
    </div>

    @if (session('success'))
        <div class="admin-alert success">
            {{ session('success') }}
        </div>
    @endif

    <div class="coupon-card">
        <div class="coupon-tabs">
            <a href="{{ route('admin.coupons.index', ['tab' => 'valid']) }}"
               class="coupon-tab {{ $tab === 'valid' ? 'active' : '' }}">
                Valid
            </a>

            <a href="{{ route('admin.coupons.index', ['tab' => 'expired']) }}"
               class="coupon-tab {{ $tab === 'expired' ? 'active' : '' }}">
                Expired
            </a>
        </div>

        <div class="coupon-toolbar">
            <div></div>

            <form method="GET" action="{{ route('admin.coupons.index') }}" class="search-box">
                <input type="hidden" name="tab" value="{{ $tab }}">

                <label>Search:</label>
                <input type="text" name="search" value="{{ $search ?? '' }}">
            </form>
        </div>

        <div class="table-responsive">
            <table class="coupon-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Coupon Code</th>
                        <th>Coupon Type</th>
                        <th>Coupon Value</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($coupons as $coupon)
                        <tr>
                            <td>{{ $coupons->firstItem() + $loop->index }}</td>

                            <td>
                                <strong>{{ $coupon->code }}</strong>
                            </td>

                            <td>{{ ucfirst($coupon->coupon_type) }}</td>

                            <td>
                                @if ($coupon->coupon_type === 'percentage')
                                    {{ number_format($coupon->coupon_value, 0) }}%
                                @else
                                    ${{ number_format($coupon->coupon_value, 2) }}
                                @endif
                            </td>

                            <td>
                                @if ($coupon->isExpired())
                                    <span class="coupon-status expired">Expired</span>
                                @elseif ($coupon->status === 'active')
                                    <span class="coupon-status active">Active</span>
                                @else
                                    <span class="coupon-status inactive">Inactive</span>
                                @endif
                            </td>

                            <td>
                                <div class="coupon-actions">
                                    <a href="{{ route('admin.coupons.edit', $coupon->id) }}"
                                       class="coupon-action-btn"
                                       title="Edit">
                                        ✎
                                    </a>

                                    <form action="{{ route('admin.coupons.destroy', $coupon->id) }}"
                                          method="POST"
                                          onsubmit="return confirm('Yakin ingin menghapus coupon ini?')">
                                        @csrf
                                        @method('DELETE')

                                        <button type="submit" class="coupon-action-btn" title="Delete">
                                            🗑
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty-state">
                                No coupons available
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($coupons->total() > 0)
            <div class="table-footer">
                <div class="table-info">
                    Showing {{ $coupons->firstItem() ?? 0 }} to {{ $coupons->lastItem() ?? 0 }} of {{ $coupons->total() }} entries
                </div>

                <div class="pagination-wrap coupon-pagination">
                    {{ $coupons->links() }}
                </div>
            </div>
        @endif
    </div>
</section>
@endsection