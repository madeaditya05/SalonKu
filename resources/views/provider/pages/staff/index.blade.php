@extends('provider.layouts.dashboard')

@section('title', 'Staffs - Provider Dashboard')
@section('page_title', 'Staffs')
@section('page_subtitle', 'Manage semua staff provider kamu.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/staff.css') }}">
@endpush

@section('content')
@php
    $staffs = $staffs ?? collect();
    $categories = $categories ?? collect();
    $branches = $branches ?? collect();
@endphp

<section class="provider-staff-page">
    <div class="staff-index-header">
        <div>
            <h1>Staffs</h1>

            <div class="staff-breadcrumb">
                <span>Dashboard</span>
                <span>›</span>
                <strong>Staffs</strong>
            </div>
        </div>

        <div class="staff-header-actions">
            <button type="button" class="staff-filter-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/>
                    <path d="M1 14h6M9 8h6M17 16h6"/>
                </svg>
                Filter
            </button>

            <button type="button" class="staff-add-btn" id="staffAddBtn">
                <svg viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Staff
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="staff-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="staff-alert error">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="staff-alert error">
            Ada data yang belum valid. Silakan cek form kembali.
        </div>
    @endif

    <div class="staff-data-card">
        <div class="staff-data-toolbar">
            <div class="staff-length-control">
                <span>Show</span>

                <select id="staffEntriesSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>

                <span>entries</span>
            </div>

            <div class="staff-search-control">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>

                <input type="text" id="staffSearchInput" placeholder="Search staff">
            </div>
        </div>

        <div class="staff-table-responsive">
            <table class="staff-datatable" id="staffTable">
                <thead>
                    <tr>
                        <th data-sort="number">
                            <span>#</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Staff</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Phone</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Location</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Branch</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Role</span>
                            <span class="sort-icon">â†•</span>
                        </th>

                        <th data-sort="text">
                            <span>Status</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th>
                            <span>Action</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($staffs as $index => $staff)
                        @php
                            $staffName = $staff->full_name ?? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                            $staffInitial = strtoupper(substr($staffName ?: 'S', 0, 1));

                            $branchName = $staff->branch->branch_name
                                ?? optional($branches->firstWhere('id', $staff->branch_id))->branch_name
                                ?? '-';

                            $categoryName = optional($categories->firstWhere('id', $staff->category_id))->name ?? $staff->category_id;
                            $roleName = $staff->providerRole->role_name ?? 'Default staff';

                            $locationParts = collect([
                                $staff->city_id ?? null,
                                $staff->state_id ?? null,
                                $staff->country_id ?? null,
                            ])->filter()->values()->implode(', ');

                            $staffPayload = [
                                'id' => $staff->id,
                                'image' => $staff->image,
                                'image_url' => $staff->image ? asset('storage/' . $staff->image) : '',
                                'first_name' => $staff->first_name,
                                'last_name' => $staff->last_name,
                                'email' => $staff->email,
                                'username' => $staff->username,
                                'country_code' => $staff->country_code,
                                'phone_number' => $staff->phone_number,
                                'gender' => $staff->gender,
                                'date_of_birth' => optional($staff->date_of_birth)->format('Y-m-d') ?? $staff->date_of_birth,
                                'address' => $staff->address,
                                'country_id' => $staff->country_id,
                                'state_id' => $staff->state_id,
                                'city_id' => $staff->city_id,
                                'postal_code' => $staff->postal_code,
                                'bio' => $staff->bio,
                                'category_id' => $staff->category_id,
                                'branch_id' => $staff->branch_id,
                                'provider_role_id' => $staff->provider_role_id,
                                'status' => $staff->status ?? 'active',
                                'update_url' => provider_route('provider.staffs.update', $staff->id),
                                'delete_url' => provider_route('provider.staffs.destroy', $staff->id),
                            ];
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <div class="staff-name-cell">
                                    <div class="staff-avatar">
                                        @if (!empty($staff->image))
                                            <img src="{{ asset('storage/' . $staff->image) }}" alt="{{ $staffName }}">
                                        @else
                                            {{ $staffInitial }}
                                        @endif
                                    </div>

                                    <div>
                                        <strong>{{ $staffName ?: '-' }}</strong>
                                        <small>{{ $staff->email ?? '-' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{ $staff->country_code ?? '' }}{{ $staff->phone_number ?? '-' }}
                            </td>

                            <td>
                                <div class="staff-location-cell">
                                    <strong>{{ $locationParts ?: '-' }}</strong>
                                    <small>{{ $staff->address ?? '-' }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="staff-branch-cell">
                                    <strong>{{ $branchName }}</strong>
                                    <small>{{ $categoryName ?: '-' }}</small>
                                </div>
                            </td>

                            <td>
                                <div class="staff-branch-cell">
                                    <strong>{{ $roleName }}</strong>
                                    <small>{{ $staff->role ?? 'staff' }}</small>
                                </div>
                            </td>

                            <td>
                                <span class="staff-status-badge {{ $staff->status ?? 'active' }}">
                                    {{ ucfirst($staff->status ?? 'active') }}
                                </span>
                            </td>

                            <td>
                                <div class="staff-action-group">
                                    <button
                                        type="button"
                                        class="staff-icon-btn edit staff-edit-btn"
                                        title="Edit"
                                        data-staff='@json($staffPayload)'
                                    >
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                    </button>

                                    <button
                                        type="button"
                                        class="staff-icon-btn delete staff-delete-trigger"
                                        title="Delete"
                                        data-delete-url="{{ provider_route('provider.staffs.destroy', $staff->id) }}"
                                    >
                                        <svg viewBox="0 0 24 24">
                                            <path d="M3 6h18"/>
                                            <path d="M8 6V4h8v2"/>
                                            <path d="M19 6l-1 14H6L5 6"/>
                                            <path d="M10 11v6M14 11v6"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="staff-empty-row">
                            <td colspan="8">No staff available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="staff-data-footer">
            <div class="staff-info-text" id="staffInfoText">
                Showing 0 to 0 of 0 entries
            </div>

            <div class="staff-pagination" id="staffPagination">
                <button type="button" data-page="first">First</button>
                <button type="button" data-page="previous">Previous</button>
                <button type="button" class="active" data-page="1">1</button>
                <button type="button" data-page="next">Next</button>
                <button type="button" data-page="last">Last</button>
            </div>
        </div>
    </div>
</section>

<div class="staff-modal-overlay" id="staffModal">
    <div class="staff-modal">
        <div class="staff-modal-header">
            <div>
                <h2 id="staffModalTitle">Add Staff</h2>
                <p>Lengkapi data staff provider.</p>
            </div>

            <button type="button" class="staff-modal-close" id="staffModalClose">
                ×
            </button>
        </div>

        <form action="{{ provider_route('provider.staffs.store') }}" method="POST" enctype="multipart/form-data" id="staffForm">
            @csrf
            <input type="hidden" name="_method" id="staffFormMethod" value="">
            <input type="hidden" name="role" value="staff">

            <div class="staff-form-body">
                <div class="staff-image-row">
                    <label for="staffImageInput" class="staff-image-upload">
                        <img src="" alt="Staff Preview" id="staffImagePreview" class="hidden">

                        <span id="staffImagePlaceholder">
                            ▧<br>
                            Image
                        </span>
                    </label>

                    <input type="file" name="image" id="staffImageInput" accept="image/*" hidden>

                    <div>
                        <strong>Profile Image</strong>
                        <p>Upload foto staff. Format JPG, PNG, WEBP.</p>
                    </div>
                </div>

                <div class="staff-form-grid two">
                    <div class="staff-form-group">
                        <label>First Name <span>*</span></label>
                        <input type="text" name="first_name" placeholder="Enter First Name">
                    </div>

                    <div class="staff-form-group">
                        <label>Last Name <span>*</span></label>
                        <input type="text" name="last_name" placeholder="Enter Last Name">
                    </div>

                    <div class="staff-form-group">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" placeholder="Enter Email">
                    </div>

                    <div class="staff-form-group">
                        <label>Username</label>
                        <input type="text" name="username" placeholder="Enter Username">
                    </div>

                    <div class="staff-form-group">
                        <label>Phone Number</label>

                        <div class="staff-phone-row">
                            <select name="country_code" id="staffPhoneCodeSelect" data-selected="+62">
                                <option value="">Loading codes...</option>
                            </select>

                            <input type="text" name="phone_number" placeholder="Enter Phone Number">
                        </div>
                    </div>

                    <div class="staff-form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">Select Gender</option>
                            <option value="male">Male</option>
                            <option value="female">Female</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    <div class="staff-form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="date_of_birth">
                    </div>

                    <div class="staff-form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="staff-form-group">
                        <label>Country</label>
                        <select name="country_id" id="staffCountrySelect" data-selected="Indonesia">
                            <option value="">Loading countries...</option>
                        </select>
                    </div>

                    <div class="staff-form-group">
                        <label>State</label>
                        <select name="state_id" id="staffStateSelect" data-selected="">
                            <option value="">Select Country First</option>
                        </select>
                    </div>

                    <div class="staff-form-group">
                        <label>City</label>
                        <select name="city_id" id="staffCitySelect" data-selected="">
                            <option value="">Select State First</option>
                        </select>
                    </div>

                    <div class="staff-form-group">
                        <label>Postal Code</label>
                        <input type="text" name="postal_code" placeholder="Enter Postal Code">
                    </div>

                    <div class="staff-form-group">
                        <label>Category</label>
                        <select name="category_id" id="staffCategorySelect" required>
    <option value="">Select Category</option>

    @foreach ($categories as $category)
        <option value="{{ $category->id }}">
            {{ $category->name }}
        </option>
    @endforeach
</select>
                    </div>

                    <div class="staff-form-group">
                        <label>Branch</label>
                        <select name="branch_id" id="staffBranchSelect" required>
    <option value="">Select Branch</option>

    @foreach ($branches as $branch)
        <option value="{{ $branch->id }}">
            {{ $branch->branch_name }}
        </option>
    @endforeach
</select>
                    </div>
                </div>

                <div class="staff-form-group full">
                    <label>Address</label>
                    <textarea name="address" placeholder="Enter Address"></textarea>
                </div>

                <div class="staff-form-group full">
                    <label>Bio</label>
                    <textarea name="bio" placeholder="Enter Bio"></textarea>
                </div>
            </div>

            <div class="staff-form-actions">
                <button type="button" class="staff-back-btn" id="staffModalCancel">
                    Cancel
                </button>

                <button type="submit" class="staff-submit-btn">
                    Save
                </button>
            </div>
        </form>
    </div>
</div>

<div class="staff-delete-modal-overlay" id="staffDeleteModal">
    <div class="staff-delete-modal">
        <div class="staff-delete-icon">
            <svg viewBox="0 0 24 24">
                <path d="M3 6h18"/>
                <path d="M8 6V4h8v2"/>
                <path d="M19 6l-1 14H6L5 6"/>
                <path d="M10 11v6M14 11v6"/>
            </svg>
        </div>

        <h2>Confirm Deletion</h2>

        <p>
            Are you sure you want to delete this staff? This action cannot be undone.
        </p>

        <div class="staff-delete-modal-actions">
            <button type="button" class="staff-modal-cancel" id="staffDeleteCancel">
                Cancel
            </button>

            <form method="POST" id="staffDeleteConfirmForm">
                @csrf
                @method('DELETE')

                <button type="submit" class="staff-modal-delete">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/staff.js') }}"></script>
@endpush
