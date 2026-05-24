@extends('admin.layouts.app')

@section('title', 'Users - JasaKu')
@section('page_title', 'Users')

@section('content')
@php
    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');
@endphp

<section class="users-page">
    <div class="users-page-header">
        <div>
            <h1>Users</h1>

            <div class="users-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>›</span>
                <strong>People</strong>
                <span>›</span>
                <strong>Users</strong>
            </div>
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

    <div class="users-card">
        <div class="users-toolbar">
            <form method="GET" action="{{ route('admin.users.index') }}" class="entries-box">
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
            </form>

            <form method="GET" action="{{ route('admin.users.index') }}" class="search-box">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="users-search-input">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search user"
                    >
                </div>
            </form>
        </div>

        <div class="users-table-wrap">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>Name <span class="sort-icon">↕</span></th>
                        <th>Email <span class="sort-icon">↕</span></th>
                        <th>Phone Number <span class="sort-icon">↕</span></th>
                        <th>Status <span class="sort-icon">↕</span></th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $customer)
                        @php
                            $profile = $customer->customerProfile;
                            $status = $profile->status ?? 'active';

                            $initial = strtoupper(substr($customer->name ?? 'U', 0, 1));

                            $avatarUrl = null;

                            if ($profile && $profile->avatar) {
                                $avatarUrl = filter_var($profile->avatar, FILTER_VALIDATE_URL)
                                    ? $profile->avatar
                                    : asset('storage/' . $profile->avatar);
                            }
                        @endphp

                        <tr>
                            <td>
                                <div class="user-name-box">
                                    <div class="user-avatar">
                                        @if ($avatarUrl)
                                            <img src="{{ $avatarUrl }}" alt="{{ $customer->name }}">
                                        @else
                                            {{ $initial }}
                                        @endif
                                    </div>

                                    <div class="user-name-text">
                                        <strong>{{ $customer->name }}</strong>
                                        <small>{{ $customer->username ?? 'Customer account' }}</small>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $customer->email }}</td>

                            <td>{{ $profile->phone_number ?? '-' }}</td>

                            <td>
                                <form
                                    action="{{ route('admin.users.toggle-status', $customer->id) }}"
                                    method="POST"
                                    class="account-toggle-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="account-toggle {{ $status === 'active' ? 'active' : '' }}"
                                        title="Toggle user status"
                                    >
                                        <span></span>
                                    </button>
                                </form>
                            </td>

                            <td>
                                <div class="user-actions">
                                    <a
                                        href="{{ route('admin.users.show', $customer->id) }}"
                                        class="user-action-btn"
                                        title="View"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M2.5 12C4.5 7.8 8 5.5 12 5.5C16 5.5 19.5 7.8 21.5 12C19.5 16.2 16 18.5 12 18.5C8 18.5 4.5 16.2 2.5 12Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </a>

                                    <form
                                        action="{{ route('admin.users.destroy', $customer->id) }}"
                                        method="POST"
                                        data-delete-form
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="user-action-btn danger"
                                            title="Delete"
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
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                Belum ada data user.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-info">
                Showing {{ $users->firstItem() ?? 0 }} to {{ $users->lastItem() ?? 0 }} of {{ $users->total() }} entries
            </div>

            <div class="pagination-wrap users-pagination">
                {{ $users->links() }}
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/users.js') }}"></script>
@endpush
