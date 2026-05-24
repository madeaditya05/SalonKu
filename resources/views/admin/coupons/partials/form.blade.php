@php
    $selectedProductType = old('product_type', $coupon->product_type ?? 'all');

    $selectedProductIds = old('product_ids', $coupon->product_ids ?? []);
    $selectedProductIds = is_array($selectedProductIds) ? $selectedProductIds : [];

    $couponMasterData = [
        'service' => $services->map(function ($service) {
            return [
                'id' => $service->id,
                'label' => $service->title,
            ];
        })->values(),

        'category' => $categories->map(function ($category) {
            return [
                'id' => $category->id,
                'label' => $category->name,
            ];
        })->values(),
    ];
@endphp

<div class="coupon-form-card">
    <div class="coupon-form-title">
        <strong>Coupon Detail</strong>
        <span>Atur kode voucher, nilai diskon, cakupan layanan, dan masa aktif.</span>
    </div>

    <div class="coupon-form-grid">
        <div class="form-group">
            <label>Coupon Code <span>*</span></label>
            <input type="text" name="code" value="{{ old('code', $coupon->code) }}" placeholder="Contoh: SALONHEMAT" required>
        </div>

        <div class="form-group">
            <label>Product Type <span>*</span></label>
            <select name="product_type" id="productTypeSelect" required>
                <option value="all" {{ $selectedProductType === 'all' ? 'selected' : '' }}>All</option>
                <option value="service" {{ $selectedProductType === 'service' ? 'selected' : '' }}>Service</option>
                <option value="category" {{ $selectedProductType === 'category' ? 'selected' : '' }}>Category</option>
            </select>
        </div>

        <div class="form-group coupon-product-field"
             id="couponProductField"
            data-selected='@json(array_map("intval", $selectedProductIds))'>
            <label>
                <span id="couponProductLabel">Category</span>
                <span>*</span>
            </label>

            <div class="coupon-multiselect" id="couponProductSelector">
                <div class="coupon-multiselect-control" id="couponProductControl">
                    <div class="coupon-selected-tags" id="couponSelectedTags"></div>

                    <input type="text"
                           id="couponProductSearch"
                           placeholder="Select data"
                           autocomplete="off">
                </div>

                <div class="coupon-multiselect-dropdown" id="couponProductDropdown"></div>
            </div>

            <div id="couponProductHiddenInputs"></div>
        </div>

        <div class="form-group">
            <label>Coupon Type <span>*</span></label>
            <select name="coupon_type" id="couponTypeSelect" required>
                <option value="">Select Coupon Type</option>
                <option value="percentage" {{ old('coupon_type', $coupon->coupon_type) === 'percentage' ? 'selected' : '' }}>Percentage</option>
                <option value="fixed" {{ old('coupon_type', $coupon->coupon_type) === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
            </select>
        </div>

        <div class="form-group">
            <label>Coupon Value <span>*</span></label>

            <div class="input-with-suffix">
                <input type="number"
                       step="0.01"
                       name="coupon_value"
                       value="{{ old('coupon_value', $coupon->coupon_value) }}"
                       placeholder="Enter coupon value"
                       required>

                <span id="couponValueSuffix">%</span>
            </div>
        </div>

        <div class="form-group">
            <label>Quantity</label>
            <select name="quantity">
                <option value="">Unlimited</option>
                <option value="10" {{ old('quantity', $coupon->quantity) == 10 ? 'selected' : '' }}>10</option>
                <option value="25" {{ old('quantity', $coupon->quantity) == 25 ? 'selected' : '' }}>25</option>
                <option value="50" {{ old('quantity', $coupon->quantity) == 50 ? 'selected' : '' }}>50</option>
                <option value="100" {{ old('quantity', $coupon->quantity) == 100 ? 'selected' : '' }}>100</option>
                <option value="500" {{ old('quantity', $coupon->quantity) == 500 ? 'selected' : '' }}>500</option>
            </select>
        </div>

        <div class="form-group">
            <label>Start Date <span>*</span></label>
            <input type="date"
                   name="start_date"
                   value="{{ old('start_date', $coupon->start_date?->format('Y-m-d')) }}"
                   required>
        </div>

        <div class="form-group">
            <label>End Date <span>*</span></label>
            <input type="date"
                   name="end_date"
                   value="{{ old('end_date', $coupon->end_date?->format('Y-m-d')) }}"
                   required>
        </div>

        <div class="form-group">
            <label>Status <span>*</span></label>
            <select name="status" required>
                <option value="active" {{ old('status', $coupon->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ old('status', $coupon->status ?? 'active') === 'inactive' ? 'selected' : '' }}>Inactive</option>
            </select>
        </div>
    </div>
</div>

<script type="application/json" id="couponMasterData">
    @json($couponMasterData)
</script>
