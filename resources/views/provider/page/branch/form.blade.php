@extends('provider.layouts.dashboard')

@section('title', ($mode ?? 'create') === 'edit' ? 'Edit Branch - Provider Dashboard' : 'Add Branch - Provider Dashboard')
@section('page_title', ($mode ?? 'create') === 'edit' ? 'Edit Branch' : 'Add Branch')
@section('page_subtitle', 'Lengkapi informasi branch dan pilih staff untuk branch ini.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/branch.css') }}">
@endpush

@section('content')
@php
    $mode = $mode ?? 'create';
    $branch = $branch ?? null;
    $step = $step ?? 'branch';
    $draft = $draft ?? [];
    $staffDraft = $staffDraft ?? [];

    $isEdit = $mode === 'edit';

    $activeBranchTab = $step === 'branch';
    $activeStaffTab = $step === 'staff';

    $getValue = function ($field, $default = '') use ($draft, $branch) {
        if (old($field) !== null) {
            return old($field);
        }

        if ($branch && isset($branch->{$field})) {
            return $branch->{$field};
        }

        return $draft[$field] ?? $default;
    };

    $workingDays = old('working_days', $getValue('working_days', []));
    $holidays = old('holidays', $getValue('holidays', []));

    $workingDays = is_array($workingDays) ? $workingDays : [];
    $holidays = is_array($holidays) ? $holidays : [];

    if (empty($holidays)) {
        $holidays = [''];
    }

    $selectedStaffs = old('staff_ids', $staffDraft['staff_ids'] ?? []);

    if (empty($selectedStaffs) && $branch && isset($branch->staffs)) {
        $selectedStaffs = $branch->staffs->pluck('id')->toArray();
    }

    $selectedStaffs = is_array($selectedStaffs) ? array_map('strval', $selectedStaffs) : [];

    $branchTabUrl = $isEdit
        ? route('provider.branch.edit', $branch->id)
        : route('provider.branch.create');

    $staffTabUrl = $isEdit
        ? route('provider.branch.edit', ['branch' => $branch->id, 'step' => 'staff'])
        : route('provider.branch.create', ['step' => 'staff']);

    $branchFormAction = $isEdit
        ? route('provider.branch.update', $branch->id)
        : route('provider.branch.continue');

    $staffFormAction = $isEdit
        ? route('provider.branch.staff.update', $branch->id)
        : route('provider.branch.store');

    $branchImage = $getValue('image');

    $selectedCountry = $getValue('country_id', 'Indonesia');
    $selectedState = $getValue('state_id');
    $selectedCity = $getValue('city_id');
    $selectedPhoneCode = $getValue('phone_code', '+62');
@endphp

<section class="provider-branch-form-page">
    <div class="branch-form-header">
        <div>
            <h1>{{ $isEdit ? 'Edit Branch' : 'Create Branch' }}</h1>

            <div class="branch-breadcrumb">
                <span>Dashboard</span>
                <span>›</span>
                <span>Branch</span>
                <span>›</span>
                <strong>{{ $isEdit ? 'Edit' : 'Create' }}</strong>
            </div>
        </div>

        <a href="{{ route('provider.branch.index') }}" class="branch-header-back">
            <svg viewBox="0 0 24 24">
                <path d="m12 19-7-7 7-7"/>
                <path d="M19 12H5"/>
            </svg>
            Back
        </a>
    </div>

    <div class="branch-tabs">
        <a href="{{ $branchTabUrl }}" class="branch-tab {{ $activeBranchTab ? 'active' : '' }}">
            <svg viewBox="0 0 24 24">
                <path d="M4 21V5a2 2 0 0 1 2-2h10v18"/>
                <path d="M16 8h2a2 2 0 0 1 2 2v11"/>
                <path d="M8 7h4M8 11h4M8 15h4"/>
            </svg>
            Branch Information
        </a>

        <a href="{{ $staffTabUrl }}" class="branch-tab {{ $activeStaffTab ? 'active' : '' }} {{ !$isEdit && empty($draft) ? 'disabled' : '' }}">
            <svg viewBox="0 0 24 24">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Add Staff
        </a>
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

    @if ($errors->any())
        <div class="branch-alert error">
            Ada data yang belum valid. Silakan cek form kembali.
        </div>
    @endif

    @if ($activeBranchTab)
        <form action="{{ $branchFormAction }}" method="POST" enctype="multipart/form-data">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="branch-form-card">
                <div class="branch-card-header">
                    <h2>Branch Information</h2>
                </div>

                <div class="branch-form-grid two">
                    <div class="branch-form-group">
                        <label>Branch Name <span>*</span></label>
                        <input type="text" name="branch_name" placeholder="Enter Branch Name" value="{{ $getValue('branch_name') }}">
                        @error('branch_name') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Phone Number <span>*</span></label>

                        <div class="branch-phone-row">
                            <select
                                name="phone_code"
                                id="branchPhoneCodeSelect"
                                data-selected="{{ $selectedPhoneCode }}"
                            >
                                <option value="">Loading codes...</option>
                            </select>

                            <input
                                type="text"
                                name="phone_number"
                                placeholder="Enter Phone Number"
                                value="{{ $getValue('phone_number') }}"
                            >
                        </div>

                        @error('phone_code') <small>{{ $message }}</small> @enderror
                        @error('phone_number') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" placeholder="Enter Email" value="{{ $getValue('email') }}">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Address <span>*</span></label>
                        <input type="text" name="address" placeholder="Enter Address" value="{{ $getValue('address') }}">
                        @error('address') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Country <span>*</span></label>

                        <select
                            name="country_id"
                            id="countrySelect"
                            data-selected="{{ $selectedCountry }}"
                        >
                            <option value="">Loading countries...</option>
                        </select>

                        @error('country_id') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>State <span>*</span></label>

                        <select
                            name="state_id"
                            id="stateSelect"
                            data-selected="{{ $selectedState }}"
                        >
                            <option value="">Select Country First</option>
                        </select>

                        @error('state_id') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>City <span>*</span></label>

                        <select
                            name="city_id"
                            id="citySelect"
                            data-selected="{{ $selectedCity }}"
                        >
                            <option value="">Select State First</option>
                        </select>

                        @error('city_id') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>ZIP Code <span>*</span></label>
                        <input type="text" name="zip_code" placeholder="Enter ZIP Code" value="{{ $getValue('zip_code') }}">
                        @error('zip_code') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Working Start Hour <span>*</span></label>
                        <input type="time" name="working_start_hour" value="{{ $getValue('working_start_hour') }}">
                        @error('working_start_hour') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="branch-form-group">
                        <label>Working End Hour <span>*</span></label>
                        <input type="time" name="working_end_hour" value="{{ $getValue('working_end_hour') }}">
                        @error('working_end_hour') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="branch-form-group full">
                    <label>Working Day <span>*</span></label>

                    <div class="branch-days-list">
                        @foreach (['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as $day)
                            <label>
                                <input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $workingDays) ? 'checked' : '' }}>
                                {{ $day }}
                            </label>
                        @endforeach
                    </div>

                    @error('working_days') <small>{{ $message }}</small> @enderror
                </div>

                <div class="branch-form-group full">
                    <label>Holiday</label>

                    <div id="holidayWrapper">
                        @foreach ($holidays as $holiday)
                            <div class="branch-holiday-row">
                                <input type="date" name="holidays[]" value="{{ is_array($holiday) ? ($holiday['date'] ?? '') : $holiday }}">
                                <button type="button" class="remove-holiday-btn">×</button>
                            </div>
                        @endforeach
                    </div>

                    <button type="button" class="branch-add-holiday" id="addHolidayBtn">
                        ⊕ Add Holiday
                    </button>
                </div>

                <div class="branch-form-group full">
                    <label>Image <span>*</span></label>

                    <label for="branchImageInput" class="branch-image-upload">
                        @if ($branchImage)
                            <img src="{{ asset('storage/' . $branchImage) }}" alt="Branch Image" id="branchImagePreview">
                            <span id="branchImagePlaceholder" class="hidden">
                                ▧<br>
                                Image
                            </span>
                        @else
                            <img src="" alt="Branch Image" id="branchImagePreview" class="hidden">
                            <span id="branchImagePlaceholder">
                                ▧<br>
                                Image
                            </span>
                        @endif
                    </label>

                    <input type="file" name="image" id="branchImageInput" accept="image/*" hidden>

                    @error('image') <small>{{ $message }}</small> @enderror
                </div>

                <div class="branch-form-actions">
                    <a href="{{ route('provider.branch.index') }}" class="branch-back-btn">
                        Back
                    </a>

                    <button type="submit" class="branch-submit-btn">
                        Continue
                    </button>
                </div>
            </div>
        </form>
    @endif

    @if ($activeStaffTab)
        <form action="{{ $staffFormAction }}" method="POST">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="branch-form-card">
                <div class="branch-card-header">
                    <h2>Add Staff</h2>
                </div>

                <div class="branch-form-group full">
                    <label>Staffs</label>

                    <div class="branch-staff-multiselect" id="branchStaffMultiselect">
                        <button type="button" class="branch-staff-control" id="branchStaffControl" tabindex="0">
                            <div class="branch-staff-tags" id="branchStaffTags">
                                <span class="branch-staff-placeholder" id="branchStaffPlaceholder">Select Staff</span>
                            </div>

                            <span class="branch-staff-arrow">⌄</span>
                        </button>

                        <div class="branch-staff-menu">
                            @forelse ($staffs as $staff)
                                @php
                                    $staffId = (string) $staff->id;
                                    $staffName = $staff->full_name ?? trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                                    $staffPhone = trim(($staff->country_code ?? '') . ($staff->phone_number ?? ''));
                                    $checkedStaff = in_array($staffId, $selectedStaffs);
                                @endphp

                                <label class="branch-staff-option">
                                    <input
                                        type="checkbox"
                                        name="staff_ids[]"
                                        value="{{ $staff->id }}"
                                        data-name="{{ $staffName }}"
                                        {{ $checkedStaff ? 'checked' : '' }}
                                    >

                                    <div class="branch-staff-avatar">
                                        @if (!empty($staff->image))
                                            <img src="{{ asset('storage/' . $staff->image) }}" alt="{{ $staffName }}">
                                        @else
                                            {{ strtoupper(substr($staffName ?: 'S', 0, 1)) }}
                                        @endif
                                    </div>

                                    <div>
                                        <strong>{{ $staffName }}</strong>
                                        <small>{{ $staffPhone ?: ($staff->email ?? 'No contact') }}</small>
                                    </div>
                                </label>
                            @empty
                                <div class="branch-staff-empty">
                                    No staff available
                                </div>
                            @endforelse
                        </div>
                    </div>

                    @error('staff_ids') <small>{{ $message }}</small> @enderror
                </div>

                <div class="branch-form-actions">
                    <a href="{{ $branchTabUrl }}" class="branch-back-btn">
                        Back
                    </a>

                    <button type="submit" class="branch-submit-btn">
                        Save
                    </button>
                </div>
            </div>
        </form>
    @endif
</section>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/branch.js') }}"></script>
@endpush