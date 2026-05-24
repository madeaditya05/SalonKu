@extends('admin.layouts.app')

@section('title', 'Providers - JasaKu')
@section('page_title', 'Providers')

@section('content')
@php
    $perPage = request('per_page', $perPage ?? 10);
    $search = request('search', $search ?? '');
@endphp

<section class="providers-page">
    <div class="providers-page-header">
        <div>
            <h1>Providers</h1>

            <div class="providers-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>›</span>
                <strong>People</strong>
                <span>›</span>
                <strong>Providers</strong>
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

    <div class="providers-card">
        <div class="providers-toolbar">
            <form method="GET" action="{{ route('admin.providers.index') }}" class="entries-box">
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

            <form method="GET" action="{{ route('admin.providers.index') }}" class="search-box">
                <input type="hidden" name="per_page" value="{{ $perPage }}">

                <div class="providers-search-input">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>

                    <input
                        type="text"
                        name="search"
                        value="{{ $search ?? '' }}"
                        placeholder="Search provider"
                    >
                </div>
            </form>
        </div>

        <div class="providers-table-wrap">
            <table class="providers-table">
                <thead>
                    <tr>
                        <th>Name <span class="sort-icon">↕</span></th>
                        <th>Email <span class="sort-icon">↕</span></th>
                        <th>Phone Number <span class="sort-icon">↕</span></th>
                        <th>Category <span class="sort-icon">↕</span></th>
                        <th>Account Status <span class="sort-icon">↕</span></th>
                        <th>Document Status <span class="sort-icon">↕</span></th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($providers as $provider)
                        @php
                            $profile = $provider->providerProfile;
                            $branchAccounts = $provider->branchAccounts ?? collect();
                            $accountStatus = $profile->status ?? 'inactive';
                            $documentStatus = $profile->document_status ?? 'pending';
                            $initial = strtoupper(substr($provider->name ?? 'P', 0, 1));
                        @endphp

                        <tr class="provider-parent-row">
                            <td>
                                <div class="provider-name-box">
                                    <button
                                        type="button"
                                        class="provider-expand-btn"
                                        data-provider-toggle="{{ $provider->id }}"
                                        aria-expanded="false"
                                        aria-controls="providerBranches-{{ $provider->id }}"
                                        @disabled($branchAccounts->isEmpty())
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M9 6l6 6-6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </button>

                                    <div class="provider-avatar">
                                        {{ $initial }}
                                    </div>

                                    <div class="provider-name-text">
                                        <strong>{{ $provider->name }}</strong>
                                        <small>{{ $provider->username ?? 'Provider account' }}</small>
                                        <span class="provider-branch-count">
                                            {{ $branchAccounts->count() }} branch account
                                        </span>
                                    </div>
                                </div>
                            </td>

                            <td>{{ $provider->email }}</td>
                            <td>{{ $profile->phone_number ?? '-' }}</td>
                            <td>{{ $profile->category ?? '-' }}</td>

                            <td>
                                <form
                                    action="{{ route('admin.providers.toggle-status', $provider->id) }}"
                                    method="POST"
                                    class="account-toggle-form"
                                >
                                    @csrf
                                    @method('PATCH')

                                    <button
                                        type="submit"
                                        class="account-toggle {{ $accountStatus === 'active' ? 'active' : '' }}"
                                        title="Toggle account status"
                                    >
                                        <span></span>
                                    </button>
                                </form>
                            </td>

                            <td>
                                <form action="{{ route('admin.providers.document-status', $provider->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')

                                    <select
                                        name="document_status"
                                        class="document-status-select {{ $documentStatus }}"
                                        onchange="this.form.submit()"
                                    >
                                        <option value="pending" {{ $documentStatus === 'pending' ? 'selected' : '' }}>Pending</option>
                                        <option value="submitted" {{ $documentStatus === 'submitted' ? 'selected' : '' }}>Submitted</option>
                                        <option value="verified" {{ $documentStatus === 'verified' ? 'selected' : '' }}>Verified</option>
                                        <option value="rejected" {{ $documentStatus === 'rejected' ? 'selected' : '' }}>Rejected</option>
                                    </select>
                                </form>
                            </td>

                            <td>
                                <div class="provider-actions">
                                    <a
                                        href="{{ route('admin.providers.show', $provider->id) }}"
                                        class="provider-action-btn"
                                        title="View"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none">
                                            <path d="M2.5 12C4.5 7.8 8 5.5 12 5.5C16 5.5 19.5 7.8 21.5 12C19.5 16.2 16 18.5 12 18.5C8 18.5 4.5 16.2 2.5 12Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </a>

                                    <form
                                        action="{{ route('admin.providers.destroy', $provider->id) }}"
                                        method="POST"
                                        data-delete-form
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            class="provider-action-btn danger"
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

                        <tr class="provider-branches-row" id="providerBranches-{{ $provider->id }}" hidden>
                            <td colspan="7">
                                <div class="provider-branch-panel">
                                    <div class="provider-branch-panel-head">
                                        <div>
                                            <strong>Branch Accounts</strong>
                                            <span>{{ $provider->name }}</span>
                                        </div>

                                        <span>{{ $branchAccounts->count() }} akun</span>
                                    </div>

                                    @if ($branchAccounts->isEmpty())
                                        <div class="provider-branch-empty">
                                            Provider pusat ini belum punya akun branch.
                                        </div>
                                    @else
                                        <div class="provider-branch-list">
                                            <div class="provider-branch-line head">
                                                <span>Account</span>
                                                <span>Branch</span>
                                                <span>Role</span>
                                                <span>Menu</span>
                                                <span>Status</span>
                                            </div>

                                            @foreach ($branchAccounts as $account)
                                                @php
                                                    $branchName = $account->providerBranch->branch_name ?? 'Branch belum dipilih';
                                                    $roleName = $account->providerRole->role_name ?? 'Role belum dipilih';
                                                    $roleStatus = $account->providerRole->status ?? 'inactive';
                                                    $menuCount = $account->providerRole?->menuPermissions?->count() ?? 0;
                                                @endphp

                                                <div class="provider-branch-line">
                                                    <div class="provider-branch-account">
                                                        <strong>{{ $account->name }}</strong>
                                                        <small>{{ $account->email }}</small>
                                                    </div>

                                                    <span>{{ $branchName }}</span>
                                                    <span>{{ $roleName }}</span>
                                                    <span>{{ $menuCount }} menu</span>
                                                    <span class="provider-branch-status {{ $roleStatus }}">
                                                        {{ ucfirst($roleStatus) }}
                                                    </span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="empty-state">
                                Belum ada data provider.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="table-footer">
            <div class="table-info">
                Showing {{ $providers->firstItem() ?? 0 }} to {{ $providers->lastItem() ?? 0 }} of {{ $providers->total() }} entries
            </div>

            <div class="pagination-wrap providers-pagination">
                {{ $providers->links() }}
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('admin/js/providers.js') }}"></script>
@endpush
