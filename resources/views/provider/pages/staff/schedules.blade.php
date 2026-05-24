@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Jadwal Staff - JasaKu')
@section('page_title', 'Jadwal Staff')

@section('content')
@php
    $days = [
        'monday' => 'Senin',
        'tuesday' => 'Selasa',
        'wednesday' => 'Rabu',
        'thursday' => 'Kamis',
        'friday' => 'Jumat',
        'saturday' => 'Sabtu',
        'sunday' => 'Minggu',
    ];
@endphp

<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Jadwal Staff</h1>
            <p>Atur hari dan jam kerja staff untuk perhitungan availability booking jam pasti.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="ops-alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="ops-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="ops-staff-grid">
        @forelse ($staffs as $staff)
            @php
                $currentDays = $staff->schedules->pluck('day_of_week')->map(fn ($day) => strtolower($day))->all();
                $firstSchedule = $staff->schedules->first();
            @endphp
            <article class="ops-staff-card">
                <h3>{{ $staff->full_name }}</h3>
                <p>
                    @if ($staff->schedules->isEmpty())
                        Belum ada jadwal.
                    @else
                        {{ $staff->schedules->pluck('day_of_week')->join(', ') }}
                    @endif
                </p>
                <form class="ops-form" method="POST" action="{{ provider_route('provider.staff.schedules.update') }}">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $staff->id }}">
                    <div class="ops-check-grid">
                        @foreach ($days as $key => $label)
                            <label class="ops-check">
                                <input type="checkbox" name="days[]" value="{{ $key }}" @checked(in_array($key, old('days', $currentDays), true))>
                                <span>{{ $label }}</span>
                            </label>
                        @endforeach
                    </div>
                    <div class="ops-grid">
                        <label class="ops-field">
                            Mulai
                            <input type="time" name="start_time" value="{{ old('start_time', $firstSchedule ? substr($firstSchedule->start_time, 0, 5) : '09:00') }}" required>
                        </label>
                        <label class="ops-field">
                            Selesai
                            <input type="time" name="end_time" value="{{ old('end_time', $firstSchedule ? substr($firstSchedule->end_time, 0, 5) : '18:00') }}" required>
                        </label>
                    </div>
                    <button class="ops-button dark" type="submit">Simpan Jadwal</button>
                </form>
            </article>
        @empty
            <div class="ops-empty">Belum ada staff.</div>
        @endforelse
    </div>
</section>
@endsection
