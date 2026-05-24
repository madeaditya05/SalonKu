@extends('admin.layouts.app')

@section('title', 'Chat - JasaKu')
@section('page_title', 'Chat')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/support-chat.css') }}">
@endpush

@section('content')
@php
    $threadQuery = fn ($thread) => array_filter([
        'thread' => $thread->id,
        'search' => $search ?: null,
    ]);

    $ticketLabels = [
        'pending' => 'Menunggu',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'closed' => 'Diakhiri',
        'none' => 'Belum ada tiket',
    ];

    $isTicketList = ($activeTab ?? 'messages') === 'tickets';
    $isRoomOpen = ! $isTicketList && $activeThread && $activeThreadCanChat;
@endphp

<section class="support-chat-page support-chat-admin support-chat-modern {{ $isRoomOpen ? 'has-active-room' : 'is-chat-list' }} {{ $isTicketList ? 'is-ticket-list' : '' }}" data-support-chat>
    <div class="support-chat-head modern">
        <div>
            <h1>Messages</h1>

            <div class="support-chat-breadcrumb">
                <a href="{{ route('admin.dashboard') }}">Dashboard</a>
                <span>&rsaquo;</span>
                <strong>Chat</strong>
            </div>
        </div>
    </div>

    @if (session('success'))
        <div class="support-ticket-alert success">{{ session('success') }}</div>
    @endif

    @if ($errors->any())
        <div class="support-ticket-alert error">{{ $errors->first() }}</div>
    @endif

    <div class="support-chat-shell support-chat-shell-modern">
        <aside class="support-chat-list support-chat-directory">
            <div class="support-directory-head">
                <form method="GET" action="{{ route('admin.chat.index') }}" class="support-chat-search compact">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>

                    @if ($isTicketList)
                        <input type="hidden" name="tab" value="tickets">
                    @endif

                    <input type="text" name="search" value="{{ $search }}" placeholder="Search" data-chat-search>
                </form>

                <div class="support-message-tabs">
                    <a href="{{ route('admin.chat.index', array_filter(['search' => $search ?: null])) }}" class="{{ $isTicketList ? '' : 'active' }}">All</a>
                    <a href="{{ route('admin.chat.index', array_filter(['tab' => 'tickets', 'search' => $search ?: null])) }}" class="{{ $isTicketList ? 'active' : '' }}">Tickets</a>
                </div>
            </div>

            <div class="support-thread-list" data-thread-list>
                @if ($isTicketList)
                    @forelse ($ticketThreads as $thread)
                        @php
                            $provider = $thread->provider;
                            $requester = $thread->providerUser ?: $provider;
                            $branchName = $requester?->providerBranch?->branch_name;
                            $requesterLabel = $branchName ? "Cabang: {$branchName}" : 'Provider pusat';
                            $ticketStatus = $thread->ticket_status ?? 'pending';
                            $initial = strtoupper(substr($requester->name ?? 'P', 0, 1));
                            $ticketPreview = \Illuminate\Support\Str::limit($thread->ticket_subject ?: 'Tiket chat provider', 72);
                        @endphp

                        <a
                            href="{{ route('admin.tickets.index', array_filter(['thread' => $thread->id, 'status' => $ticketStatus, 'search' => $search ?: null])) }}"
                            class="support-thread-item support-ticket-list-item"
                            data-thread-id="{{ $thread->id }}"
                            data-chat-row
                            data-chat-label="{{ \Illuminate\Support\Str::lower(($requester->name ?? 'Provider') . ' ' . $requesterLabel . ' ' . ($provider->name ?? 'Provider') . ' ' . $ticketPreview) }}"
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
                                <em class="support-thread-last">{{ $ticketPreview }}</em>
                            </span>

                            <span class="support-thread-meta">
                                <time>{{ $thread->ticket_requested_at?->format('d M H:i') }}</time>
                            </span>
                        </a>
                    @empty
                        <div class="support-chat-empty compact">
                            <strong>Belum ada tiket</strong>
                            <span>Tiket provider yang masuk tampil di sini.</span>
                        </div>
                    @endforelse
                @else
                    @forelse ($threads as $thread)
                        @php
                            $provider = $thread->provider;
                            $requester = $thread->providerUser ?: $provider;
                            $branchName = $requester?->providerBranch?->branch_name;
                            $requesterLabel = $branchName ? "Cabang: {$branchName}" : 'Provider pusat';
                            $lastMessage = $thread->lastMessage;
                            $ticketStatus = $thread->ticket_status ?? 'none';
                            $unreadCount = (int) ($thread->unread_count ?? 0);
                            $isActive = $activeThread && (int) $activeThread->id === (int) $thread->id;
                            $initial = strtoupper(substr($requester->name ?? 'P', 0, 1));
                            $previewBody = $lastMessage ? trim((string) $lastMessage->body) : '';
                            $preview = $lastMessage
                                ? ($previewBody !== ''
                                    ? \Illuminate\Support\Str::limit($previewBody, 48)
                                    : ($lastMessage->attachment_path ? 'Mengirim gambar' : ''))
                                : \Illuminate\Support\Str::limit($thread->ticket_subject ?: 'Tiket chat provider', 48);
                        @endphp

                        <a
                            href="{{ route('admin.chat.index', $threadQuery($thread)) }}"
                            class="support-thread-item {{ $isActive ? 'active' : '' }}"
                            data-thread-id="{{ $thread->id }}"
                            data-chat-row
                            data-chat-label="{{ \Illuminate\Support\Str::lower(($requester->name ?? 'Provider') . ' ' . $requesterLabel . ' ' . ($provider->name ?? 'Provider')) }}"
                        >
                            <span class="support-avatar">{{ $initial }}</span>

                            <span class="support-thread-copy">
                                <strong>{{ $requester->name ?? 'Provider' }}</strong>
                                <small>
                                    {{ $requesterLabel }} | {{ $provider->name ?? 'Provider' }}
                                    <span class="support-ticket-mini {{ $ticketStatus }}" data-thread-status="{{ $thread->id }}">
                                        {{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}
                                    </span>
                                </small>
                                <em class="support-thread-last" data-thread-last>
                                    {{ $preview }}
                                </em>
                            </span>

                            <span class="support-thread-meta">
                                <time data-thread-time>{{ $thread->last_message_at?->format('H:i') }}</time>
                                <b class="{{ $unreadCount > 0 ? '' : 'is-hidden' }}" data-thread-unread>{{ $unreadCount }}</b>
                            </span>
                        </a>
                    @empty
                        <div class="support-chat-empty compact">
                            <strong>Belum ada chat aktif</strong>
                            <span>Chat tampil setelah tiket disetujui dari menu Tickets.</span>
                        </div>
                    @endforelse
                @endif
            </div>
        </aside>

        @unless ($isTicketList)
        <div class="support-chat-panel support-chat-conversation">
            @if ($activeThread)
                @php
                    $activeProvider = $activeThread->provider;
                    $activeRequester = $activeThread->providerUser ?: $activeProvider;
                    $activeBranchName = $activeRequester?->providerBranch?->branch_name;
                    $activeRequesterLabel = $activeBranchName ? "Cabang: {$activeBranchName}" : 'Provider pusat';
                    $ticketStatus = $activeThread->ticket_status ?? 'none';
                    $activeInitial = strtoupper(substr($activeRequester->name ?? 'P', 0, 1));
                @endphp

                <div class="support-chat-panel-head modern">
                    <a
                        href="{{ route('admin.chat.index', array_filter(['list' => 1, 'search' => $search ?: null])) }}"
                        class="support-chat-back"
                        title="Kembali ke daftar chat"
                        aria-label="Kembali ke daftar chat"
                    >
                        <svg viewBox="0 0 24 24" fill="none">
                            <path d="M15 18l-6-6 6-6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span>Back</span>
                    </a>

                    <div class="support-chat-person">
                        <span class="support-avatar large">{{ $activeInitial }}</span>

                        <div>
                            <strong>{{ $activeRequester->name ?? 'Provider' }}</strong>
                            <span>{{ $activeRequesterLabel }} | {{ $activeRequester->email ?? '-' }}</span>
                        </div>
                    </div>

                    <div class="support-chat-tags">
                        <span class="support-ticket-badge {{ $ticketStatus }}" data-thread-status="{{ $activeThread->id }}">
                            {{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}
                        </span>
                    </div>

                    <div class="support-chat-actions">
                        @if ($activeThreadCanChat)
                            <form method="POST" action="{{ route('admin.chat.ticket.end', $activeThread) }}" class="support-chat-end-form support-chat-end-compact">
                                @csrf

                                <button type="submit" title="Akhiri chat">
                                    Akhiri
                                </button>
                            </form>
                        @endif

                        <a href="{{ route('admin.tickets.index', ['thread' => $activeThread->id, 'status' => $ticketStatus]) }}" title="Tickets">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            </svg>
                        </a>

                    </div>
                </div>

                @if (! $activeThreadCanChat)
                    <div class="support-ticket-review">
                        <div class="support-ticket-card">
                            <span class="support-ticket-kicker">Tiket Chat</span>
                            <h2>{{ $activeThread->ticket_subject ?: 'Pengajuan chat provider' }}</h2>
                            <p>Pengajuan dan approval tiket dikelola di menu Tickets.</p>

                            <dl class="support-ticket-meta">
                                <div>
                                    <dt>Diajukan</dt>
                                    <dd>{{ $activeThread->ticket_requested_at?->format('d M Y H:i') ?? '-' }}</dd>
                                </div>

                                <div>
                                    <dt>Status</dt>
                                    <dd>{{ $ticketLabels[$ticketStatus] ?? ucfirst($ticketStatus) }}</dd>
                                </div>
                            </dl>

                            @if (in_array($ticketStatus, ['rejected', 'closed'], true) && $activeThread->ticket_rejection_reason)
                                <div class="support-ticket-note">
                                    {{ $activeThread->ticket_rejection_reason }}
                                </div>
                            @endif

                            <div class="support-ticket-actions">
                                <a href="{{ route('admin.tickets.index', ['thread' => $activeThread->id, 'status' => $ticketStatus]) }}" class="support-ticket-primary support-ticket-link">
                                    Buka Tickets
                                </a>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="support-chat-messages modern" data-chat-messages>
                        @forelse ($messages as $message)
                            <div
                                class="support-message {{ $message['is_mine'] ? 'is-mine' : '' }}"
                                data-message-id="{{ $message['id'] }}"
                                data-message-sender-id="{{ $message['sender_id'] }}"
                                data-message-sender-role="{{ $message['sender_role'] }}"
                                data-message-created-at="{{ $message['created_at'] }}"
                            >
                                <div class="support-bubble">
                                    <div class="support-bubble-meta">
                                        <span class="support-sender-line">
                                            <strong>{{ $message['sender_name'] }}</strong>
                                            @if (($message['sender_role'] ?? '') === 'admin')
                                                <span class="support-admin-badge">Admin</span>
                                                <span class="support-verified-check support-message-check" title="Akun admin resmi" aria-label="Akun admin resmi">
                                                    <svg viewBox="0 0 20 20" fill="none">
                                                        <circle cx="10" cy="10" r="9" fill="currentColor"/>
                                                        <path d="M6 10.2l2.5 2.5L14.2 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </span>
                                            @endif
                                        </span>
                                        <span class="support-bubble-time">{{ $message['sent_at'] }}</span>
                                    </div>

                                    @if (! empty($message['attachment']) && ($message['attachment']['type'] ?? '') === 'image')
                                        <a href="{{ $message['attachment']['url'] }}" class="support-message-image" target="_blank" rel="noopener">
                                            <img src="{{ $message['attachment']['url'] }}" alt="{{ $message['attachment']['name'] ?: 'Gambar chat' }}">
                                        </a>
                                    @endif

                                    @if ($message['body'] !== '')
                                        <p>{!! nl2br(e($message['body'])) !!}</p>
                                    @endif

                                    @if ($message['is_mine'])
                                        <div class="support-bubble-footer">
                                            <span
                                                class="support-message-status is-{{ $message['delivery_status'] }}"
                                                data-message-status
                                                data-status="{{ $message['delivery_status'] }}"
                                            >
                                                {{ $message['delivery_label'] }}
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="support-chat-empty" data-chat-empty>
                                <strong>Belum ada pesan</strong>
                                <span>Mulai percakapan dengan provider ini.</span>
                            </div>
                        @endforelse
                    </div>

                    <form class="support-chat-compose modern" data-chat-form enctype="multipart/form-data">
                        <div class="support-compose-tools">
                            <button type="button" title="Emoticon" data-emoji-toggle>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="2"/>
                                    <path d="M8 14s1.5 2 4 2 4-2 4-2M9 9h.01M15 9h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </button>

                            <button type="button" title="Kirim gambar" data-chat-image-trigger>
                                <svg viewBox="0 0 24 24" fill="none">
                                    <rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="2"/>
                                    <circle cx="9" cy="10" r="1.5" fill="currentColor"/>
                                    <path d="M6.5 17l4.2-4.2 2.8 2.8 1.8-1.8L18 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                        </div>

                        <input type="file" name="image" accept="image/*" data-chat-image hidden>

                        <div class="support-emoji-panel" data-emoji-panel hidden>
                            @foreach (['😀', '😊', '🙏', '👍', '👌', '✨', '❤️', '😂', '😮', '😢', '🔥', '✅'] as $emoji)
                                <button type="button" data-emoji="{{ $emoji }}">{{ $emoji }}</button>
                            @endforeach
                        </div>

                        <div class="support-compose-field">
                            <textarea name="body" rows="1" placeholder="Type your message" maxlength="2000" data-chat-input></textarea>

                            <div class="support-file-preview is-hidden" data-chat-file-preview>
                                <span data-chat-file-name></span>
                                <button type="button" title="Hapus gambar" data-chat-file-clear>&times;</button>
                            </div>
                        </div>

                        <button type="submit" title="Send">
                            <svg viewBox="0 0 24 24" fill="none">
                                <path d="M22 2L11 13" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M22 2L15 22L11 13L2 9L22 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span>Send</span>
                        </button>
                    </form>
                @endif
            @else
                <div class="support-chat-empty full modern">
                    <strong>Pilih chat</strong>
                    <span>Pilih provider dari daftar untuk membuka room chat.</span>
                </div>
            @endif
        </div>
        @endunless
    </div>

    <script type="application/json" id="supportChatConfig">
        {!! json_encode($chatConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/support-chat.js') }}"></script>
@endpush
