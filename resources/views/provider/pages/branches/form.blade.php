@extends('provider.layouts.dashboard')

@section('title', ($mode ?? 'create') === 'edit' ? 'Edit Branch - Provider Dashboard' : 'Add Branch - Provider Dashboard')
@section('page_title', ($mode ?? 'create') === 'edit' ? 'Edit Branch' : 'Add Branch')
@section('page_subtitle', 'Complete branch details and choose staff for this branch.')

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
        ? provider_route('provider.branch.edit', $branch->id)
        : provider_route('provider.branch.create');

    $staffTabUrl = $isEdit
        ? provider_route('provider.branch.edit', ['branch' => $branch->id, 'step' => 'staff'])
        : provider_route('provider.branch.create', ['step' => 'staff']);

    $branchFormAction = $isEdit
        ? provider_route('provider.branch.update', $branch->id)
        : provider_route('provider.branch.continue');

    $staffFormAction = $isEdit
        ? provider_route('provider.branch.staff.update', $branch->id)
        : provider_route('provider.branch.store');

    $branchImage = $getValue('image');
    $branchImageUrl = null;

    if ($branchImage) {
        $branchImageUrl = \Illuminate\Support\Str::startsWith($branchImage, ['http://', 'https://'])
            ? $branchImage
            : asset(\Illuminate\Support\Str::startsWith($branchImage, 'storage/') ? $branchImage : 'storage/' . ltrim($branchImage, '/'));
    }

    $selectedCountry = $getValue('country_id', 'Indonesia');
    $selectedState = $getValue('state_id');
    $selectedCity = $getValue('city_id');
    $selectedPhoneCode = $getValue('phone_code', '+62');
    $selectedLatitude = $getValue('latitude');
    $selectedLongitude = $getValue('longitude');
    $workingDayOptions = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $selectedStaffCount = count($selectedStaffs);
    $availableStaffCount = isset($staffs) ? $staffs->count() : 0;
    $activeStepNumber = $activeBranchTab ? '1 / 2' : '2 / 2';
@endphp

<section class="admin-category-page admin-booking-page provider-branch-form-page provider-branch-editor-page">
    <div class="admin-booking-route admin-category-route provider-branch-form-route">
        <div class="admin-breadcrumb">
            <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
            <span>&rsaquo;</span>
            <a href="{{ provider_route('provider.branch.index') }}">Branch</a>
            <span>&rsaquo;</span>
            <strong>{{ $isEdit ? 'Edit' : 'Create' }}</strong>
        </div>

        <a href="{{ provider_route('provider.branch.index') }}" class="admin-category-add-button provider-branch-form-back">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="m12 19-7-7 7-7"/>
                <path d="M19 12H5"/>
            </svg>
            Back to Branch
        </a>
    </div>

    <div class="provider-branch-form-heading">
        <div>
            <span>{{ $isEdit ? 'Branch editor' : 'New branch setup' }}</span>
            <h1>{{ $isEdit ? 'Edit Branch' : 'Create Branch' }}</h1>
            <p>Complete branch details, operating schedule, photo, and assigned staff.</p>
        </div>

        <div class="provider-branch-step-indicator">
            <span>Step</span>
            <strong>{{ $activeStepNumber }}</strong>
        </div>
    </div>

    @if (session('success'))
        <div class="admin-booking-alert success">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="admin-booking-alert danger">
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="admin-booking-alert danger">
            Some fields are invalid. Please check the form again.
        </div>
    @endif

    <div class="admin-booking-tabs provider-branch-form-tabs">
        <a href="{{ $branchTabUrl }}" class="admin-booking-tab {{ $activeBranchTab ? 'active' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M4 21V5a2 2 0 0 1 2-2h10v18"/>
                <path d="M16 8h2a2 2 0 0 1 2 2v11"/>
                <path d="M8 7h4M8 11h4M8 15h4"/>
            </svg>
            Branch Information
        </a>

        <a href="{{ $staffTabUrl }}" class="admin-booking-tab {{ $activeStaffTab ? 'active' : '' }} {{ !$isEdit && empty($draft) ? 'disabled' : '' }}">
            <svg viewBox="0 0 24 24" aria-hidden="true">
                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                <circle cx="9" cy="7" r="4"/>
                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
            </svg>
            Add Staff
        </a>
    </div>

    @if ($activeBranchTab)
        <form action="{{ $branchFormAction }}" method="POST" enctype="multipart/form-data" class="provider-branch-editor-form">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="admin-booking-card branch-form-card provider-branch-form-card">
                <div class="provider-branch-form-layout">
                    <div class="provider-branch-form-main">
                        <section class="provider-branch-form-section">
                            <div class="provider-branch-section-head">
                                <span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M4 21V5a2 2 0 0 1 2-2h10v18"/>
                                        <path d="M16 8h2a2 2 0 0 1 2 2v11"/>
                                        <path d="M8 7h4M8 11h4M8 15h4"/>
                                    </svg>
                                </span>

                                <div>
                                    <h2>Branch Details</h2>
                                    <p>Branch name, contact details, and primary address.</p>
                                </div>
                            </div>

                            <div class="branch-form-grid two provider-branch-field-grid">
                                <div class="branch-form-group">
                                    <label>Branch Name <span>*</span></label>
                                    <input type="text" name="branch_name" placeholder="Enter branch name" value="{{ $getValue('branch_name') }}">
                                    @error('branch_name') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group">
                                    <label>Email <span>*</span></label>
                                    <input type="email" name="email" placeholder="branch@email.com" value="{{ $getValue('email') }}">
                                    @error('email') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group full">
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
                                            placeholder="Enter phone number"
                                            value="{{ $getValue('phone_number') }}"
                                        >
                                    </div>

                                    @error('phone_code') <small>{{ $message }}</small> @enderror
                                    @error('phone_number') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group full">
                                    <label>Address <span>*</span></label>
                                    <input type="text" name="address" placeholder="Enter full address" value="{{ $getValue('address') }}">
                                    @error('address') <small>{{ $message }}</small> @enderror
                                </div>
                            </div>
                        </section>

                        <section class="provider-branch-form-section">
                            <div class="provider-branch-section-head">
                                <span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 21s7-4.35 7-11a7 7 0 1 0-14 0c0 6.65 7 11 7 11z"/>
                                        <circle cx="12" cy="10" r="2.5"/>
                                    </svg>
                                </span>

                                <div>
                                    <h2>Location</h2>
                                    <p>Branch region and optional coordinates for the customer catalog.</p>
                                </div>
                            </div>

                            <div class="branch-form-grid three provider-branch-field-grid">
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
                                    <input type="text" name="zip_code" placeholder="Enter ZIP code" value="{{ $getValue('zip_code') }}">
                                    @error('zip_code') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group">
                                    <label>Latitude</label>
                                    <input
                                        type="number"
                                        name="latitude"
                                        id="branchLatitudeInput"
                                        step="0.0000001"
                                        min="-90"
                                        max="90"
                                        placeholder="-6.2000000"
                                        value="{{ $selectedLatitude }}"
                                    >
                                    @error('latitude') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group">
                                    <label>Longitude</label>
                                    <input
                                        type="number"
                                        name="longitude"
                                        id="branchLongitudeInput"
                                        step="0.0000001"
                                        min="-180"
                                        max="180"
                                        placeholder="106.8166660"
                                        value="{{ $selectedLongitude }}"
                                    >
                                    @error('longitude') <small>{{ $message }}</small> @enderror
                                </div>

                                <div class="branch-form-group full branch-location-helper provider-branch-location-helper">
                                    <button type="button" id="branchUseCurrentLocation" class="branch-location-btn">
                                        Use My Current Position
                                    </button>
                                    <small>Coordinates help customers find the nearest branch.</small>
                                </div>
                            </div>
                        </section>

                        <section class="provider-branch-form-section">
                            <div class="provider-branch-section-head">
                                <span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 7v5l3 2"/>
                                    </svg>
                                </span>

                                <div>
                                    <h2>Operational Schedule</h2>
                                    <p>Opening hours, working days, and branch holidays.</p>
                                </div>
                            </div>

                            <div class="branch-form-grid two provider-branch-field-grid">
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

                            <div class="branch-form-group full provider-branch-days-field">
                                <label>Working Day <span>*</span></label>

                                <div class="branch-days-list provider-branch-day-grid">
                                    @foreach ($workingDayOptions as $day)
                                        <label>
                                            <input type="checkbox" name="working_days[]" value="{{ $day }}" {{ in_array($day, $workingDays) ? 'checked' : '' }}>
                                            {{ $day }}
                                        </label>
                                    @endforeach
                                </div>

                                @error('working_days') <small>{{ $message }}</small> @enderror
                            </div>

                            <div class="branch-form-group full provider-branch-holiday-field">
                                <label>Holiday</label>

                                <div id="holidayWrapper" class="provider-branch-holiday-list">
                                    @foreach ($holidays as $holiday)
                                        <div class="branch-holiday-row">
                                            <input type="date" name="holidays[]" value="{{ is_array($holiday) ? ($holiday['date'] ?? '') : $holiday }}">
                                            <button type="button" class="remove-holiday-btn" aria-label="Remove holiday" title="Remove holiday">
                                                <svg viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M18 6 6 18"/>
                                                    <path d="m6 6 12 12"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>

                                <button type="button" class="branch-add-holiday" id="addHolidayBtn">
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <path d="M12 5v14"/>
                                        <path d="M5 12h14"/>
                                    </svg>
                                    <span>Add Holiday</span>
                                </button>
                            </div>
                        </section>
                    </div>

                    <aside class="provider-branch-form-aside">
                        <section class="provider-branch-form-section provider-branch-media-section">
                            <div class="provider-branch-section-head compact">
                                <span>
                                    <svg viewBox="0 0 24 24" aria-hidden="true">
                                        <rect x="4" y="5" width="16" height="14" rx="2"/>
                                        <path d="m8 15 2.5-3 2 2.5L16 10l4 5"/>
                                        <circle cx="8" cy="8" r="1"/>
                                    </svg>
                                </span>

                                <div>
                                    <h2>Branch Image</h2>
                                    <p>Main photo for the customer catalog.</p>
                                </div>
                            </div>

                            <div class="branch-form-group full provider-branch-upload-field">
                                <label>Image <span>*</span></label>

                                <label for="branchImageInput" class="branch-image-upload">
                                    @if ($branchImageUrl)
                                        <img src="{{ $branchImageUrl }}" alt="Branch Image" id="branchImagePreview">
                                        <span id="branchImagePlaceholder" class="hidden">
                                            Upload<br>
                                            Image
                                        </span>
                                    @else
                                        <img src="" alt="Branch Image" id="branchImagePreview" class="hidden">
                                        <span id="branchImagePlaceholder">
                                            Upload<br>
                                            Image
                                        </span>
                                    @endif
                                </label>

                                <input type="file" name="image" id="branchImageInput" accept="image/*" hidden>

                                @error('image') <small>{{ $message }}</small> @enderror
                            </div>

                            <div class="provider-branch-media-note">
                                <span>JPG, PNG, WEBP</span>
                                <strong>Max 2 MB</strong>
                            </div>
                        </section>
                    </aside>
                </div>

                <div class="branch-form-actions">
                    <a href="{{ provider_route('provider.branch.index') }}" class="branch-back-btn">
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
        <form action="{{ $staffFormAction }}" method="POST" class="provider-branch-editor-form">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="admin-booking-card branch-form-card provider-branch-form-card provider-branch-staff-card">
                <section class="provider-branch-form-section">
                    <div class="provider-branch-section-head">
                        <span>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                            </svg>
                        </span>

                        <div>
                            <h2>Add Staff</h2>
                            <p>Select the staff assigned to this branch.</p>
                        </div>
                    </div>

                    <div class="provider-branch-staff-overview">
                        <div>
                            <span>Available Staff</span>
                            <strong>{{ number_format($availableStaffCount) }}</strong>
                        </div>

                        <div>
                            <span>Selected</span>
                            <strong>{{ number_format($selectedStaffCount) }}</strong>
                        </div>
                    </div>

                    <div class="branch-form-group full provider-branch-staff-field">
                        <label>Staffs</label>

                        <div class="branch-staff-multiselect" id="branchStaffMultiselect">
                            <button type="button" class="branch-staff-control" id="branchStaffControl" tabindex="0">
                                <div class="branch-staff-tags" id="branchStaffTags">
                                    <span class="branch-staff-placeholder" id="branchStaffPlaceholder">Select Staff</span>
                                </div>

                                <span class="branch-staff-arrow" aria-hidden="true">
                                    <svg viewBox="0 0 24 24">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </span>
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
                </section>

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
