@extends('provider.layouts.dashboard')
@include('provider.pages.shared.booking-flow-styles')

@section('title', 'Skill Staff - JasaKu')
@section('page_title', 'Skill Staff')

@section('content')
<section class="ops-page">
    <div class="ops-head">
        <div>
            <h1>Skill Staff</h1>
            <p>Pilih service yang bisa dikerjakan setiap staff. Customer hanya melihat staff yang cocok untuk semua service pilihannya.</p>
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
                $updated = (int) session('updated_staff_id') === (int) $staff->id;
                $selectedSkillIds = collect(old('skills.' . $staff->id, $staff->skills->pluck('id')->all()))
                    ->map(fn ($id) => (string) $id)
                    ->all();
                $availableServices = $services->filter(function ($service) use ($staff) {
                    if (!$staff->branch_id || empty($service->branch_ids)) {
                        return true;
                    }

                    return in_array((int) $staff->branch_id, array_map('intval', (array) $service->branch_ids), true);
                })->values();
            @endphp

            <article class="ops-staff-card {{ $updated ? 'is-updated' : '' }}">
                <div class="ops-staff-title">
                    <div>
                        <h3>{{ $staff->full_name }}</h3>
                        <p>{{ $staff->branch->branch_name ?? 'Belum punya branch' }}</p>
                    </div>

                    @if ($updated)
                        <span class="ops-chip success">Updated</span>
                    @endif
                </div>

                <form class="ops-form" method="POST" action="{{ provider_route('provider.staff.skills.update') }}" id="staff-skill-form-{{ $staff->id }}">
                    @csrf
                    <input type="hidden" name="staff_id" value="{{ $staff->id }}">
                    <div class="ops-check-grid">
                        @forelse ($availableServices as $service)
                            @php
                                $inputId = 'skill-' . $staff->id . '-' . $service->id;
                                $checked = in_array((string) $service->id, $selectedSkillIds, true);
                            @endphp

                            <label class="ops-check {{ $checked ? 'is-checked' : '' }}" for="{{ $inputId }}">
                                <input
                                    id="{{ $inputId }}"
                                    type="checkbox"
                                    name="skills[{{ $staff->id }}][]"
                                    value="{{ $service->id }}"
                                    @checked($checked)
                                >
                                <span>{{ $service->title }}</span>
                            </label>
                        @empty
                            <div class="ops-empty mini">Belum ada service untuk branch staff ini.</div>
                        @endforelse
                    </div>
                    <button class="ops-button dark" type="submit">Simpan Skill</button>
                </form>
            </article>
        @empty
            <div class="ops-empty">Belum ada staff.</div>
        @endforelse
    </div>
</section>
@endsection
