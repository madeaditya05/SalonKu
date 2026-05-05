<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ServiceCategoryController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);

        if (! in_array($perPage, [10, 25, 50, 100])) {
            $perPage = 10;
        }

        $query = ServiceCategory::query()->orderBy('id', 'asc');

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('slug', 'like', '%' . $search . '%')
                    ->orWhere('status', 'like', '%' . $search . '%');
            });
        }

        $categories = $query->paginate($perPage)->withQueryString();

        return view('admin.services.categories.index', compact(
            'categories',
            'search',
            'perPage'
        ));
    }

    public function store(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'unique:service_categories,slug'],
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
            $imagePath = $request->file('image')->store('service-categories/images', 'public');
        }

        if ($request->hasFile('icon')) {
            $iconPath = $request->file('icon')->store('service-categories/icons', 'public');
        }

        ServiceCategory::create([
            'name' => $validated['name'],
            'slug' => $slug,
            'image' => $imagePath,
            'icon' => $iconPath,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) $validated['is_featured'],
        ]);

        return back()->with('success', 'Category berhasil ditambahkan.');
    }

    public function update(Request $request, ServiceCategory $category)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('service_categories', 'slug')->ignore($category->id),
            ],
            'description' => ['nullable', 'string'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'is_featured' => ['required', Rule::in(['0', '1'])],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,svg', 'max:2048'],
        ]);

        $imagePath = $category->image;
        $iconPath = $category->icon;

        if ($request->hasFile('image')) {
            $this->deleteLocalFile($category->image);
            $imagePath = $request->file('image')->store('service-categories/images', 'public');
        }

        if ($request->hasFile('icon')) {
            $this->deleteLocalFile($category->icon);
            $iconPath = $request->file('icon')->store('service-categories/icons', 'public');
        }

        $category->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?: Str::slug($validated['name']),
            'image' => $imagePath,
            'icon' => $iconPath,
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'is_featured' => (bool) $validated['is_featured'],
        ]);

        return back()->with('success', 'Category berhasil diperbarui.');
    }

    public function toggleFeatured(ServiceCategory $category)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $category->update([
            'is_featured' => ! $category->is_featured,
        ]);

        return back()->with('success', 'Featured category berhasil diubah.');
    }

    public function toggleStatus(ServiceCategory $category)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $category->update([
            'status' => $category->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status category berhasil diubah.');
    }

    public function destroy(ServiceCategory $category)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $this->deleteLocalFile($category->image);
        $this->deleteLocalFile($category->icon);

        $category->delete();

        return back()->with('success', 'Category berhasil dihapus.');
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