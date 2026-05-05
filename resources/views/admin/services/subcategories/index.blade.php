@extends('admin.layouts.app')

@section('title', 'Sub Category - JasaKu')
@section('page_title', 'Sub Category')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/pages/service-subcategories.css') }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');
    $categoryId = request('category_id', $categoryId ?? '');

    $assetUrl = function ($path) {
        if (!$path) {
            return null;
        }

        $path = ltrim($path, '/');

        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        if (Str::startsWith($path, 'storage/')) {
            return asset($path);
        }

        return asset('storage/' . $path);
    };
@endphp

<section class="subcategory-page">
    <div class="subcategory-page-header">
        <div>
            <h1>Sub Category</h1>

            <div class="subcategory-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>›</span>
                <a href="{{ route('admin.services.index') }}">Service</a>
                <span>›</span>
                <strong>Sub Category</strong>
            </div>
        </div>

        <div class="subcategory-header-actions">
            <form method="GET" action="{{ route('admin.service-subcategories.index') }}">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                <select name="category_id" class="filter-select" onchange="this.form.submit()">
                    <option value="">All Category</option>

                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" {{ (string) $categoryId === (string) $category->id ? 'selected' : '' }}>
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </form>

            <button type="button" class="admin-dark-btn" data-modal-open="addSubCategoryModal">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M12 5V19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Add Sub Category
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="admin-alert error">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="admin-alert error">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="subcategory-card">
        <div class="subcategory-toolbar">
            <form method="GET" action="{{ route('admin.service-subcategories.index') }}" class="entries-box">
                <span>Show</span>

                <select name="per_page" onchange="this.form.submit()">
                    <option value="10" {{ (int) $perPage === 10 ? 'selected' : '' }}>10</option>
                    <option value="25" {{ (int) $perPage === 25 ? 'selected' : '' }}>25</option>
                    <option value="50" {{ (int) $perPage === 50 ? 'selected' : '' }}>50</option>
                    <option value="100" {{ (int) $perPage === 100 ? 'selected' : '' }}>100</option>
                </select>

                <span>entries</span>

                @if ($search)
                    <input type="hidden" name="search" value="{{ $search }}">
                @endif

                @if ($categoryId)
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif
            </form>

            <form method="GET" action="{{ route('admin.service-subcategories.index') }}" class="search-box">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                @if ($categoryId)
                    <input type="hidden" name="category_id" value="{{ $categoryId }}">
                @endif

                <div class="subcategory-search-input">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search sub category"
                    >
                </div>
            </form>
        </div>

        <div class="subcategory-table-wrap">
            <table class="subcategory-table">
                <thead>
                    <tr>
                        <th class="number-column"># <span class="sort-icon">↕</span></th>
                        <th>Sub Category <span class="sort-icon">↕</span></th>
                        <th>Slug <span class="sort-icon">↕</span></th>
                        <th>Category <span class="sort-icon">↕</span></th>
                        <th>Status <span class="sort-icon">↕</span></th>
                        <th>Featured <span class="sort-icon">↕</span></th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($subCategories as $subCategory)
                        @php
                            $imageUrl = $assetUrl($subCategory->image);
                            $iconUrl = $assetUrl($subCategory->icon);
                            $status = $subCategory->status ?? 'inactive';
                            $isFeatured = (bool) $subCategory->is_featured;
                        @endphp

                        <tr>
                            <td>
                                {{ $subCategories->firstItem() + $loop->index }}
                            </td>

                            <td>
                                <div class="subcategory-name-box">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $subCategory->name }}" class="subcategory-thumb">
                                    @else
                                        <div class="subcategory-thumb-placeholder">
                                            {{ strtoupper(substr($subCategory->name ?? 'S', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="subcategory-name-text">
                                        <strong title="{{ $subCategory->name }}">
                                            {{ $subCategory->name }}
                                        </strong>

                                        @if ($subCategory->description)
                                            <small>{{ Str::limit(strip_tags($subCategory->description), 42) }}</small>
                                        @else
                                            <small>Service sub category</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>{{ $subCategory->slug }}</td>

                            <td>
                                {{ $subCategory->category->name ?? '-' }}
                            </td>

                            <td>
                                <form
                                    action="{{ route('admin.service-subcategories.toggle-status', $subCategory->id) }}"
                                    method="POST"
                                    class="inline-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="status-pill status-{{ $status }}"
                                        title="Click to change status"
                                    >
                                        <span></span>
                                        {{ ucfirst($status) }}
                                    </button>
                                </form>
                            </td>

                            <td>
                                <form
                                    action="{{ route('admin.service-subcategories.toggle-featured', $subCategory->id) }}"
                                    method="POST"
                                    class="toggle-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="feature-switch {{ $isFeatured ? 'is-on' : '' }}"
                                        aria-label="Toggle featured"
                                        title="{{ $isFeatured ? 'Featured aktif' : 'Featured nonaktif' }}"
                                    >
                                        <span></span>
                                    </button>
                                </form>
                            </td>

                            <td>
                                <div class="subcategory-actions">
                                    <button
                                        type="button"
                                        class="subcategory-action-btn"
                                        title="Edit"
                                        data-modal-open="editSubCategoryModal{{ $subCategory->id }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M4 20H8L18 10L14 6L4 16V20Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M13 7L17 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="subcategory-action-btn danger"
                                        title="Delete"
                                        data-modal-open="deleteSubCategoryModal{{ $subCategory->id }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M5 7H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M8 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M12 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M16 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M7 7L8 21H16L17 7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                Belum ada data sub category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-info">
                Showing {{ $subCategories->firstItem() ?? 0 }} to {{ $subCategories->lastItem() ?? 0 }} of {{ $subCategories->total() }} entries
            </div>

            <div class="pagination-wrap subcategory-pagination">
                {{ $subCategories->links() }}
            </div>
        </div>
    </div>
</section>

<div class="subcategory-modal" id="addSubCategoryModal">
    <div class="subcategory-modal-dialog large">
        <div class="subcategory-modal-header">
            <div>
                <h3>Add Sub Category</h3>
                <p>Tambah data sub category service baru.</p>
            </div>

            <button type="button" class="modal-close" data-modal-close>
                ×
            </button>
        </div>

        <form action="{{ route('admin.service-subcategories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="subcategory-modal-body">
                <div class="form-grid two">
                    <div class="form-group">
                        <label>Category <span>*</span></label>

                        <select name="service_category_id" required>
                            <option value="">Select Category</option>

                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}" {{ old('service_category_id') == $category->id ? 'selected' : '' }}>
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Sub Category Name <span>*</span></label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Enter Sub Category Name"
                            required
                            data-slug-source
                        >
                    </div>
                </div>

                <div class="form-group">
                    <label>Slug</label>
                    <input
                        type="text"
                        name="slug"
                        value="{{ old('slug') }}"
                        placeholder="Kosongkan untuk otomatis"
                        data-slug-target
                    >
                </div>

                <div class="form-grid two">
                    <div class="form-group">
                        <label>Image</label>

                        <label class="upload-box upload-field">
                            <input type="file" name="image" accept="image/*,.svg" data-file-input>
                            <img src="" alt="Preview" class="upload-preview">

                            <span class="upload-icon">＋</span>
                            <strong>Upload Image</strong>
                            <small>JPG, PNG, SVG, WEBP maksimal 2MB</small>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Icon</label>

                        <label class="upload-box upload-field">
                            <input type="file" name="icon" accept="image/*,.svg" data-file-input>
                            <img src="" alt="Preview" class="upload-preview">

                            <span class="upload-icon">＋</span>
                            <strong>Upload Icon</strong>
                            <small>JPG, PNG, SVG, WEBP maksimal 2MB</small>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Enter Description">{{ old('description') }}</textarea>
                </div>

                <div class="form-grid two">
                    <div class="switch-row">
                        <span>Status Active</span>

                        <label class="form-switch">
                            <input type="hidden" name="status" value="inactive">
                            <input type="checkbox" name="status" value="active" checked>
                            <span></span>
                        </label>
                    </div>

                    <div class="switch-row">
                        <span>Featured</span>

                        <label class="form-switch">
                            <input type="hidden" name="is_featured" value="0">
                            <input type="checkbox" name="is_featured" value="1" checked>
                            <span></span>
                        </label>
                    </div>
                </div>
            </div>

            <div class="subcategory-modal-footer">
                <button type="button" class="modal-cancel-btn" data-modal-close>
                    Cancel
                </button>

                <button type="submit" class="modal-save-btn">
                    Save Sub Category
                </button>
            </div>
        </form>
    </div>
</div>

@foreach ($subCategories as $subCategory)
    @php
        $imageUrl = $assetUrl($subCategory->image);
        $iconUrl = $assetUrl($subCategory->icon);
    @endphp

    <div class="subcategory-modal" id="editSubCategoryModal{{ $subCategory->id }}">
        <div class="subcategory-modal-dialog large">
            <div class="subcategory-modal-header">
                <div>
                    <h3>Edit Sub Category</h3>
                    <p>Ubah data sub category service.</p>
                </div>

                <button type="button" class="modal-close" data-modal-close>
                    ×
                </button>
            </div>

            <form action="{{ route('admin.service-subcategories.update', $subCategory->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="subcategory-modal-body">
                    <div class="form-grid two">
                        <div class="form-group">
                            <label>Category <span>*</span></label>

                            <select name="service_category_id" required>
                                <option value="">Select Category</option>

                                @foreach ($categories as $category)
                                    <option value="{{ $category->id }}" {{ old('service_category_id', $subCategory->service_category_id) == $category->id ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Sub Category Name <span>*</span></label>
                            <input
                                type="text"
                                name="name"
                                value="{{ old('name', $subCategory->name) }}"
                                placeholder="Enter Sub Category Name"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Slug</label>
                        <input
                            type="text"
                            name="slug"
                            value="{{ old('slug', $subCategory->slug) }}"
                            placeholder="subcategory-slug"
                        >
                    </div>

                    <div class="form-grid two">
                        <div class="form-group">
                            <label>Image</label>

                            <div class="current-preview-box">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $subCategory->name }}">
                                @else
                                    No image
                                @endif
                            </div>

                            <label class="upload-box upload-field">
                                <input type="file" name="image" accept="image/*,.svg" data-file-input>
                                <img src="" alt="Preview" class="upload-preview">

                                <span class="upload-icon">＋</span>
                                <strong>Change Image</strong>
                                <small>Biarkan kosong jika tidak diganti</small>
                            </label>
                        </div>

                        <div class="form-group">
                            <label>Icon</label>

                            <div class="current-preview-box">
                                @if ($iconUrl)
                                    <img src="{{ $iconUrl }}" alt="{{ $subCategory->name }}">
                                @else
                                    No icon
                                @endif
                            </div>

                            <label class="upload-box upload-field">
                                <input type="file" name="icon" accept="image/*,.svg" data-file-input>
                                <img src="" alt="Preview" class="upload-preview">

                                <span class="upload-icon">＋</span>
                                <strong>Change Icon</strong>
                                <small>Biarkan kosong jika tidak diganti</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Enter Description">{{ old('description', $subCategory->description) }}</textarea>
                    </div>

                    <div class="form-grid two">
                        <div class="switch-row">
                            <span>Status Active</span>

                            <label class="form-switch">
                                <input type="hidden" name="status" value="inactive">
                                <input type="checkbox" name="status" value="active" {{ old('status', $subCategory->status) === 'active' ? 'checked' : '' }}>
                                <span></span>
                            </label>
                        </div>

                        <div class="switch-row">
                            <span>Featured</span>

                            <label class="form-switch">
                                <input type="hidden" name="is_featured" value="0">
                                <input type="checkbox" name="is_featured" value="1" {{ old('is_featured', $subCategory->is_featured) ? 'checked' : '' }}>
                                <span></span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="subcategory-modal-footer">
                    <button type="button" class="modal-cancel-btn" data-modal-close>
                        Cancel
                    </button>

                    <button type="submit" class="modal-save-btn">
                        Update Sub Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="subcategory-modal" id="deleteSubCategoryModal{{ $subCategory->id }}">
        <div class="subcategory-modal-dialog delete">
            <div class="delete-icon">
                <svg viewBox="0 0 24 24" fill="none">
                    <path d="M5 7H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M9 7V5H15V7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M8 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M12 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M16 10V18" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    <path d="M7 7L8 21H16L17 7" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                </svg>
            </div>

            <h3>Delete Sub Category?</h3>

            <p>
                Sub category <strong>{{ $subCategory->name }}</strong> akan dihapus.
                Aksi ini tidak bisa dibatalkan.
            </p>

            <div class="delete-actions">
                <button type="button" class="modal-cancel-btn" data-modal-close>
                    Cancel
                </button>

                <form action="{{ route('admin.service-subcategories.destroy', $subCategory->id) }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="submit" class="delete-confirm-btn">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/service-subcategories.js') }}"></script>
@endpush