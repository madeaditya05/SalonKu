@extends('provider.layouts.dashboard')

@section('title', 'Roles & Permissions - Provider Dashboard')
@section('page_title', 'Roles & Permissions')
@section('page_subtitle', 'Buat akun cabang dan atur akses menu dashboard provider.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/roles-permissions.css') }}">
@endpush

@section('content')
@php
    $roles = $roles ?? collect();
    $branches = $branches ?? collect();
    $branchAccounts = $branchAccounts ?? collect();
    $menuSections = $menuSections ?? [];
    $oldMenuKeys = collect(old('menu_keys', []))->map(fn ($key) => (string) $key)->all();
@endphp

<section class="roles-permissions-page">
    <div class="roles-header">
        <div>
            <h1>Roles & Permissions</h1>

            <div class="roles-breadcrumb">
                <span>Dashboard</span>
                <span>></span>
                <strong>Akun Cabang</strong>
            </div>
        </div>

        <button type="button" class="roles-primary-btn" id="roleResetBtn">
            <svg viewBox="0 0 24 24">
                <path d="M12 5v14"/>
                <path d="M5 12h14"/>
            </svg>
            New Branch Account
        </button>
    </div>

    @if (session('success'))
        <div class="roles-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="roles-alert error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="roles-alert error">
            Ada data yang belum valid. Silakan cek form akun cabang kembali.
        </div>
    @endif

    <div class="roles-layout">
        <div class="roles-list-panel">
            <div class="role-panel-head">
                <div>
                    <span>Access accounts</span>
                    <h2>Branch Accounts</h2>
                </div>

                <strong>{{ $roles->count() }}</strong>
            </div>

            <div class="role-account-table-wrap">
                <table class="role-account-table">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th>Branch</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Menu Access</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($roles as $role)
                            @php
                                $permissionKeys = $role->menuPermissions->pluck('menu_key')->values()->all();
                                $account = $role->users->first();
                                $accountName = $account?->name ?? $role->role_name;
                                $accountInitial = strtoupper(substr($accountName ?: 'B', 0, 1));
                                $rolePayload = [
                                    'id' => $role->id,
                                    'role_name' => $role->role_name,
                                    'account_name' => $account?->name,
                                    'account_email' => $account?->email,
                                    'branch_id' => $role->branch_id,
                                    'description' => $role->description,
                                    'status' => $role->status,
                                    'menu_keys' => $permissionKeys,
                                    'update_url' => provider_route('provider.roles-permissions.update', $role->id),
                                ];
                            @endphp

                            <tr>
                                <td>
                                    <div class="role-account-cell">
                                        <span class="role-account-avatar">{{ $accountInitial }}</span>
                                        <div>
                                            <strong>{{ $accountName }}</strong>
                                            <small>{{ $account?->email ?? 'Email belum dibuat' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>{{ $role->branch->branch_name ?? 'Branch belum dipilih' }}</td>
                                <td>
                                    <strong>{{ $role->role_name }}</strong>
                                    @if ($role->description)
                                        <small>{{ $role->description }}</small>
                                    @endif
                                </td>
                                <td>
                                    <span class="role-status {{ $role->status }}">{{ ucfirst($role->status) }}</span>
                                </td>
                                <td>
                                    <div class="role-permission-tags compact">
                                        @forelse (array_slice($permissionKeys, 0, 5) as $permissionKey)
                                            <span>{{ $menuLabels[$permissionKey] ?? $permissionKey }}</span>
                                        @empty
                                            <span>No menu access</span>
                                        @endforelse

                                        @if (count($permissionKeys) > 5)
                                            <span>+{{ count($permissionKeys) - 5 }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <div class="role-table-actions">
                                        <button type="button" class="roles-secondary-btn role-edit-btn" data-role='@json($rolePayload)'>
                                            <svg viewBox="0 0 24 24">
                                                <path d="M12 20h9"/>
                                                <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                            </svg>
                                            Edit
                                        </button>

                                        <form action="{{ provider_route('provider.roles-permissions.destroy', $role->id) }}" method="POST">
                                            @csrf
                                            @method('DELETE')

                                            <button type="submit" class="roles-danger-btn" data-confirm-delete>
                                                <svg viewBox="0 0 24 24">
                                                    <path d="M3 6h18"/>
                                                    <path d="M8 6V4h8v2"/>
                                                    <path d="M19 6l-1 14H6L5 6"/>
                                                </svg>
                                                Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6">
                                    <div class="roles-empty">
                                        <strong>Belum ada akun cabang.</strong>
                                        <span>Buat akun cabang pertama lalu pilih menu yang boleh dibuka.</span>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div
        class="role-modal-overlay"
        id="roleModal"
        data-open-on-errors="{{ $errors->any() ? '1' : '0' }}"
        aria-hidden="true"
    >
        <div class="role-modal" role="dialog" aria-modal="true" aria-labelledby="roleFormTitle">
            <div class="role-modal-header">
                <div>
                    <span>Branch account</span>
                    <h2 id="roleFormTitle">Create Branch Account</h2>
                    <p>Lengkapi akun cabang lalu pilih menu yang boleh dibuka.</p>
                </div>

                <div class="role-modal-header-actions">
                    <span class="role-form-state" id="roleFormState">New</span>
                    <button type="button" class="role-modal-close" id="roleModalClose" aria-label="Close modal">&times;</button>
                </div>
            </div>

        <form
            action="{{ provider_route('provider.roles-permissions.store') }}"
            method="POST"
            class="role-form-panel"
            id="roleForm"
            data-store-url="{{ provider_route('provider.roles-permissions.store') }}"
        >
            @csrf
            <input type="hidden" name="_method" id="roleFormMethod" value="PUT" disabled>

            <div class="role-modal-body">
                <div class="role-form-grid account">
                    <div class="role-field">
                        <label for="accountNameInput">Account Name <span>*</span></label>
                        <input
                            type="text"
                            name="account_name"
                            id="accountNameInput"
                            value="{{ old('account_name') }}"
                            placeholder="Contoh: Admin Cabang Bandung"
                            required
                        >
                        @error('account_name') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="role-field">
                        <label for="accountEmailInput">Login Email <span>*</span></label>
                        <input
                            type="email"
                            name="account_email"
                            id="accountEmailInput"
                            value="{{ old('account_email') }}"
                            placeholder="bandung@provider.test"
                            required
                        >
                        @error('account_email') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="role-field">
                        <label for="accountPasswordInput">Password <span id="accountPasswordRequired">*</span></label>
                        <input
                            type="password"
                            name="account_password"
                            id="accountPasswordInput"
                            placeholder="Minimal 8 karakter"
                        >
                        @error('account_password') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="role-form-grid">
                    <div class="role-field">
                        <label for="roleNameInput">Role Name <span>*</span></label>
                        <input
                            type="text"
                            name="role_name"
                            id="roleNameInput"
                            value="{{ old('role_name') }}"
                            placeholder="Contoh: Manager Cabang"
                            required
                        >
                        @error('role_name') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="role-field">
                        <label for="roleBranchSelect">Branch <span>*</span></label>
                        <select name="branch_id" id="roleBranchSelect" required>
                            <option value="">Pilih branch</option>

                            @foreach ($branches as $branch)
                                <option value="{{ $branch->id }}" @selected((string) old('branch_id') === (string) $branch->id)>
                                    {{ $branch->branch_name }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="role-field">
                        <label for="roleStatusSelect">Role Status <span>*</span></label>
                        <select name="status" id="roleStatusSelect" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                        @error('status') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="role-field full">
                    <label for="roleDescriptionInput">Description</label>
                    <textarea
                        name="description"
                        id="roleDescriptionInput"
                        placeholder="Catatan singkat untuk akun cabang ini"
                    >{{ old('description') }}</textarea>
                    @error('description') <small>{{ $message }}</small> @enderror
                </div>

                <div class="role-section-head">
                    <div>
                        <h3>Menu Access</h3>
                        <p>Pilih menu dashboard yang boleh dibuka akun cabang ini.</p>
                    </div>

                    <button type="button" class="roles-mini-btn" id="roleSelectAllMenus">Select all</button>
                </div>

                <div class="role-permission-list">
                    @foreach ($menuSections as $section)
                        <div class="role-permission-group">
                            <div class="role-permission-group-head">
                                <div>
                                    <strong>{{ $section['title'] }}</strong>
                                    <span>{{ count($section['items']) }} menu</span>
                                </div>

                                <button type="button" data-section-toggle>Pilih</button>
                            </div>

                            <div class="role-permission-options">
                                @foreach ($section['items'] as $item)
                                    @php
                                        $menuKey = $item['key'];
                                        $inputId = 'role-menu-' . $menuKey;
                                    @endphp

                                    <label class="role-permission-option" for="{{ $inputId }}">
                                        <input
                                            type="checkbox"
                                            name="menu_keys[]"
                                            value="{{ $menuKey }}"
                                            id="{{ $inputId }}"
                                            @checked(in_array($menuKey, $oldMenuKeys, true))
                                        >

                                        <span class="role-permission-option-text">
                                            <strong>{{ $item['label'] }}</strong>
                                            <small>{{ $item['description'] }}</small>
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                @error('menu_keys') <small class="role-error-block">{{ $message }}</small> @enderror
                @error('menu_keys.*') <small class="role-error-block">{{ $message }}</small> @enderror
            </div>

            <div class="role-form-actions">
                <button type="button" class="roles-secondary-btn" id="roleCancelEditBtn">Cancel</button>
                <button type="submit" class="roles-primary-btn" id="roleSubmitBtn">Save Branch Account</button>
            </div>
        </form>
        </div>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/roles-permissions.js') }}"></script>
@endpush
