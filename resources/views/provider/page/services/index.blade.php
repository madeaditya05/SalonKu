@extends('provider.layouts.dashboard')

@section('title', 'My Service - Provider Dashboard')
@section('page_title', 'My Service')
@section('page_subtitle', 'Manage semua layanan yang kamu buat.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/my-service.css') }}">
@endpush

@section('content')
<section class="provider-service-page">
    <div class="service-index-header">
        <div>
            <h1>My Service</h1>

            <div class="service-breadcrumb">
                <span>Dashboard</span>
                <span>›</span>
                <strong>My Service</strong>
            </div>
        </div>

        <div class="service-header-actions">
            <button type="button" class="service-filter-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M4 21v-7M4 10V3M12 21v-9M12 8V3M20 21v-5M20 12V3"/>
                    <path d="M1 14h6M9 8h6M17 16h6"/>
                </svg>
                Filter
            </button>

            <a href="{{ route('provider.services.create') }}" class="service-add-btn">
                <svg viewBox="0 0 24 24">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Service
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="service-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="service-alert error">
            {{ session('error') }}
        </div>
    @endif

    <div class="service-data-card">
        <div class="service-data-toolbar">
            <div class="service-length-control">
                <span>Show</span>

                <select id="serviceEntriesSelect">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                </select>

                <span>entries</span>
            </div>

            <div class="service-search-control">
                <svg viewBox="0 0 24 24">
                    <circle cx="11" cy="11" r="7"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>

                <input type="text" id="serviceSearchInput" placeholder="Search service">
            </div>
        </div>

        <div class="service-table-responsive">
            <table class="service-datatable" id="serviceTable">
                <thead>
                    <tr>
                        <th data-sort="number">
                            <span>#</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Service Name</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Slug</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Status</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th data-sort="text">
                            <span>Verify Status</span>
                            <span class="sort-icon">↕</span>
                        </th>

                        <th>
                            <span>Action</span>
                        </th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($services as $index => $service)
                        @php
                            $documentStatus = optional($service->provider?->providerProfile)->document_status ?? 'pending';
                        @endphp

                        <tr>
                            <td>{{ $index + 1 }}</td>

                            <td>
                                <div class="service-name-cell">
                                    <div class="service-avatar">
                                        {{ strtoupper(substr($service->title ?? 'S', 0, 1)) }}
                                    </div>

                                    <div>
                                        <strong>{{ $service->title }}</strong>
                                        <small>{{ $service->category ?? 'General' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $service->slug }}</td>

                            <td data-status="{{ $service->status }}">
                                <form action="{{ route('provider.services.toggle-status', $service->id) }}" method="POST" class="service-status-form">
                                    @csrf
                                    @method('PATCH')

                                    <label class="service-switch">
                                        <input
                                            type="checkbox"
                                            onchange="this.form.submit()"
                                            {{ $service->status === 'active' ? 'checked' : '' }}
                                        >
                                        <span></span>
                                    </label>
                                </form>
                            </td>

                            <td data-verify="{{ $documentStatus }}">
                                <span class="service-verify-badge {{ $documentStatus }}">
                                    • {{ ucfirst($documentStatus) }}
                                </span>
                            </td>

                            <td>
                                <div class="service-action-group">
                                    <a href="{{ route('provider.services.edit', $service->id) }}" class="service-icon-btn edit" title="Edit">
                                        <svg viewBox="0 0 24 24">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                    </a>

                                    <form action="{{ route('provider.services.destroy', $service->id) }}" method="POST" class="service-delete-form">
                                        @csrf
                                        @method('DELETE')

                                        <button type="button" class="service-icon-btn delete service-delete-trigger" title="Delete">
                                            <svg viewBox="0 0 24 24">
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v6M14 11v6"/>
                                            </svg>
                                        </button>
                                    </form>

                                    <button type="button" class="service-promote-btn">
                                        Promote
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr class="service-empty-row">
                            <td colspan="6">No Service found</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="service-data-footer">
            <div class="service-info-text" id="serviceInfoText">
                Showing 0 to 0 of 0 entries
            </div>

            <div class="service-pagination" id="servicePagination">
                <button type="button" data-page="first">First</button>
                <button type="button" data-page="previous">Previous</button>
                <button type="button" class="active" data-page="1">1</button>
                <button type="button" data-page="next">Next</button>
                <button type="button" data-page="last">Last</button>
            </div>
        </div>
    </div>
</section>

<div class="service-delete-modal-overlay" id="serviceDeleteModal">
    <div class="service-delete-modal">
        <div class="service-delete-icon">
            <svg viewBox="0 0 24 24">
                <path d="M3 6h18"/>
                <path d="M8 6V4h8v2"/>
                <path d="M19 6l-1 14H6L5 6"/>
                <path d="M10 11v6M14 11v6"/>
            </svg>
        </div>

        <h2>Confirm Deletion</h2>

        <p>
            Are you sure you want to delete this item? This action cannot be undone.
        </p>

        <div class="service-delete-modal-actions">
            <button type="button" class="service-modal-cancel" id="serviceDeleteCancel">
                Cancel
            </button>

            <form method="POST" id="serviceDeleteConfirmForm">
                @csrf
                @method('DELETE')

                <button type="submit" class="service-modal-delete">
                    Yes, Delete
                </button>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/my-service.js') }}"></script>
@endpush