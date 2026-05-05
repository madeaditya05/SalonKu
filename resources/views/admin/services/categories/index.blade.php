@extends('admin.layouts.app')

@section('title', 'Category - JasaKu')
@section('page_title', 'Category')

@push('styles')
    <link rel="stylesheet" href="{{ asset('admin/css/pages/service-categories.css') }}">
@endpush

@section('content')
@php
    use Illuminate\Support\Str;

    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');

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

<section class="category-page">
    <div class="category-page-header">
        <div>
            <h1>Category</h1>

            <div class="category-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>›</span>
                <a href="{{ route('admin.services.index') }}">Service</a>
                <span>›</span>
                <strong>Category</strong>
            </div>
        </div>

        <button type="button" class="admin-dark-btn" data-modal-open="addCategoryModal">
            <svg viewBox="0 0 24 24" fill="none">
                <path d="M12 5V19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                <path d="M5 12H19" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
            Add Category
        </button>
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

    <div class="category-card">
        <div class="category-toolbar">
            <form method="GET" action="{{ route('admin.service-categories.index') }}" class="entries-box">
                @if ($search)
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

            <form method="GET" action="{{ route('admin.service-categories.index') }}" class="search-box">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="category-search-input">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search category"
                    >
                </div>
            </form>
        </div>

        <div class="category-table-wrap">
            <table class="category-table">
                <thead>
                    <tr>
                        <th class="number-column"># <span class="sort-icon">↕</span></th>
                        <th>Categories <span class="sort-icon">↕</span></th>
                        <th>Slug <span class="sort-icon">↕</span></th>
                        <th>Status <span class="sort-icon">↕</span></th>
                        <th>Featured <span class="sort-icon">↕</span></th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($categories as $category)
                        @php
                            $imageUrl = $assetUrl($category->image);
                            $iconUrl = $assetUrl($category->icon);
                            $status = $category->status ?? 'inactive';
                            $isFeatured = (bool) $category->is_featured;
                        @endphp

                        <tr>
                            <td>
                                {{ $loop->iteration + ($categories->firstItem() - 1) }}
                            </td>

                            <td>
                                <div class="category-name-box">
                                    @if ($imageUrl)
                                        <img src="{{ $imageUrl }}" alt="{{ $category->name }}" class="category-thumb">
                                    @else
                                        <div class="category-thumb-placeholder">
                                            {{ strtoupper(substr($category->name ?? 'C', 0, 1)) }}
                                        </div>
                                    @endif

                                    <div class="category-name-text">
                                        <strong>{{ $category->name }}</strong>

                                        @if ($category->description)
                                            <small>{{ Str::limit(strip_tags($category->description), 44) }}</small>
                                        @else
                                            <small>Service category</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <td>{{ $category->slug }}</td>

                            <td>
                                <form
                                    action="{{ route('admin.service-categories.toggle-status', $category->id) }}"
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
                                    action="{{ route('admin.service-categories.toggle-featured', $category->id) }}"
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
                                <div class="category-actions">
                                    <button
                                        type="button"
                                        class="category-action-btn"
                                        title="Edit"
                                        data-modal-open="editCategoryModal{{ $category->id }}"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M4 20H8L18 10L14 6L4 16V20Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <path d="M13 7L17 11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="category-action-btn danger"
                                        title="Delete"
                                        data-modal-open="deleteCategoryModal{{ $category->id }}"
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
                            <td colspan="6" class="empty-state">
                                Belum ada data category.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-info">
                Showing {{ $categories->firstItem() ?? 0 }} to {{ $categories->lastItem() ?? 0 }} of {{ $categories->total() }} entries
            </div>

            <div class="pagination-wrap category-pagination">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</section>

{{-- Add Category Modal --}}
<div class="category-modal" id="addCategoryModal">
    <div class="category-modal-dialog large">
        <div class="category-modal-header">
            <div>
                <h3>Add Category</h3>
                <p>Tambah data category service baru.</p>
            </div>

            <button type="button" class="modal-close" data-modal-close>
                ×
            </button>
        </div>

        <form action="{{ route('admin.service-categories.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="category-modal-body">
                <div class="form-grid two">
                    <div class="form-group">
                        <label>Name <span>*</span></label>
                        <input
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            placeholder="Contoh: Salon"
                            data-slug-source
                            required
                        >
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
                </div>

                <div class="form-grid two">
                    <div class="form-group">
                        <label>Image</label>

                        <label class="upload-field">
                            <input type="file" name="image" accept="image/*" data-file-input>
                            <img src="" alt="Preview" class="upload-preview">

                            <span class="upload-icon">＋</span>
                            <strong>Upload Image</strong>
                            <small>JPG, PNG, WEBP maksimal 2MB</small>
                        </label>
                    </div>

                    <div class="form-group">
                        <label>Icon</label>

                        <label class="upload-field">
                            <input type="file" name="icon" accept="image/*" data-file-input>
                            <img src="" alt="Preview" class="upload-preview">

                            <span class="upload-icon">＋</span>
                            <strong>Upload Icon</strong>
                            <small>JPG, PNG, SVG, WEBP maksimal 2MB</small>
                        </label>
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" rows="4" placeholder="Deskripsi category">{{ old('description') }}</textarea>
                </div>

                <div class="switch-row">
                    <span>Status Active</span>

                    <label class="form-switch">
                        <input type="checkbox" name="status" value="active" checked>
                        <span></span>
                    </label>
                </div>

                <div class="switch-row">
                    <span>Featured</span>

                    <label class="form-switch">
                        <input type="checkbox" name="is_featured" value="1" checked>
                        <span></span>
                    </label>
                </div>
            </div>

            <div class="category-modal-footer">
                <button type="button" class="modal-cancel-btn" data-modal-close>
                    Cancel
                </button>

                <button type="submit" class="modal-save-btn">
                    Save Category
                </button>
            </div>
        </form>
    </div>
</div>

@foreach ($categories as $category)
    @php
        $imageUrl = $assetUrl($category->image);
        $iconUrl = $assetUrl($category->icon);
    @endphp

    {{-- Edit Category Modal --}}
    <div class="category-modal" id="editCategoryModal{{ $category->id }}">
        <div class="category-modal-dialog large">
            <div class="category-modal-header">
                <div>
                    <h3>Edit Category</h3>
                    <p>Ubah data category service.</p>
                </div>

                <button type="button" class="modal-close" data-modal-close>
                    ×
                </button>
            </div>

            <form action="{{ route('admin.service-categories.update', $category->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="category-modal-body">
                    <div class="form-grid two">
                        <div class="form-group">
                            <label>Name <span>*</span></label>
                            <input type="text" name="name" value="{{ old('name', $category->name) }}" placeholder="Category name" required>
                        </div>

                        <div class="form-group">
                            <label>Slug</label>
                            <input type="text" name="slug" value="{{ old('slug', $category->slug) }}" placeholder="category-slug">
                        </div>
                    </div>

                    <div class="form-grid two">
                        <div class="form-group">
                            <label>Image</label>

                            <div class="current-preview-box">
                                @if ($imageUrl)
                                    <img src="{{ $imageUrl }}" alt="{{ $category->name }}">
                                @else
                                    No image
                                @endif
                            </div>

                            <label class="upload-field">
                                <input type="file" name="image" accept="image/*" data-file-input>
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
                                    <img src="{{ $iconUrl }}" alt="{{ $category->name }}">
                                @else
                                    No icon
                                @endif
                            </div>

                            <label class="upload-field">
                                <input type="file" name="icon" accept="image/*" data-file-input>
                                <img src="" alt="Preview" class="upload-preview">

                                <span class="upload-icon">＋</span>
                                <strong>Change Icon</strong>
                                <small>Biarkan kosong jika tidak diganti</small>
                            </label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="4" placeholder="Deskripsi category">{{ old('description', $category->description) }}</textarea>
                    </div>

                    <div class="switch-row">
                        <span>Status Active</span>

                        <label class="form-switch">
                            <input type="checkbox" name="status" value="active" {{ $category->status === 'active' ? 'checked' : '' }}>
                            <span></span>
                        </label>
                    </div>

                    <div class="switch-row">
                        <span>Featured</span>

                        <label class="form-switch">
                            <input type="checkbox" name="is_featured" value="1" {{ $category->is_featured ? 'checked' : '' }}>
                            <span></span>
                        </label>
                    </div>
                </div>

                <div class="category-modal-footer">
                    <button type="button" class="modal-cancel-btn" data-modal-close>
                        Cancel
                    </button>

                    <button type="submit" class="modal-save-btn">
                        Update Category
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Delete Category Modal --}}
    <div class="category-modal" id="deleteCategoryModal{{ $category->id }}">
        <div class="category-modal-dialog delete">
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

            <h3>Delete Category?</h3>

            <p>
                Category <strong>{{ $category->name }}</strong> akan dihapus.
                Aksi ini tidak bisa dibatalkan.
            </p>

            <div class="delete-actions">
                <button type="button" class="modal-cancel-btn" data-modal-close>
                    Cancel
                </button>

                <form action="{{ route('admin.service-categories.destroy', $category->id) }}" method="POST">
                    @csrf
                    @method('DELETE')

                    <button type="button" class="delete-confirm-btn">
                        Delete
                    </button>
                </form>
            </div>
        </div>
    </div>
@endforeach
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/service-categories.js') }}"></script>
@endpush