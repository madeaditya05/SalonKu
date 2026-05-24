<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Coupon;
use App\Models\Service;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $tab = $request->get('tab', 'valid');
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Coupon::query()->latest();

        if ($tab === 'expired') {
            $query->whereDate('end_date', '<', now()->toDateString());
        } else {
            $query->where('status', 'active')
                ->whereDate('end_date', '>=', now()->toDateString());
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('product_type', 'like', '%' . $search . '%')
                    ->orWhere('coupon_type', 'like', '%' . $search . '%')
                    ->orWhere('coupon_value', 'like', '%' . $search . '%');
            });
        }

        $coupons = $query->paginate($perPage)->withQueryString();

        return view('admin.coupons.index', compact(
            'coupons',
            'tab',
            'search',
            'perPage'
        ));
    }

    public function create()
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        return view('admin.coupons.create', array_merge([
            'coupon' => new Coupon(),
            'mode' => 'create',
        ], $this->masterData()));
    }

    public function store(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $this->validateCoupon($request);

        Coupon::create($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon berhasil ditambahkan.');
    }

    public function edit(Coupon $coupon)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        return view('admin.coupons.edit', array_merge([
            'coupon' => $coupon,
            'mode' => 'edit',
        ], $this->masterData()));
    }

    public function update(Request $request, Coupon $coupon)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $this->validateCoupon($request, $coupon->id);

        $coupon->update($validated);

        return redirect()
            ->route('admin.coupons.index')
            ->with('success', 'Coupon berhasil diperbarui.');
    }

    public function destroy(Coupon $coupon)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $coupon->delete();

        return back()->with('success', 'Coupon berhasil dihapus.');
    }

    private function validateCoupon(Request $request, ?int $couponId = null): array
    {
        $validated = $request->validate([
            'code' => [
                'required',
                'string',
                'max:100',
                Rule::unique('coupons', 'code')->ignore($couponId),
            ],
            'product_type' => ['required', Rule::in(['all', 'service', 'category'])],
            'product_ids' => ['nullable', 'array'],
            'product_ids.*' => ['integer'],
            'coupon_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'coupon_value' => ['required', 'numeric', 'min:0'],
            'quantity' => ['nullable', 'integer', 'min:1'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'status' => ['nullable', Rule::in(['active', 'inactive'])],
        ], [
            'code.required' => 'Code wajib diisi.',
            'code.unique' => 'Code coupon sudah digunakan.',
            'product_type.required' => 'Product type wajib dipilih.',
            'coupon_type.required' => 'Coupon type wajib dipilih.',
            'coupon_value.required' => 'Coupon value wajib diisi.',
            'start_date.required' => 'Start date wajib diisi.',
            'end_date.required' => 'End date wajib diisi.',
            'end_date.after_or_equal' => 'End date harus sama atau setelah start date.',
        ]);

        $productType = $validated['product_type'];
        $productIds = $validated['product_ids'] ?? [];

        if ($productType === 'all') {
            $validated['product_ids'] = null;
        } else {
            if (empty($productIds)) {
                throw ValidationException::withMessages([
                    'product_ids' => ucfirst($productType) . ' wajib dipilih.',
                ]);
            }

            $this->validateProductIds($productType, $productIds);

            $validated['product_ids'] = array_values(array_unique($productIds));
        }

        $validated['status'] = $request->get('status', 'active');

        return $validated;
    }

    private function validateProductIds(string $productType, array $productIds): void
    {
        $count = 0;

        if ($productType === 'service') {
            $count = Service::whereIn('id', $productIds)->count();
        }

        if ($productType === 'category') {
            $count = ServiceCategory::whereIn('id', $productIds)->count();
        }

        if ($count !== count(array_unique($productIds))) {
            throw ValidationException::withMessages([
                'product_ids' => 'Data master yang dipilih tidak valid.',
            ]);
        }
    }

    private function masterData(): array
    {
        return [
            'services' => Service::query()
                ->orderBy('title')
                ->get(['id', 'title']),

            'categories' => ServiceCategory::query()
                ->orderBy('name')
                ->get(['id', 'name']),
        ];
    }
}
