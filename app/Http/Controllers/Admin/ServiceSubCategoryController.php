<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use App\Models\ServiceSubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceSubCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $search = $request->get('search');
        $categoryId = $request->get('category_id');
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $categories = ServiceCategory::orderBy('name')->get();

        $query = ServiceSubCategory::with('category')
            ->orderBy('id', 'asc');

        if (! empty($categoryId)) {
            $query->where('service_category_id', $categoryId);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%')
                    ->orWhereHas('category', function ($categoryQuery) use ($search) {
                        $categoryQuery->where('name', 'like', '%' . $search . '%');
                    });
            });
        }

        $subCategories = $query->paginate($perPage)->withQueryString();

        return view('admin.services.subcategories.index', compact(
            'subCategories',
            'categories',
            'search',
            'categoryId',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:service_sub_categories,slug'],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_featured' => ['required', Rule::in(['0', '1'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $slug = $validated['slug'] ?: Str::slug($validated['name']);

        $imagePath = null;
        $iconPath = null;

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('service-subcategories/images', 'public');
        }

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('service-subcategories/icons', 'public');
        }

        ServiceSubCategory::create([
            'service_category_id' => $validated['service_category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'image' => $imagePath,
            'icon' => $iconPath,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) $validated['is_featured'],
        ]);

        return back()->with('success', 'Sub category berhasil ditambahkan.');
    }

    public function update(Request $request, ServiceSubCategory $subCategory)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'service_category_id' => ['required', 'exists:service_categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('service_sub_categories', 'slug')->ignore($subCategory->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_featured' => ['required', Rule::in(['0', '1'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $imagePath = $subCategory->image;
        $iconPath = $subCategory->icon;

        if ($request->hasFile('image')) {
            $this->deleteLocalFile($subCategory->image);
            $imagePath = $request->file('image')->store('service-subcategories/images', 'public');
        }

        if ($request->hasFile('icon')) {
            $this->deleteLocalFile($subCategory->icon);
            $iconPath = $request->file('icon')->store('service-subcategories/icons', 'public');
        }

        $subCategory->update([
            'service_category_id' => $validated['service_category_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'image' => $imagePath,
            'icon' => $iconPath,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) $validated['is_featured'],
        ]);

        return back()->with('success', 'Sub category berhasil diperbarui.');
    }

    public function toggleFeatured(ServiceSubCategory $subCategory)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $subCategory->update([
            'is_featured' => ! $subCategory->is_featured,
        ]);

        return back()->with('success', 'Featured sub category berhasil diubah.');
    }

    public function toggleStatus(ServiceSubCategory $subCategory)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $subCategory->update([
            'status' => $subCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status sub category berhasil diubah.');
    }

    public function destroy(ServiceSubCategory $subCategory)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $this->deleteLocalFile($subCategory->image);
        $this->deleteLocalFile($subCategory->icon);

        $subCategory->delete();

        return back()->with('success', 'Sub category berhasil dihapus.');
    }

    private function deleteLocalFile(?string $path): void
    {
        if (! $path) {
            return;
        }

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}