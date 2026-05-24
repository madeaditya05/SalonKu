@extends('admin.layouts.app')

@section('title', 'Tickets - JasaKu')
@section('page_title', 'Tickets')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/support-chat.css') }}">
@endpush

@section('content')
@php
    $ticketLabels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'closed' => 'Diakhiri',
    ];

    $threadQuery = fn ($thread) => array_filter([
        'thread' => $thread->id,
        'status' => $status,
        'search' => $search ?: null,
    ]);

    $statusQuery = fn ($value) => array_filter([
        'status' => $value,
        'search' => $search ?: null,
    ]);

    $statuses = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'closed' => 'Diakhiri',
        'all' => 'Semua',
    ];
@endphp

<section class="support-chat-page">
    <div class="support-chat-head">
        <div>
            <h1>Tickets</h1>

            <div class="support-chat-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>&rsaquo;</span>
                <strong>Tickets</strong>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="support-ticket-alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="support-ticket-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="support-chat-shell">
        <aside class="support-chat-list">
            <form method="GET" action="{{ route('admin.tickets.index') }}" class="support-chat-search">
                <svg viewBox="0 0 24 24" fill="none">
                    <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>

                <input type="hidden" name="status" value="{{ $status }}">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search provider">
            </form>

            <div class="support-ticket-tabs">
                @foreach ($statuses as $statusKey => $label)
                    @php
                        $count = $statusKey === 'all'
                            ? $statusCounts->sum()
                            : (int) ($statusCounts[$statusKey] ?? 0);
                    @endphp

                    <a href="{{ route('admin.tickets.index', $statusQuery($statusKey)) }}"
                       class="{{ $status === $statusKey ? 'active' : '' }}">
                        <span>{{ $label }}</span>
                        <b>{{ $count }}</b>
                    </a>
                @endforeach
            </div>

            <div class="support-thread-list">
                @forelse ($threads as $thread)
                    @php
                        $provider = $thread->provider;
                        $requester = $thread->providerUser ?: $provider;
                        $branchName = $requester?->providerBranch?->branch_name;
                        $requesterLabel = $branchName ? "Cabang: {$branchName}" : 'Provider pusat';
                        $ticketStatus = $thread->ticket_status ?? 'pending';
                        $isActive = $activeThread && (int) $activeThread->id === (int) $thread->id;
                        $initial = strtoupper(substr($requester->name ?? 'P', 0, 1));
                    @endphp

                    <a
                        href="{{ route('admin.tickets.index', $threadQuery($thread)) }}"
                        class="support-thread-item {{ $isActive ? 'active' : '' }}"
                    >
                        <span class="support-avatar">{{ $initial }}</span>

                        <span class="support-thread-copy">
                            <strong>{{ $requester->name ?? 'Provider' }}</strong>
                            <small>
                                {{ $requesterLabel }} | {{ $provider->name ?? 'Provider' }}
                                <span class="support-ticket-mini {{ $ticketStatus }}">
                                    {{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}
                                </span>
                            </small>
                            <em class="support-thread-last">
                                {{ \Illuminate\Support\Str::limit($thread->ticket_subject ?: 'Tiket chat provider', 48) }}
                            </em>
                        </span>

                        <span class="support-thread-meta">
                            <time>{{ $thread->ticket_requested_at?->format('H:i') }}</time>
                        </span>
                    </a>
                @empty
                    <div class="support-chat-empty compact">
                        <strong>Belum ada tiket</strong>
                        <span>Tiket provider yang masuk tampil di sini.</span>
                    </div>
                @endforelse
            </div>
        </aside>

        <div class="support-chat-panel">
            @if ($activeThread)
                @php
                    $activeProvider = $activeThread->provider;
                    $activeRequester = $activeThread->providerUser ?: $activeProvider;
                    $activeBranchName = $activeRequester?->providerBranch?->branch_name;
                    $activeRequesterLabel = $activeBranchName ? "Cabang: {$activeBranchName}" : 'Provider pusat';
                    $profile = $activeProvider?->providerProfile;
                    $ticketStatus = $activeThread->ticket_status ?? 'pending';
                    $activeInitial = strtoupper(substr($activeRequester->name ?? 'P', 0, 1));
                @endphp

                <div class="support-chat-panel-head">
                    <div class="support-chat-person">
                        <span class="support-avatar large">{{ $activeInitial }}</span>

                        <div>
                            <strong>{{ $activeRequester->name ?? 'Provider' }}</strong>
                            <span>{{ $activeRequesterLabel }} | {{ $activeRequester->email ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="support-chat-tags">
                        <span class="support-ticket-badge {{ $ticketStatus }}">
                            {{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}
                        </span>
                        <span>{{ ucfirst($profile->status ?? 'inactive') }}</span>
                        <span>{{ ucfirst($profile->document_status ?? 'pending') }}</span>
                    </div>
                </div>

                <div class="support-ticket-review">
                    <div class="support-ticket-card">
                        <span class="support-ticket-kicker">Pengajuan Tiket</span>
                        <h2>{{ $activeThread->ticket_subject ?: 'Pengajuan chat provider' }}</h2>
                        <p>{{ $activeThread->ticket_body ?: 'Provider belum menulis detail tiket.' }}</p>

                        <dl class="support-ticket-meta">
                            <div>
                                <dt>Diajukan</dt>
                                <dd>{{ $activeThread->ticket_requested_at?->format('d M Y H:i') ?? '-' }}</dd>
                            </div>

                            <div>
                                <dt>Direview</dt>
                                <dd>{{ $activeThread->ticket_reviewed_at?->format('d M Y H:i') ?? '-' }}</dd>
                            </div>
                        </dl>

                        @if (in_array($ticketStatus, ['rejected', 'closed'], true) && $activeThread->ticket_rejection_reason)
                            <div class="support-ticket-note">
                                {{ $activeThread->ticket_rejection_reason }}
                            </div>
                        @endif

                        <div class="support-ticket-actions">
                            @if (in_array($ticketStatus, ['pending', 'rejected'], true))
                                <form method="POST" action="{{ route('admin.tickets.approve', $activeThread) }}">
                                    @csrf

                                    <button type="submit" class="support-ticket-primary">
                                        Setujui tiket
                                    </button>
                                </form>
                            @endif

                            @if ($ticketStatus === 'pending')
                                <form method="POST" action="{{ route('admin.tickets.reject', $activeThread) }}" class="support-ticket-reject-form">
                                    @csrf

                                    <input type="text" name="reason" maxlength="500" placeholder="Alasan penolakan opsional">

                                    <button type="submit" class="support-ticket-secondary">
                                        Tolak
                                    </button>
                                </form>
                            @endif

                            @if ($ticketStatus === 'approved')
                                <a href="{{ route('admin.chat.index', ['thread' => $activeThread->id]) }}" class="support-ticket-primary support-ticket-link">
                                    Buka Chat
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            @else
                <div class="support-chat-empty full">
                    <strong>Pilih tiket</strong>
                    <span>Detail tiket akan tampil setelah dipilih.</span>
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
