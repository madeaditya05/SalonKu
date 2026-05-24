@extends('provider.layouts.dashboard')

@section('title', 'Support Help - JasaKu')
@section('page_title', 'Support Help')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/support-chat.css') }}">
@endpush

@section('content')
@php
    $authUser = request()->user();
    $thread = $adminThread ?? $thread;
    $provider = $thread->provider;
    $requester = $thread->providerUser ?: $authUser;
    $ticketStatus = $thread->ticket_status ?? 'none';
    $ticketLabels = [
        'none' => 'Belum diajukan',
        'pending' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'closed' => 'Chat diakhiri',
    ];
    $faqs = [
        [
            'question' => 'Bagaimana cara membuka chat dengan admin?',
            'answer' => 'Baca FAQ lebih dulu. Jika masih butuh bantuan langsung, ajukan tiket chat support dari bagian bawah halaman ini. Admin akan meninjau tiket sebelum room chat dibuka.',
        ],
        [
            'question' => 'Kenapa chat admin harus memakai tiket?',
            'answer' => 'Tiket membantu admin memahami konteks masalah lebih cepat, menjaga antrean bantuan tetap rapi, dan menyimpan riwayat permintaan untuk follow-up.',
        ],
        [
            'question' => 'Berapa lama tiket ditinjau?',
            'answer' => 'Tiket akan masuk ke antrean admin. Sertakan judul dan detail yang jelas agar proses review lebih cepat.',
        ],
        [
            'question' => 'Apakah chat internal provider perlu tiket?',
            'answer' => 'Tidak. Chat internal antar akun provider bisa langsung digunakan dari menu Chat.',
        ],
    ];
@endphp

<section class="support-chat-page support-chat-provider support-help-page">
    <div class="support-help-hero">
        <div class="support-help-copy">
            <div class="support-chat-breadcrumb">
                <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
                <span>&rsaquo;</span>
                <strong>Support Help</strong>
            </div>

            <span class="support-help-kicker">Support Center</span>
            <h1>Pusat Bantuan Provider</h1>
            <p>Cek FAQ umum lebih dulu. Jika masih butuh bantuan admin, ajukan tiket chat support dengan detail yang jelas.</p>
        </div>

        <div class="support-help-status">
            <span>Status tiket</span>
            <strong>{{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}</strong>
            <b class="support-ticket-badge {{ $ticketStatus }}">
                {{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}
            </b>
        </div>
    </div>

    @if (session('success'))
        <div class="support-ticket-alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="support-ticket-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="support-help-grid">
        <main class="support-help-main">
            <section class="support-help-section support-faq-card" aria-labelledby="support-faq-title">
                <div class="support-help-section-head">
                    <span>FAQ Umum</span>
                    <h2 id="support-faq-title">Bantuan cepat untuk provider</h2>
                </div>

                <div class="support-faq-list">
                    @foreach ($faqs as $index => $faq)
                        <details {{ $index === 0 ? 'open' : '' }}>
                            <summary>{{ $faq['question'] }}</summary>
                            <p>{{ $faq['answer'] }}</p>
                        </details>
                    @endforeach
                </div>
            </section>

            <section class="support-help-section support-help-ticket" aria-labelledby="support-ticket-title">
                @if ($ticketStatus === 'approved')
                    <div class="support-help-section-head">
                        <span>Chat Support Aktif</span>
                        <h2 id="support-ticket-title">{{ $thread->ticket_subject ?: 'Chat admin sudah terbuka' }}</h2>
                    </div>
                    <p>{{ $thread->ticket_body ?: 'Admin sudah menyetujui akses chat support.' }}</p>

                    <dl class="support-ticket-meta">
                        <div>
                            <dt>Diajukan</dt>
                            <dd>{{ $thread->ticket_requested_at?->format('d M Y H:i') ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt>Disetujui</dt>
                            <dd>{{ $thread->ticket_reviewed_at?->format('d M Y H:i') ?? '-' }}</dd>
                        </div>
                    </dl>

                    <div class="support-ticket-actions">
                        <a href="{{ provider_route('provider.chat.index', ['thread' => $thread->id]) }}" class="support-ticket-primary support-ticket-link">
                            Buka Chat Support
                        </a>
                    </div>
                @elseif ($ticketStatus === 'pending')
                    <div class="support-help-section-head">
                        <span>Pengajuan Chat Support</span>
                        <h2 id="support-ticket-title">{{ $thread->ticket_subject ?: 'Pengajuan chat support sedang ditinjau' }}</h2>
                    </div>
                    <p>{{ $thread->ticket_body ?: 'Tiket sedang ditinjau admin. Kamu bisa membuka halaman ini lagi untuk melihat statusnya.' }}</p>

                    <dl class="support-ticket-meta">
                        <div>
                            <dt>Diajukan</dt>
                            <dd>{{ $thread->ticket_requested_at?->format('d M Y H:i') ?? '-' }}</dd>
                        </div>

                        <div>
                            <dt>Status</dt>
                            <dd>{{ $ticketLabels[$ticketStatus] }}</dd>
                        </div>
                    </dl>
                @else
                    <div class="support-help-section-head">
                        <span>Butuh Chat Support?</span>
                        <h2 id="support-ticket-title">
                            @if ($ticketStatus === 'closed')
                                Chat support sebelumnya sudah diakhiri
                            @elseif ($ticketStatus === 'rejected')
                                Ajukan ulang tiket chat support
                            @else
                                Ajukan tiket chat support
                            @endif
                        </h2>
                    </div>
                    <p>Jika FAQ belum menjawab kebutuhanmu, kirim tiket agar admin bisa membuka room chat support dengan konteks yang jelas.</p>

                    @if (in_array($ticketStatus, ['rejected', 'closed'], true) && $thread->ticket_rejection_reason)
                        <div class="support-ticket-note">
                            {{ $thread->ticket_rejection_reason }}
                        </div>
                    @endif

                    <form method="POST" action="{{ provider_route('provider.tickets.store') }}" class="support-ticket-form">
                        @csrf

                        <label>
                            <span>Judul tiket</span>
                            <input type="text" name="subject" value="{{ old('subject', $thread->ticket_subject) }}" maxlength="160" required>
                        </label>

                        <label>
                            <span>Detail kebutuhan</span>
                            <textarea name="body" rows="5" maxlength="2000" required>{{ old('body', $thread->ticket_body) }}</textarea>
                        </label>

                        <button type="submit" class="support-ticket-primary">
                            Ajukan ke admin
                        </button>
                    </form>
                @endif
            </section>
        </main>

        <aside class="support-help-aside">
            <section class="support-help-side-card">
                <span>Detail akun</span>
                <h2>Informasi provider</h2>

                <div class="support-help-side-list">
                    <div>
                        <small>Provider</small>
                        <strong>{{ $provider->name ?? '-' }}</strong>
                    </div>

                    <div>
                        <small>Akun pengaju</small>
                        <strong>{{ $requester->name ?? '-' }}</strong>
                    </div>

                    <div>
                        <small>Status tiket</small>
                        <strong>{{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}</strong>
                    </div>
                </div>
            </section>

            <section class="support-help-side-card">
                <span>Chat Internal</span>
                <h2>Chat sesama provider</h2>
                <p>Chat internal tidak memakai tiket dan riwayat percakapan tetap tersimpan.</p>

                <div class="support-ticket-actions">
                    <a href="{{ provider_route('provider.chat.index') }}" class="support-ticket-primary support-ticket-link">
                        Buka Chat Internal
                    </a>
                </div>
            </section>
        </aside>
    </div>
</section>
@endsection
