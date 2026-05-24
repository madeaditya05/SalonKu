@extends('provider.layouts.dashboard')

@section('title', 'My Profile - Provider Dashboard')
@section('page_title', 'My Profile')
@section('page_subtitle', 'Preview profile provider.')

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
@endphp

<section class="profile-page">
    <div class="profile-header">
        <div>
            <h1>My Profile</h1>
            <p>Dashboard / My Profile</p>
        </div>

        <a href="{{ provider_route('provider.profile.edit') }}" class="profile-primary-btn">
            Edit Profile
        </a>
    </div>

    @if (session('success'))
        <div class="profile-alert success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="profile-alert error">{{ session('error') }}</div>
    @endif

    <div class="profile-grid">
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

        <div class="profile-main">
            <div class="profile-card">
                <div class="profile-card-title">
                    <h2>Basic Information</h2>
                </div>

                <div class="profile-info-grid">
                    <div>
                        <span>Name</span>
                        <strong>{{ $user->name ?? '-' }}</strong>
                    </div>

                    <div>
                        <span>Username</span>
                        <strong>{{ $user->username ?? '-' }}</strong>
                    </div>

                    <div>
                        <span>Email</span>
                        <strong>{{ $user->email ?? '-' }}</strong>
                    </div>

                    <div>
                        <span>Phone Number</span>
                        <strong>{{ $profile->phone_number ?? '-' }}</strong>
                    </div>
                </div>
            </div>

            <div class="profile-card">
                <div class="profile-card-title">
                    <div>
                        <h2>Documents</h2>
                        <p>Dokumen verifikasi provider.</p>
                    </div>

                    <span class="status-badge {{ $documentStatus }}">
                        {{ ucfirst($documentStatus) }}
                    </span>
                </div>

                <div class="profile-document-grid">
                    <div class="profile-document-item">
                        <div class="document-preview">
                            @if ($ktpImage)
                                <img src="{{ $ktpImage }}" alt="KTP Image">
                            @else
                                <span>No KTP Image</span>
                            @endif
                        </div>

                        <h3>Foto KTP</h3>
                    </div>

                    <div class="profile-document-item">
                        <div class="document-preview">
                            @if ($businessImage)
                                <img src="{{ $businessImage }}" alt="Business Image">
                            @else
                                <span>No Business Image</span>
                            @endif
                        </div>

                        <h3>Foto Usaha</h3>
                    </div>
                </div>

                @if ($documentStatus !== 'verified')
                    <div class="profile-help-box">
                        Lengkapi dokumen di halaman edit profile agar admin bisa melakukan verifikasi.
                    </div>
                @endif
            </div>
        </div>
    </div>
</section>
@endsection