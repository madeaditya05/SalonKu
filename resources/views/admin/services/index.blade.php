@extends('admin.layouts.app')

@section('title', 'Services - JasaKu')
@section('page_title', 'Services')

@section('content')
@php
    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');

    $serviceStatusClass = function ($status) {
        return match ($status) {
            'active' => 'active',
            'inactive' => 'inactive',
            default => 'neutral',
        };
    };

    $documentStatusClass = function ($status) {
        return match ($status) {
            'verified' => 'verified',
            'submitted' => 'submitted',
            'pending' => 'pending',
            'rejected' => 'rejected',
            default => 'neutral',
        };
    };

    $statusLabel = function ($status) {
        return ucwords(str_replace('_', ' ', $status ?: '-'));
    };
@endphp

<section class="admin-services-page">
    <div class="admin-service-heading">
        <div>
            <h1>Services</h1>

            <div class="admin-service-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>›</span>
                <strong>Services</strong>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-service-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="admin-service-alert danger">
            {{ session('error') }}
        </div>
    @endif

    <div class="admin-service-card">
        <div class="admin-service-toolbar">
            <form method="GET" action="{{ route('admin.services.index') }}" class="admin-service-entries">
                @if ($search !== '')
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

            <form method="GET" action="{{ route('admin.services.index') }}" class="admin-service-search">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <label for="serviceSearchInput">Search:</label>

                <div class="admin-service-search-box">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"></circle>
                        <path d="m21 21-4.3-4.3"></path>
                    </svg>

                    <input id="serviceSearchInput"
                           type="text"
                           name="search"
                           value="{{ $search }}"
                           placeholder="Search service">
                </div>
            </form>
        </div>

        <div class="admin-service-table-wrap">
            <table class="admin-service-table">
                <thead>
                    <tr>
                        <th># <span>↕</span></th>
                        <th>Service Name <span>↕</span></th>
                        <th>Slug <span>↕</span></th>
                        <th>Provider <span>↕</span></th>
                        <th>Category <span>↕</span></th>
                        <th>Code <span>↕</span></th>
                        <th>Price <span>↕</span></th>
                        <th>Status <span>↕</span></th>
                        <th>Verify Status <span>↕</span></th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($services as $service)
                        @php
                            $providerName = $service->provider_name ?? '-';
                            $providerEmail = $service->provider_email ?? '-';
                            $documentStatus = $service->provider_document_status ?? 'pending';
                        @endphp

                        <tr>
                            <td>
                                {{ $loop->iteration + ($services->firstItem() - 1) }}
                            </td>

                            <td>
                                <div class="admin-service-name">
                                    <strong>{{ $service->title ?? '-' }}</strong>

                                    @if (!empty($service->sub_category))
                                        <small>{{ $service->sub_category }}</small>
                                    @endif
                                </div>
                            </td>

                            <td>
                                <span class="admin-service-text">
                                    {{ $service->slug ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <div class="admin-service-provider">
                                    <span>
                                        {{ strtoupper(substr($providerName !== '-' ? $providerName : 'P', 0, 1)) }}
                                    </span>

                                    <div>
                                        <strong>{{ $providerName }}</strong>
                                        <small>{{ $providerEmail }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>
                                <span class="admin-service-text">
                                    {{ $service->category ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <span class="admin-service-code">
                                    {{ $service->code ?? '-' }}
                                </span>
                            </td>

                            <td>
                                <strong class="admin-service-price">
                                    Rp {{ number_format((float) ($service->price ?? 0), 2) }}
                                </strong>
                            </td>

                            <td>
                                <span class="admin-service-badge {{ $serviceStatusClass($service->status ?? null) }}">
                                    <i></i>
                                    {{ $statusLabel($service->status ?? 'inactive') }}
                                </span>
                            </td>

                            <td>
                                <span class="admin-service-badge {{ $documentStatusClass($documentStatus) }}">
                                    <i></i>
                                    {{ $statusLabel($documentStatus) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="admin-service-empty">
                                <div>
                                    <span>
                                        <svg viewBox="0 0 24 24">
                                            <path d="M4 7h16"></path>
                                            <path d="M4 12h16"></path>
                                            <path d="M4 17h16"></path>
                                        </svg>
                                    </span>

                                    <strong>Belum ada data service.</strong>
                                    <p>Service dari provider akan tampil di sini.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="admin-service-footer">
            <p>
                Showing {{ $services->firstItem() ?? 0 }} to {{ $services->lastItem() ?? 0 }} of {{ $services->total() }} entries
            </p>

            <div class="admin-service-pagination">
                {{ $services->links() }}
            </div>
        </div>
    </div>
</section>
@endsection