@extends('admin.layouts.app')

@section('content')
<section class="profile-page">
    <div class="page-header">
        <h1>My Profile</h1>
        <div class="breadcrumb">
            Dashboard <span>/</span> My Profile
        </div>
    </div>

    <div class="profile-card">
        <div class="profile-avatar">
            <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name ?? 'Admin') }}&background=ff2b8a&color=fff" alt="Profile">
        </div>

        <div>
            <h2>{{ Auth::user()->name }}</h2>
            <p>{{ Auth::user()->email }}</p>
            <span>{{ ucfirst(Auth::user()->role) }}</span>
        </div>
    </div>
</section>
@endsection