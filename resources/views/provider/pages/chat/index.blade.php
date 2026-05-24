@extends('provider.layouts.dashboard')

@section('title', 'Chat - JasaKu')
@section('page_title', 'Chat')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/support-chat.css') }}">
@endpush

@section('content')
@php
    $authUser = request()->user();
    $adminTicketStatus = $adminThread->ticket_status ?? 'none';
    $ticketLabels = [
        'none' => 'Belum diajukan',
        'pending' => 'Menunggu approval',
        'approved' => 'Disetujui',
        'rejected' => 'Ditolak',
        'closed' => 'Chat diakhiri',
    ];

    $otherParticipant = function ($thread) use ($authUser) {
        if (($thread->conversation_type ?? 'provider_admin') === 'provider_admin') {
            return null;
        }

        return (int) $thread->provider_user_id === (int) $authUser->id
            ? $thread->branchUser
            : $thread->providerUser;
    };

    $threadTitle = function ($thread) use ($otherParticipant) {
        if (($thread->conversation_type ?? 'provider_admin') === 'provider_admin') {
            return 'JasaKu Admin';
        }

        return $otherParticipant($thread)?->name ?: 'Provider';
    };

    $threadSubtitle = function ($thread) use ($authUser, $otherParticipant) {
        if (($thread->conversation_type ?? 'provider_admin') === 'provider_admin') {
            $requester = $thread->providerUser ?: $authUser;
            $branchName = $requester?->providerBranch?->branch_name;

            return $branchName ? "Support admin | {$branchName}" : 'Support admin';
        }

        $other = $otherParticipant($thread);

        return $other?->providerBranch?->branch_name ?: 'Provider internal';
    };

    $threadIsAdmin = fn ($thread) => ($thread->conversation_type ?? 'provider_admin') === 'provider_admin';
    $threadBadge = fn ($thread) => $threadIsAdmin($thread) ? 'Admin' : 'Internal';
    $threadInitial = fn ($thread) => strtoupper(substr($threadTitle($thread), 0, 1) ?: 'C');
    $activeIsAdmin = $activeThread && $threadIsAdmin($activeThread);
    $isRoomOpen = $activeThread && $activeThreadCanChat;
@endphp

<section class="support-chat-page support-chat-provider support-chat-modern {{ $isRoomOpen ? 'has-active-room' : 'is-chat-list' }}" data-support-chat>
    <div class="support-chat-head modern">
        <div>
            <h1>Messages</h1>

            <div class="support-chat-breadcrumb">
                <a href="{{ provider_route('provider.dashboard') }}">Dashboard</a>
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
                <label class="support-chat-search compact">
                    <svg viewBox="0 0 24 24" fill="none">
                        <circle cx="11" cy="11" r="7" stroke="currentColor" stroke-width="2"/>
                        <path d="M21 21L16.7 16.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                    <input type="text" placeholder="Search" data-chat-search>
                </label>

                <div class="support-message-tabs">
                    <span class="active">All</span>
                    <a href="{{ provider_route('provider.tickets.index') }}">Support Help</a>
                </div>
            </div>

            <div class="support-thread-list" data-thread-list>
                @forelse ($threads as $thread)
                    @php
                        $unreadCount = (int) ($thread->unread_count ?? 0);
                        $isActive = $activeThread && (int) $activeThread->id === (int) $thread->id;
                        $isAdminThread = $threadIsAdmin($thread);
                        $lastMessage = $thread->lastMessage;
                        $previewBody = $lastMessage ? trim((string) $lastMessage->body) : '';
                        $preview = $lastMessage
                            ? ($previewBody !== ''
                                ? \Illuminate\Support\Str::limit($previewBody, 48)
                                : ($lastMessage->attachment_path ? 'Mengirim gambar' : ''))
                            : \Illuminate\Support\Str::limit($thread->ticket_subject ?: 'Mulai percakapan', 48);
                    @endphp

                    <a
                        href="{{ provider_route('provider.chat.index', ['thread' => $thread->id]) }}"
                        class="support-thread-item {{ $isActive ? 'active' : '' }}"
                        data-thread-id="{{ $thread->id }}"
                        data-chat-row
                        data-chat-label="{{ \Illuminate\Support\Str::lower($threadTitle($thread) . ' ' . $threadSubtitle($thread)) }}"
                    >
                        <span class="support-avatar">{{ $threadInitial($thread) }}</span>

                        <span class="support-thread-copy">
                            <span class="support-identity-line">
                                <strong>{{ $threadTitle($thread) }}</strong>
                                @if ($isAdminThread)
                                    <span class="support-admin-chip">Admin</span>
                                    <span class="support-verified-check" title="Akun admin resmi" aria-label="Akun admin resmi">
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <circle cx="10" cy="10" r="9" fill="currentColor"/>
                                            <path d="M6 10.2l2.5 2.5L14.2 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                @endif
                            </span>
                            <small>
                                {{ $threadSubtitle($thread) }}
                                @unless ($isAdminThread)
                                    <span class="support-ticket-mini approved">{{ $threadBadge($thread) }}</span>
                                @endunless
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
                        <span>Pilih kontak internal atau buka Support Help.</span>
                    </div>
                @endforelse
            </div>

            <div class="support-contact-section">
                <span>Start internal chat</span>

                @forelse ($internalContacts as $contact)
                    <form
                        method="POST"
                        action="{{ provider_route('provider.chat.internal.start') }}"
                        class="support-contact-form"
                        data-chat-row
                        data-chat-label="{{ \Illuminate\Support\Str::lower($contact->name . ' ' . ($contact->providerBranch?->branch_name ?? 'provider pusat')) }}"
                    >
                        @csrf
                        <input type="hidden" name="contact_user_id" value="{{ $contact->id }}">

                        <button type="submit" class="support-thread-item support-contact-button">
                            <span class="support-avatar">{{ strtoupper(substr($contact->name ?? 'P', 0, 1)) }}</span>

                            <span class="support-thread-copy">
                                <strong>{{ $contact->name }}</strong>
                                <small>{{ $contact->providerBranch?->branch_name ?? 'Provider pusat' }}</small>
                                <em class="support-thread-last">Buka percakapan</em>
                            </span>
                        </button>
                    </form>
                @empty
                    <div class="support-chat-empty compact">
                        <strong>Tidak ada kontak</strong>
                        <span>Kontak internal akan muncul setelah akun cabang dibuat.</span>
                    </div>
                @endforelse
            </div>
        </aside>

        <div class="support-chat-panel support-chat-conversation">
            @if ($activeThread && $activeThreadCanChat)
                <div class="support-chat-panel-head modern">
                    <a
                        href="{{ provider_route('provider.chat.index', ['list' => 1]) }}"
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
                        <span class="support-avatar large">{{ $threadInitial($activeThread) }}</span>

                        <div>
                            <span class="support-identity-line">
                                <strong>{{ $threadTitle($activeThread) }}</strong>
                                @if ($activeIsAdmin)
                                    <span class="support-admin-chip">Admin</span>
                                    <span class="support-verified-check" title="Akun admin resmi" aria-label="Akun admin resmi">
                                        <svg viewBox="0 0 20 20" fill="none">
                                            <circle cx="10" cy="10" r="9" fill="currentColor"/>
                                            <path d="M6 10.2l2.5 2.5L14.2 7" stroke="#ffffff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                        </svg>
                                    </span>
                                @endif
                            </span>
                            <span>{{ $threadSubtitle($activeThread) }}</span>
                        </div>
                    </div>

                    <div class="support-chat-actions">
                        @if ($activeIsAdmin)
                            <a href="{{ provider_route('provider.tickets.index') }}" title="Support Help">
                                <svg viewBox="0 0 24 24" fill="none">
                                    <path d="M4 5h16v14H4z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    <path d="M8 9h8M8 13h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

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
                            <span>Tulis pesan pertama untuk memulai percakapan.</span>
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
            @else
                <div class="support-chat-empty full modern support-provider-empty">
                    <strong>Pilih Chat</strong>
                    <span>Pilih kontak internal di kiri, atau buka Support Help untuk bantuan admin.</span>

                    <div class="support-ticket-actions support-provider-empty-actions">
                        <a href="{{ provider_route('provider.tickets.index') }}" class="support-ticket-primary support-ticket-link">
                            Buka Support Help
                        </a>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <script type="application/json" id="supportChatConfig">
        {!! json_encode($chatConfig, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    </script>
</section>
@endsection

@push('scripts')
    <script src="{{ asset('js/support-chat.js') }}"></script>
@endpush
