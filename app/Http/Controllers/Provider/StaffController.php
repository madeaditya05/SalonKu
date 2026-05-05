<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderBranch;
use App\Models\ProviderStaff;
use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class StaffController extends Controller
{
    private function providerId(): int
    {
        return (int) (Auth::user()->provider_id ?? Auth::id());
    }

    public function index(Request $request)
    {
        $providerId = $this->providerId();

        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100], true)) {
            $perPage = 10;
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Category dari table service_categories
        |--------------------------------------------------------------------------
        */

        $categories = ServiceCategory::query()
            ->where('status', 'active')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Ambil Sub Category dari table service_sub_categories
        |--------------------------------------------------------------------------
        */

        $subCategories = ServiceSubCategory::query()
            ->where('status', 'active')
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Ambil Branch dari table provider_branches berdasarkan provider login
        |--------------------------------------------------------------------------
        */

        $branches = ProviderBranch::query()
            ->where('provider_id', $providerId)
            ->where('status', 'active')
            ->orderBy('branch_name')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | Ambil Staff
        |--------------------------------------------------------------------------
        */

        $staffs = ProviderStaff::query()
            ->where('provider_id', $providerId)
            ->with([
                'branch:id,provider_id,branch_name,status',
                'category:id,name,status',
                'subCategory:id,service_category_id,name,status',
            ])
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('username', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhereHas('branch', function ($branchQuery) use ($search) {
                            $branchQuery->where('branch_name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('category', function ($categoryQuery) use ($search) {
                            $categoryQuery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('subCategory', function ($subCategoryQuery) use ($search) {
                            $subCategoryQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('provider.page.staffs.index', compact(
            'staffs',
            'search',
            'perPage',
            'categories',
            'subCategories',
            'branches'
        ));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedStaffData($request);

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('provider/staffs', 'public');
        }

        $validated['provider_id'] = $this->providerId();
        $validated['role'] = 'staff';

        ProviderStaff::create($validated);

        return redirect()
            ->route('provider.staffs.index')
            ->with('success', 'Staff berhasil ditambahkan.');
    }

    public function update(Request $request, ProviderStaff $staff)
    {
        $this->authorizeProviderStaff($staff);

        $validated = $this->validatedStaffData($request, $staff);

        if ($request->hasFile('image')) {
            if ($staff->image && Storage::disk('public')->exists($staff->image)) {
                Storage::disk('public')->delete($staff->image);
            }

            $validated['image'] = $request->file('image')->store('provider/staffs', 'public');
        }

        $validated['role'] = 'staff';

        $staff->update($validated);

        return redirect()
            ->route('provider.staffs.index')
            ->with('success', 'Staff berhasil diperbarui.');
    }

    public function destroy(ProviderStaff $staff)
    {
        $this->authorizeProviderStaff($staff);

        if ($staff->image && Storage::disk('public')->exists($staff->image)) {
            Storage::disk('public')->delete($staff->image);
        }

        $staff->delete();

        return redirect()
            ->route('provider.staffs.index')
            ->with('success', 'Staff berhasil dihapus.');
    }

    private function validatedStaffData(Request $request, ?ProviderStaff $staff = null): array
    {
        $providerId = $this->providerId();

        return $request->validate([
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],

            'first_name' => [
                'required',
                'string',
                'max:255',
            ],

            'last_name' => [
                'required',
                'string',
                'max:255',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('provider_staffs', 'email')
                    ->where(fn ($query) => $query->where('provider_id', $providerId))
                    ->ignore($staff?->id),
            ],

            'username' => [
                'nullable',
                'string',
                'max:255',
            ],

            'country_code' => [
                'nullable',
                'string',
                'max:20',
            ],

            'phone_number' => [
                'nullable',
                'string',
                'max:255',
            ],

            'gender' => [
                'nullable',
                Rule::in(['male', 'female', 'other']),
            ],

            'date_of_birth' => [
                'nullable',
                'date',
            ],

            'address' => [
                'nullable',
                'string',
            ],

            'country_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'state_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'city_id' => [
                'nullable',
                'string',
                'max:255',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:255',
            ],

            'bio' => [
                'nullable',
                'string',
            ],

            /*
            |--------------------------------------------------------------------------
            | Category dari table service_categories
            |--------------------------------------------------------------------------
            */

            'category_id' => [
                'required',
                Rule::exists('service_categories', 'id')
                    ->where(fn ($query) => $query->where('status', 'active')),
            ],

            /*
            |--------------------------------------------------------------------------
            | Sub Category dari table service_sub_categories
            | Harus sesuai dengan category_id yang dipilih
            |--------------------------------------------------------------------------
            */

            'sub_category_id' => [
                'required',
                Rule::exists('service_sub_categories', 'id')
                    ->where(function ($query) use ($request) {
                        $query->where('status', 'active');

                        if ($request->filled('category_id')) {
                            $query->where('service_category_id', $request->category_id);
                        }
                    }),
            ],

            /*
            |--------------------------------------------------------------------------
            | Branch dari table provider_branches
            | Hanya branch milik provider yang sedang login
            |--------------------------------------------------------------------------
            */

            'branch_id' => [
                'required',
                Rule::exists('provider_branches', 'id')
                    ->where(function ($query) use ($providerId) {
                        $query->where('provider_id', $providerId)
                            ->where('status', 'active');
                    }),
            ],

            'status' => [
                'nullable',
                Rule::in(['active', 'inactive']),
            ],
        ], [
            'first_name.required' => 'First name wajib diisi.',
            'last_name.required' => 'Last name wajib diisi.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email staff sudah digunakan.',
            'category_id.required' => 'Category wajib dipilih.',
            'category_id.exists' => 'Category tidak valid.',
            'sub_category_id.required' => 'Sub category wajib dipilih.',
            'sub_category_id.exists' => 'Sub category tidak valid atau tidak sesuai dengan category.',
            'branch_id.required' => 'Branch wajib dipilih.',
            'branch_id.exists' => 'Branch tidak valid atau bukan milik provider ini.',
        ]);
    }

    private function authorizeProviderStaff(ProviderStaff $staff): void
    {
        if ((int) $staff->provider_id !== $this->providerId()) {
            abort(403, 'Akses ditolak.');
        }
    }
}