@extends('provider.layouts.dashboard')

@section('title', 'Edit Profile - Provider Dashboard')
@section('page_title', 'Edit Profile')
@section('page_subtitle', 'Edit profile provider.')

@push('styles')
    <link rel="stylesheet" href="{{ asset('provider/css/provider-profile.css') }}">
@endpush

@section('content')
@php
    $profileImage = $profile->image ? asset('storage/' . $profile->image) : null;
    $ktpImage = $profile->ktp_image ? asset('storage/' . $profile->ktp_image) : null;
    $businessImage = $profile->business_image ? asset('storage/' . $profile->business_image) : null;

    $accountStatus = $profile->status ?? 'active';
    $documentStatus = $profile->document_status ?? 'pending';
    $documentLocked = $documentStatus === 'verified';
@endphp

<section class="profile-page">
    <div class="profile-header">
        <div>
            <h1>Edit Profile</h1>
            <p>Dashboard / My Profile / Edit</p>
        </div>

        <a href="{{ route('provider.profile') }}" class="profile-secondary-btn">
            Back
        </a>
    </div>

    @if (session('success'))
        <div class="profile-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="profile-alert error">{{ session('error') }}</div>
    @endif

    @if ($errors->any())
        <div class="profile-alert error">
            Ada data yang belum valid. Silakan cek kembali.
        </div>
    @endif

    <div class="profile-edit-layout">
        <div class="profile-main">
            <form action="{{ route('provider.profile.update') }}" method="POST" enctype="multipart/form-data" class="profile-card">
                @csrf
                @method('PUT')

                <div class="profile-card-title">
                    <div>
                        <h2>Profile Information</h2>
                        <p>Ubah data utama akun provider.</p>
                    </div>
                </div>

                <div class="profile-upload-row">
                    <label for="profileImageInput" class="profile-upload-avatar">
                        @if ($profileImage)
                            <img src="{{ $profileImage }}" id="profileImagePreview" alt="Profile Image">
                            <span id="profileImagePlaceholder" class="hidden">Upload</span>
                        @else
                            <img src="" id="profileImagePreview" class="hidden" alt="Profile Image">
                            <span id="profileImagePlaceholder">Upload</span>
                        @endif
                    </label>

                    <input type="file" name="image" id="profileImageInput" accept="image/*" hidden>

                    <div>
                        <strong>Profile Photo</strong>
                        <p>JPG, PNG, atau WEBP. Maksimal 2MB.</p>
                        @error('image') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div class="profile-form-group">
                        <label>Name <span>*</span></label>
                        <input type="text" name="name" value="{{ old('name', $user->name) }}" placeholder="Enter Name">
                        @error('name') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-form-group">
                        <label>Username</label>
                        <input type="text" name="username" value="{{ old('username', $user->username) }}" placeholder="Enter Username">
                        @error('username') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-form-group">
                        <label>Email <span>*</span></label>
                        <input type="email" name="email" value="{{ old('email', $user->email) }}" placeholder="Enter Email">
                        @error('email') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-form-group">
                        <label>Phone Number</label>
                        <input type="text" name="phone_number" value="{{ old('phone_number', $profile->phone_number) }}" placeholder="Enter Phone Number">
                        @error('phone_number') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                <div class="profile-actions">
                    <a href="{{ route('provider.profile') }}" class="profile-secondary-btn">
                        Cancel
                    </a>

                    <button type="submit" class="profile-primary-btn">
                        Save Profile
                    </button>
                </div>
            </form>

            <form action="{{ route('provider.profile.documents.update') }}" method="POST" enctype="multipart/form-data" class="profile-card">
                @csrf

                <div class="profile-card-title">
                    <div>
                        <h2>Documents</h2>

                        @if ($documentLocked)
                            <p>Dokumen sudah verified dan tidak bisa dimodifikasi lagi.</p>
                        @else
                            <p>Upload dokumen verifikasi provider.</p>
                        @endif
                    </div>

                    <span class="status-badge {{ $documentStatus }}">
                        {{ ucfirst($documentStatus) }}
                    </span>
                </div>

                <div class="profile-document-grid">
                    <div class="profile-document-item">
                        <label
                            for="{{ $documentLocked ? '' : 'ktpImageInput' }}"
                            class="document-upload {{ $documentLocked ? 'locked' : '' }}"
                        >
                            @if ($ktpImage)
                                <img src="{{ $ktpImage }}" id="ktpImagePreview" alt="KTP Image">
                                <span id="ktpImagePlaceholder" class="hidden">
                                    {{ $documentLocked ? 'Verified' : 'Upload KTP' }}
                                </span>
                            @else
                                <img src="" id="ktpImagePreview" class="hidden" alt="KTP Image">
                                <span id="ktpImagePlaceholder">
                                    {{ $documentLocked ? 'Verified' : 'Upload KTP' }}
                                </span>
                            @endif

                            @if ($documentLocked)
                                <div class="document-locked-layer">
                                    Verified
                                </div>
                            @endif
                        </label>

                        <input
                            type="file"
                            name="ktp_image"
                            id="ktpImageInput"
                            accept="image/*"
                            {{ $documentLocked ? 'disabled' : '' }}
                            hidden
                        >

                        <h3>Foto KTP</h3>
                        @error('ktp_image') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-document-item">
                        <label
                            for="{{ $documentLocked ? '' : 'businessImageInput' }}"
                            class="document-upload {{ $documentLocked ? 'locked' : '' }}"
                        >
                            @if ($businessImage)
                                <img src="{{ $businessImage }}" id="businessImagePreview" alt="Business Image">
                                <span id="businessImagePlaceholder" class="hidden">
                                    {{ $documentLocked ? 'Verified' : 'Upload Usaha' }}
                                </span>
                            @else
                                <img src="" id="businessImagePreview" class="hidden" alt="Business Image">
                                <span id="businessImagePlaceholder">
                                    {{ $documentLocked ? 'Verified' : 'Upload Usaha' }}
                                </span>
                            @endif

                            @if ($documentLocked)
                                <div class="document-locked-layer">
                                    Verified
                                </div>
                            @endif
                        </label>

                        <input
                            type="file"
                            name="business_image"
                            id="businessImageInput"
                            accept="image/*"
                            {{ $documentLocked ? 'disabled' : '' }}
                            hidden
                        >

                        <h3>Foto Usaha</h3>
                        @error('business_image') <small>{{ $message }}</small> @enderror
                    </div>
                </div>

                @if ($documentLocked)
                    <div class="profile-help-box verified">
                        Dokumen sudah <b>Verified</b> oleh admin. Provider tidak dapat mengganti atau mengupload ulang dokumen.
                    </div>
                @elseif ($documentStatus === 'submitted')
                    <div class="profile-help-box">
                        Dokumen sudah <b>Submitted</b> dan sedang menunggu proses verifikasi admin.
                        Jika kamu upload ulang dokumen, status tetap menjadi <b>Submitted</b>.
                    </div>
                @elseif ($documentStatus === 'rejected')
                    <div class="profile-help-box rejected">
                        Dokumen sebelumnya ditolak. Silakan upload dokumen baru.
                        Setelah dikirim, status akan menjadi <b>Submitted</b>.
                    </div>
                @else
                    <div class="profile-help-box">
                        Setelah dokumen dikirim, status dokumen akan menjadi <b>Submitted</b>.
                    </div>
                @endif

                <div class="profile-actions">
                    @if ($documentLocked)
                        <button type="button" class="profile-primary-btn disabled" disabled>
                            Documents Verified
                        </button>
                    @else
                        <button type="submit" class="profile-primary-btn">
                            Submit Documents
                        </button>
                    @endif
                </div>
            </form>

            <form action="{{ route('provider.profile.password.update') }}" method="POST" class="profile-card">
                @csrf
                @method('PUT')

                <div class="profile-card-title">
                    <div>
                        <h2>Change Password</h2>
                        <p>Ubah password login provider.</p>
                    </div>
                </div>

                <div class="profile-form-grid">
                    <div class="profile-form-group full">
                        <label>Current Password <span>*</span></label>
                        <input type="password" name="current_password" placeholder="Enter Current Password">
                        @error('current_password') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-form-group">
                        <label>New Password <span>*</span></label>
                        <input type="password" name="password" placeholder="Enter New Password">
                        @error('password') <small>{{ $message }}</small> @enderror
                    </div>

                    <div class="profile-form-group">
                        <label>Confirm Password <span>*</span></label>
                        <input type="password" name="password_confirmation" placeholder="Confirm Password">
                    </div>
                </div>

                <div class="profile-actions">
                    <button type="submit" class="profile-primary-btn">
                        Update Password
                    </button>
                </div>
            </form>
        </div>

        <aside class="profile-card profile-side-card">
            <div class="profile-avatar">
                @if ($profileImage)
                    <img src="{{ $profileImage }}" alt="Profile Image">
                @else
                    <span>{{ strtoupper(substr($user->name ?? 'P', 0, 1)) }}</span>
                @endif
            </div>

            <h2>{{ $user->name }}</h2>
            <p>{{ $user->email }}</p>

            <div class="profile-status-list">
                <div>
                    <span>Account</span>
                    <strong class="status-badge {{ $accountStatus }}">
                        {{ ucfirst($accountStatus) }}
                    </strong>
                </div>

                <div>
                    <span>Document</span>
                    <strong class="status-badge {{ $documentStatus }}">
                        {{ ucfirst($documentStatus) }}
                    </strong>
                </div>
            </div>

            @if (!empty($profile->document_note))
                <div class="profile-note">
                    <strong>Admin Note</strong>
                    <p>{{ $profile->document_note }}</p>
                </div>
            @endif
        </aside>
    </div>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('provider/js/provider-profile.js') }}"></script>
@endpush