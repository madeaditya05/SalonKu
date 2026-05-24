@extends('provider.layouts.dashboard')

@section('title', 'Branch - Provider Dashboard')
@section('page_title', 'Branch')
@section('page_subtitle', 'Manage semua cabang provider kamu.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/branch.css') }}">
@endpush

@section('content')
@php
    $isBranchAccount = $isBranchAccount ?? false;
@endphp

<section class="provider-branch-page">
    <div class="branch-index-header">
        <div>
            <h1>Branch</h1>

            <div class="branch-breadcrumb">
                <span>Dashboard</span>
                <span>›</span>
                <strong>Branch</strong>
            </div>
        </div>

        <div class="branch-header-actions">
            <button type="button" class="branch-filter-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/>
                    <path d="M1 14h6M9 8h6M17 16h6"/>
                </svg>
                Filter
            </button>

            @unless ($isBranchAccount)
                <a href="{{ provider_route('provider.branch.create') }}" class="branch-add-btn">
                    <svg viewBox="0 0 24 24">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Add Branch
                </a>
            @endunless
        </div>
    </div>

    @if (session('success'))
        <div class="branch-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="branch-alert error">
            {{ session('error') }}
        </div>
    @endif

    <div class="branch-data-card">
        <div class="branch-data-toolbar">
            <div class="branch-length-control">
                <span>Show</span>

                <select id="branchEntriesSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>

                <span>entries</span>
            </div>

            <div class="branch-search-control">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>

                <input type="text" id="branchSearchInput" placeholder="Search branch">
            </div>
        </div>

        <div class="branch-table-responsive">
            <table class="branch-datatable" id="branchTable">
                <thead>
                    <tr>
                        <th data-sort="number">
                            <span>#</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Branch Name</span>
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

                        <th data-sort="number">
                            <span>Staffs</span>
                            <span class="sort-icon">↕</span>
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
                    @forelse ($branches as $index => $branch)
                        @php
                            $staffCount = $branch->staffs_count ?? optional($branch->staffs)->count() ?? 0;
                            $branchInitial = strtoupper(substr($branch->branch_name ?? 'B', 0, 1));

                            $locationParts = collect([
                                $branch->city_id ?? null,
                                $branch->state_id ?? null,
                                $branch->country_id ?? null,
                            ])->filter()->values()->implode(', ');
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <div class="branch-name-cell">
                                    <div class="branch-avatar">
                                        @if (!empty($branch->image_url))
                                            <img src="{{ $branch->image_url }}" alt="{{ $branch->branch_name }}">
                                        @else
                                            {{ $branchInitial }}
                                        @endif
                                    </div>

                                    <div>
                                        <strong>{{ $branch->branch_name }}</strong>
                                        <small>{{ $branch->email ?? 'No email' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                {{ $branch->phone_code ?? '' }}{{ $branch->phone_number ?? '-' }}
                            </td>

                            <td>
                                <div class="branch-location-cell">
                                    <strong>{{ $locationParts ?: '-' }}</strong>
                                    <small>{{ $branch->address ?? '-' }}</small>
                                </div>
                            </td>

                            <td>
                                <span class="branch-count-badge">
                                    {{ $staffCount }}
                                </span>
                            </td>

                            <td>
                                <span class="branch-status-badge {{ $branch->status ?? 'active' }}">
                                    {{ ucfirst($branch->status ?? 'active') }}
                                </span>
                            </td>

                            <td>
                                <div class="branch-action-group">
                                    <a href="{{ provider_route('provider.branch.edit', $branch->id) }}" class="branch-icon-btn edit" title="Edit">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    @unless ($isBranchAccount)
                                        <button
                                            type="button"
                                            class="branch-icon-btn delete branch-delete-trigger"
                                            title="Delete"
                                            data-delete-url="{{ provider_route('provider.branch.destroy', $branch->id) }}"
                                        >
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v6M14 11v6"/>
                                            </svg>
                                        </button>
                                    @endunless
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="branch-empty-row">
                            <td colspan="7">No branch available</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="branch-data-footer">
            <div class="branch-info-text" id="branchInfoText">
                Showing 0 to 0 of 0 entries
            </div>

            <div class="branch-pagination" id="branchPagination">
                <button type="button" data-page="first">First</button>
                <button type="button" data-page="previous">Previous</button>
                <button type="button" class="active" data-page="1">1</button>
                <button type="button" data-page="next">Next</button>
                <button type="button" data-page="last">Last</button>
            </div>
        </div>
    </div>
</section>

<div class="branch-delete-modal-overlay" id="branchDeleteModal">
    <div class="branch-delete-modal">
        <div class="branch-delete-icon">
            <svg viewBox="0 0 24 24">
                <path d="M3 6h18"/>
                <path d="M8 6V4h8v2"/>
                <path d="M19 6l-1 14H6L5 6"/>
                <path d="M10 11v6M14 11v6"/>
            </svg>
        </div>

        <h2>Confirm Deletion</h2>

        <p>
            Are you sure you want to delete this branch? This action cannot be undone.
        </p>

        <div class="branch-delete-modal-actions">
            <button type="button" class="branch-modal-cancel" id="branchDeleteCancel">
                Cancel
            </button>

            <form method="POST" id="branchDeleteConfirmForm">
                @csrf
                @method('DELETE')

                <button type="submit" class="branch-modal-delete">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/branch.js') }}"></script>
@endpush
