<?php

namespace App\Http\Controllers;

use App\Events\ChatMessageSent;
use App\Events\ChatThreadUpdated;
use App\Models\ChatMessage;
use App\Models\ChatThread;
use App\Models\User;
use App\Services\AppNotificationService;
use App\Support\ChatMessagePresenter;
use App\Support\ProviderMenuAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SupportChatController extends Controller
{
    private const TYPE_PROVIDER_ADMIN = 'provider_admin';
    private const TYPE_PROVIDER_BRANCH = 'provider_branch';

    public function adminIndex(Request $request)
    {
        $admin = $request->user();

        abort_unless($admin?->role === 'admin', 403);

        $search = trim((string) $request->query('search', ''));
        $activeTab = $request->query('tab') === 'tickets' ? 'tickets' : 'messages';
        $providerIds = $this->providerOwners($search)->pluck('id');

        $threads = ChatThread::query()
            ->with(['provider.providerProfile', 'providerUser.providerBranch', 'lastMessage.sender'])
            ->whereIn('provider_id', $providerIds)
            ->where('conversation_type', self::TYPE_PROVIDER_ADMIN)
            ->where('ticket_status', 'approved')
            ->get()
            ->sortByDesc(fn (ChatThread $thread) => optional(
                $thread->last_message_at ?: $thread->ticket_requested_at ?: $thread->updated_at
            )->timestamp ?: 0)
            ->values();

        $ticketThreads = ChatThread::query()
            ->with(['provider.providerProfile', 'providerUser.providerBranch', 'ticketReviewer', 'lastMessage.sender'])
            ->whereIn('provider_id', $providerIds)
            ->where('conversation_type', self::TYPE_PROVIDER_ADMIN)
            ->whereIn('ticket_status', ['pending', 'approved', 'rejected', 'closed'])
            ->get()
            ->sortByDesc(fn (ChatThread $thread) => optional(
                $thread->ticket_requested_at ?: $thread->ticket_reviewed_at ?: $thread->last_message_at ?: $thread->updated_at
            )->timestamp ?: 0)
            ->values();

        $threads->each(function (ChatThread $thread) {
            $thread->setAttribute('unread_count', $this->threadChatApproved($thread) ? $this->unreadCount($thread, 'admin') : 0);
        });

        $requestedThreadId = (int) $request->query('thread');
        $requestedTicketId = (int) $request->query('ticket');
        $listOnly = $request->boolean('list') || $activeTab === 'tickets';
        $requestedThread = $requestedThreadId > 0 ? $threads->firstWhere('id', $requestedThreadId) : null;
        $activeThread = $listOnly ? null : $requestedThread;
        $activeTicketThread = $activeTab === 'tickets' && $requestedTicketId > 0
            ? $ticketThreads->firstWhere('id', $requestedTicketId)
            : null;
        $activeThreadCanChat = $activeThread ? $this->threadChatApproved($activeThread) : false;
        $messages = collect();

        if ($activeThread && $activeThreadCanChat) {
            $messages = $this->messagesFor($activeThread, $admin);
            $this->markThreadRead($activeThread, 'admin');
            $activeThread->setAttribute('unread_count', 0);
        }

        $approvedThreadIds = $threads
            ->filter(fn (ChatThread $thread) => $this->threadChatApproved($thread))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return view('admin.chat.index', [
            'threads' => $threads,
            'ticketThreads' => $ticketThreads,
            'activeThread' => $activeThread,
            'activeTicketThread' => $activeTicketThread,
            'activeThreadCanChat' => $activeThreadCanChat,
            'messages' => $messages,
            'search' => $search,
            'activeTab' => $activeTab,
            'chatConfig' => $this->chatConfig(
                $admin,
                $activeThread,
                $approvedThreadIds,
                $activeThreadCanChat ? route('admin.chat.messages.store', $activeThread) : null,
                $activeThreadCanChat ? route('admin.chat.read', $activeThread) : null,
                $activeThreadCanChat ? route('admin.chat.messages.index', $activeThread) : null
            ),
        ]);
    }

    public function adminTicketsIndex(Request $request)
    {
        $admin = $request->user();

        abort_unless($admin?->role === 'admin', 403);

        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'pending');
        $allowedStatuses = ['pending', 'approved', 'rejected', 'closed', 'all'];
        $status = in_array($status, $allowedStatuses, true) ? $status : 'pending';
        $perPage = (int) $request->query('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;
        $sortBy = (string) $request->query('sort_by', 'requested_at');
        $sortDirection = strtolower((string) $request->query('sort_direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['requested_at', 'reviewed_at', 'last_message_at', 'status', 'subject', 'requester', 'provider'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'requested_at';

        $baseQuery = ChatThread::query()
            ->with([
                'provider.providerProfile',
                'providerUser.providerBranch',
                'providerUser.providerRole',
                'ticketReviewer',
                'opener.providerBranch',
                'closer',
                'lastMessage.sender',
            ])
            ->where('conversation_type', self::TYPE_PROVIDER_ADMIN)
            ->whereIn('ticket_status', ['pending', 'approved', 'rejected', 'closed'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('ticket_subject', 'like', "%{$search}%")
                        ->orWhere('ticket_body', 'like', "%{$search}%")
                        ->orWhere('ticket_rejection_reason', 'like', "%{$search}%")
                        ->orWhereHas('provider', function ($providerQuery) use ($search) {
                            $providerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhereHas('providerProfile', function ($profileQuery) use ($search) {
                                    $profileQuery
                                        ->where('phone_number', 'like', "%{$search}%")
                                        ->orWhere('category', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('providerUser', function ($requesterQuery) use ($search) {
                            $requesterQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%")
                                ->orWhereHas('providerBranch', function ($branchQuery) use ($search) {
                                    $branchQuery->where('branch_name', 'like', "%{$search}%");
                                })
                                ->orWhereHas('providerRole', function ($roleQuery) use ($search) {
                                    $roleQuery->where('role_name', 'like', "%{$search}%");
                                });
                        })
                        ->orWhereHas('ticketReviewer', function ($reviewerQuery) use ($search) {
                            $reviewerQuery
                                ->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            });

        $statusCounts = (clone $baseQuery)
            ->select('ticket_status', DB::raw('count(*) as aggregate'))
            ->groupBy('ticket_status')
            ->pluck('aggregate', 'ticket_status');

        $allThreads = (clone $baseQuery)
            ->when($status !== 'all', fn ($query) => $query->where('ticket_status', $status))
            ->get()
            ->sortBy(function (ChatThread $thread) use ($sortBy) {
                return match ($sortBy) {
                    'reviewed_at' => optional($thread->ticket_reviewed_at)->timestamp ?: 0,
                    'last_message_at' => optional($thread->last_message_at)->timestamp ?: 0,
                    'status' => $thread->ticket_status ?: '',
                    'subject' => Str::lower($thread->ticket_subject ?: ''),
                    'requester' => Str::lower(($thread->providerUser ?: $thread->provider)?->name ?: ''),
                    'provider' => Str::lower($thread->provider?->name ?: ''),
                    default => optional($thread->ticket_requested_at ?: $thread->created_at)->timestamp ?: 0,
                };
            }, SORT_REGULAR, $sortDirection === 'desc')
            ->values();

        $requestedThreadId = (int) $request->query('thread');
        $page = max(1, (int) $request->query('page', 1));
        $threads = new LengthAwarePaginator(
            $allThreads->forPage($page, $perPage)->values(),
            $allThreads->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
        $activeThread = $allThreads->firstWhere('id', $requestedThreadId) ?: $threads->getCollection()->first();
        $summary = [
            'total' => (int) $statusCounts->sum(),
            'pending' => (int) ($statusCounts['pending'] ?? 0),
            'approved' => (int) ($statusCounts['approved'] ?? 0),
            'completed' => (int) ($statusCounts['rejected'] ?? 0) + (int) ($statusCounts['closed'] ?? 0),
        ];

        return view('admin.tickets.index', [
            'threads' => $threads,
            'activeThread' => $activeThread,
            'search' => $search,
            'status' => $status,
            'perPage' => $perPage,
            'sortBy' => $sortBy,
            'sortDirection' => $sortDirection,
            'statusCounts' => $statusCounts,
            'summary' => $summary,
        ]);
    }

    public function providerIndex(Request $request)
    {
        $providerUser = $request->user();

        abort_unless($providerUser?->role === 'provider', 403);

        $adminThread = $this->activeProviderAdminThread($providerUser);
        $adminThread->loadMissing(['provider.providerProfile', 'providerUser.providerBranch', 'ticketReviewer']);

        $threads = $this->providerChatThreads($providerUser);
        $internalContacts = $this->internalChatContacts($providerUser);
        $threads->each(function (ChatThread $thread) use ($providerUser) {
            $thread->setAttribute('unread_count', $this->unreadCount($thread, $this->readerRoleForUser($thread, $providerUser)));
        });

        $requestedThreadId = (int) $request->query('thread');
        $listOnly = $request->boolean('list');
        $requestedThread = $requestedThreadId > 0 ? $threads->firstWhere('id', $requestedThreadId) : null;
        $activeThread = $listOnly ? null : $requestedThread;
        $activeThreadCanChat = $activeThread ? $this->threadChatApproved($activeThread) : false;
        $messages = collect();

        if ($activeThread && $activeThreadCanChat) {
            $messages = $this->messagesFor($activeThread, $providerUser);
            $this->markThreadRead($activeThread, $this->readerRoleForUser($activeThread, $providerUser));
            $activeThread->setAttribute('unread_count', 0);
        }

        return view('provider.pages.chat.index', [
            'adminThread' => $adminThread,
            'threads' => $threads,
            'internalContacts' => $internalContacts,
            'activeThread' => $activeThread,
            'activeThreadCanChat' => $activeThreadCanChat,
            'messages' => $messages,
            'chatConfig' => $this->chatConfig(
                $providerUser,
                $activeThread,
                $threads->pluck('id')->map(fn ($id) => (int) $id)->all(),
                $activeThreadCanChat ? provider_route('provider.chat.messages.store', $activeThread) : null,
                $activeThreadCanChat ? provider_route('provider.chat.read', $activeThread) : null,
                $activeThreadCanChat ? provider_route('provider.chat.messages.index', $activeThread) : null
            ),
        ]);
    }

    public function providerTicketsIndex(Request $request)
    {
        $providerUser = $request->user();

        abort_unless($providerUser?->role === 'provider', 403);

        $adminThread = $this->activeProviderAdminThread($providerUser);
        $adminThread->loadMissing(['provider.providerProfile', 'providerUser.providerBranch', 'ticketReviewer']);

        $isProviderOwner = ProviderMenuAccess::isProviderOwner($providerUser);

        return view('provider.pages.tickets.index', [
            'adminThread' => $adminThread,
            'thread' => $adminThread,
            'canChat' => $this->threadChatApproved($adminThread),
            'isProviderOwner' => $isProviderOwner,
        ]);
    }

    public function providerTicketStore(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user?->role === 'provider'
            && ProviderMenuAccess::userCanAccess($user, 'tickets'),
            403
        );

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:160'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $subject = trim($validated['subject']);
        $body = trim($validated['body']);

        if ($subject === '' || $body === '') {
            return back()
                ->withErrors(['subject' => 'Judul dan detail tiket tidak boleh kosong.'])
                ->withInput();
        }

        $thread = $this->activeProviderAdminThread($user);

        if ($this->threadChatApproved($thread)) {
            return provider_route_redirect('provider.chat.index')
                ->with('success', 'Tiket sudah disetujui. Chat dengan admin sudah terbuka.');
        }

        if ($thread->ticket_status === 'pending') {
            return provider_route_redirect('provider.tickets.index')
                ->with('success', 'Tiket chat kamu masih menunggu persetujuan admin.');
        }

        $thread->forceFill([
            'ticket_status' => 'pending',
            'ticket_subject' => $subject,
            'ticket_body' => $body,
            'ticket_rejection_reason' => null,
            'ticket_requested_at' => now(),
            'ticket_reviewed_at' => null,
            'ticket_reviewed_by' => null,
            'opened_by_user_id' => $user->id,
            'closed_by_user_id' => null,
            'closed_at' => null,
            'status' => 'open',
        ])->save();

        $this->notificationService()->createForUsers(
            $this->notificationService()->adminRecipients(),
            'ticket.pending',
            'Tiket chat baru',
            ($user->name ?: 'Provider') . ' mengajukan tiket chat ke admin.',
            route('admin.tickets.index', ['status' => 'pending', 'thread' => $thread->id]),
            [
                'thread_id' => (int) $thread->id,
                'provider_id' => (int) $thread->provider_id,
                'subject' => $subject,
            ],
            (int) $user->id
        );

        return provider_route_redirect('provider.tickets.index')
            ->with('success', 'Tiket chat berhasil diajukan. Admin akan meninjau tiket ini.');
    }

    public function providerInternalStart(Request $request): RedirectResponse
    {
        $user = $request->user();

        abort_unless(
            $user?->role === 'provider'
            && ProviderMenuAccess::userCanAccess($user, 'chat'),
            403
        );

        $providerId = ProviderMenuAccess::providerOwnerId($user);
        $contactIds = $this->internalChatContacts($user)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $validated = $request->validate([
            'contact_user_id' => ['required', 'integer', Rule::in($contactIds)],
        ]);

        $contact = $this->internalChatContacts($user)->firstWhere('id', (int) $validated['contact_user_id']);
        abort_unless($contact, 404);

        $thread = $this->ensureInternalThread($providerId, $user, $contact);

        return provider_route_redirect('provider.chat.index', ['thread' => $thread->id])
            ->with('success', 'Chat internal dibuka. History percakapan tetap tersimpan.');
    }

    public function providerInternalTicketStore(Request $request): RedirectResponse
    {
        return $this->providerInternalStart($request);
    }

    public function adminStore(Request $request, ChatThread $thread): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless($this->threadChatApproved($thread), 403, 'Tiket chat belum disetujui.');

        return $this->storeMessage($request, $thread, 'admin');
    }

    public function adminMessages(Request $request, ChatThread $thread): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless(in_array($thread->ticket_status, ['approved', 'closed'], true), 403);

        return $this->messagesResponse($request, $thread, $request->user());
    }

    public function providerStore(Request $request, ChatThread $thread): JsonResponse
    {
        $user = $request->user();

        abort_unless($this->providerCanChatThread($user, $thread), 403);

        return $this->storeMessage($request, $thread, $this->senderRoleForUser($thread, $user));
    }

    public function providerMessages(Request $request, ChatThread $thread): JsonResponse
    {
        $user = $request->user();

        abort_unless(
            $user?->role === 'provider'
            && ProviderMenuAccess::userCanAccess($user, 'chat')
            && $this->providerCanAccessThread($user, $thread)
            && in_array($thread->ticket_status, ['approved', 'closed'], true),
            403
        );

        return $this->messagesResponse($request, $thread, $user);
    }

    public function adminRead(Request $request, ChatThread $thread): JsonResponse
    {
        abort_unless($request->user()?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless($this->threadChatApproved($thread), 403);

        $this->markThreadRead($thread, 'admin');

        return response()->json([
            'ok' => true,
            'thread' => $this->threadStatePayload($thread),
        ]);
    }

    public function providerRead(Request $request, ChatThread $thread): JsonResponse
    {
        $user = $request->user();

        abort_unless($this->providerCanChatThread($user, $thread), 403);

        $this->markThreadRead($thread, $this->readerRoleForUser($thread, $user));

        return response()->json([
            'ok' => true,
            'thread' => $this->threadStatePayload($thread),
        ]);
    }

    public function adminTicketApprove(Request $request, ChatThread $thread): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless(in_array($thread->ticket_status, ['pending', 'rejected'], true), 404);

        $thread->forceFill([
            'ticket_status' => 'approved',
            'ticket_rejection_reason' => null,
            'ticket_reviewed_at' => now(),
            'ticket_reviewed_by' => $admin->id,
            'last_admin_read_at' => now(),
            'closed_by_user_id' => null,
            'closed_at' => null,
            'status' => 'open',
        ])->save();

        $this->notifyProviderParticipants(
            $thread,
            'tickets',
            'ticket.approved',
            'Tiket chat disetujui',
            'Admin menyetujui tiket chat. Chat sudah bisa digunakan.',
            'provider.chat.index',
            ['thread' => $thread->id],
            (int) $admin->id
        );

        $message = 'Tiket disetujui. Provider sekarang bisa chat dengan admin.';

        if ($request->input('return_to') === 'chat') {
            return redirect()
                ->route('admin.chat.index', ['tab' => 'tickets', 'ticket' => $thread->id])
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.tickets.index', ['status' => 'approved', 'thread' => $thread->id])
            ->with('success', $message);
    }

    public function adminTicketReject(Request $request, ChatThread $thread): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless($thread->ticket_status === 'pending', 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        $thread->forceFill([
            'ticket_status' => 'rejected',
            'ticket_rejection_reason' => $reason,
            'ticket_reviewed_at' => now(),
            'ticket_reviewed_by' => $admin->id,
        ])->save();

        $this->notifyProviderParticipants(
            $thread,
            'tickets',
            'ticket.rejected',
            'Tiket chat ditolak',
            $reason !== '' ? $reason : 'Admin menolak pengajuan tiket chat.',
            'provider.tickets.index',
            [],
            (int) $admin->id
        );

        $message = 'Tiket chat ditolak.';

        if ($request->input('return_to') === 'chat') {
            return redirect()
                ->route('admin.chat.index', ['tab' => 'tickets', 'ticket' => $thread->id])
                ->with('success', $message);
        }

        return redirect()
            ->route('admin.tickets.index', ['status' => 'rejected', 'thread' => $thread->id])
            ->with('success', $message);
    }

    public function adminTicketEnd(Request $request, ChatThread $thread): RedirectResponse
    {
        $admin = $request->user();

        abort_unless($admin?->role === 'admin', 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN, 404);
        abort_unless($this->threadChatApproved($thread), 404);

        $reason = $this->closeThread($request, $thread, $admin, 'Chat diakhiri oleh admin.', 'admin');

        $this->notifyProviderParticipants(
            $thread,
            'tickets',
            'ticket.closed',
            'Chat diakhiri admin',
            $reason . ' Ajukan tiket ulang untuk membuka chat lagi.',
            'provider.tickets.index',
            [],
            (int) $admin->id
        );

        return redirect()
            ->route('admin.tickets.index', ['status' => 'closed', 'thread' => $thread->id])
            ->with('success', 'Chat berhasil diakhiri. Provider harus mengajukan tiket ulang untuk chat lagi.');
    }

    public function providerInternalTicketApprove(Request $request, ChatThread $thread): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->role === 'provider' && ProviderMenuAccess::isProviderOwner($user), 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH, 404);
        abort_unless((int) $thread->provider_id === (int) $user->id, 403);
        abort_unless(in_array($thread->ticket_status, ['pending', 'rejected'], true), 404);

        $thread->forceFill([
            'ticket_status' => 'approved',
            'ticket_rejection_reason' => null,
            'ticket_reviewed_at' => now(),
            'ticket_reviewed_by' => $user->id,
            'last_provider_read_at' => now(),
            'closed_by_user_id' => null,
            'closed_at' => null,
            'status' => 'open',
        ])->save();

        if ($thread->branchUser) {
            $this->notificationService()->createForUser(
                $thread->branchUser,
                'ticket.internal.approved',
                'Tiket chat disetujui pusat',
                'Provider pusat menyetujui tiket chat internal. Chat sudah bisa digunakan.',
                provider_route('provider.chat.index', ['thread' => $thread->id], true, true),
                [
                    'thread_id' => (int) $thread->id,
                    'provider_id' => (int) $thread->provider_id,
                ]
            );
        }

        return provider_route_redirect('provider.tickets.index', ['thread' => $thread->id])
            ->with('success', 'Tiket internal disetujui. Chat pusat-cabang sudah terbuka.');
    }

    public function providerInternalTicketReject(Request $request, ChatThread $thread): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user?->role === 'provider' && ProviderMenuAccess::isProviderOwner($user), 403);
        abort_unless($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH, 404);
        abort_unless((int) $thread->provider_id === (int) $user->id, 403);
        abort_unless($thread->ticket_status === 'pending', 404);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));

        $thread->forceFill([
            'ticket_status' => 'rejected',
            'ticket_rejection_reason' => $reason,
            'ticket_reviewed_at' => now(),
            'ticket_reviewed_by' => $user->id,
        ])->save();

        if ($thread->branchUser) {
            $this->notificationService()->createForUser(
                $thread->branchUser,
                'ticket.internal.rejected',
                'Tiket chat ditolak pusat',
                $reason !== '' ? $reason : 'Provider pusat menolak pengajuan chat internal.',
                provider_route('provider.tickets.index', [], true, true),
                [
                    'thread_id' => (int) $thread->id,
                    'provider_id' => (int) $thread->provider_id,
                ]
            );
        }

        return provider_route_redirect('provider.tickets.index', ['thread' => $thread->id])
            ->with('success', 'Tiket internal ditolak.');
    }

    public function providerTicketEnd(Request $request, ChatThread $thread): RedirectResponse
    {
        $user = $request->user();

        abort_unless($this->providerCanEndThread($user, $thread), 403);
        abort_unless($this->threadChatApproved($thread), 404);

        $reason = $this->closeThread($request, $thread, $user, 'Chat diakhiri oleh provider pusat.', 'provider_owner');

        if ($thread->branchUser) {
            $this->notificationService()->createForUser(
                $thread->branchUser,
                'ticket.internal.closed',
                'Chat diakhiri provider pusat',
                $reason . ' Ajukan tiket ulang untuk membuka chat lagi.',
                provider_route('provider.tickets.index', [], true, true),
                [
                    'thread_id' => (int) $thread->id,
                    'provider_id' => (int) $thread->provider_id,
                ]
            );
        }

        return provider_route_redirect('provider.tickets.index', ['thread' => $thread->id])
            ->with('success', 'Chat internal berhasil diakhiri. Cabang harus mengajukan tiket ulang untuk chat lagi.');
    }

    private function providerOwners(string $search = ''): Collection
    {
        return User::query()
            ->with('providerProfile')
            ->where('role', 'provider')
            ->whereNull('provider_id')
            ->whereNull('provider_role_id')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhereHas('providerProfile', function ($profileQuery) use ($search) {
                            $profileQuery->where('phone_number', 'like', "%{$search}%")
                                ->orWhere('category', 'like', "%{$search}%");
                        })
                        ->orWhereHas('branchAccounts', function ($branchQuery) use ($search) {
                            $branchQuery->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%")
                                ->orWhere('username', 'like', "%{$search}%");
                        });
                });
            })
            ->orderBy('name')
            ->get();
    }

    private function branchAccounts(int $providerId): Collection
    {
        return User::query()
            ->with('providerBranch')
            ->where('role', 'provider')
            ->where('provider_id', $providerId)
            ->where(function ($query) {
                $query->whereNotNull('provider_role_id')
                    ->orWhereNotNull('branch_id');
            })
            ->orderBy('name')
            ->get();
    }

    private function internalChatContacts(User $user): Collection
    {
        $providerId = ProviderMenuAccess::providerOwnerId($user);
        $owner = User::query()
            ->with('providerBranch')
            ->whereKey($providerId)
            ->where('role', 'provider')
            ->first();

        $branchAccounts = $this->branchAccounts($providerId);
        $contacts = ProviderMenuAccess::isProviderOwner($user)
            ? $branchAccounts
            : collect([$owner])->merge($branchAccounts);

        return $contacts
            ->filter(fn (?User $contact) => $contact
                && (int) $contact->id !== (int) $user->id
                && ProviderMenuAccess::userCanAccess($contact, 'chat'))
            ->unique(fn (User $contact) => (int) $contact->id)
            ->values();
    }

    private function activeProviderAdminThread(User $user): ChatThread
    {
        $providerId = ProviderMenuAccess::providerOwnerId($user);

        $thread = ChatThread::query()
            ->where('provider_id', $providerId)
            ->where('conversation_type', self::TYPE_PROVIDER_ADMIN)
            ->where('ticket_status', '!=', 'closed')
            ->where(function ($query) use ($user) {
                $query->where('provider_user_id', $user->id);

                if (ProviderMenuAccess::isProviderOwner($user)) {
                    $query->orWhereNull('provider_user_id');
                }
            })
            ->latest('updated_at')
            ->first();

        if ($thread) {
            return $thread;
        }

        return ChatThread::query()->create([
            'provider_id' => $providerId,
            'provider_user_id' => $user->id,
            'conversation_type' => self::TYPE_PROVIDER_ADMIN,
            'status' => 'open',
            'ticket_status' => 'none',
            'opened_by_user_id' => $user->id,
        ]);
    }

    private function activeBranchInternalThread(User $branchUser): ?ChatThread
    {
        if (ProviderMenuAccess::isProviderOwner($branchUser)) {
            return null;
        }

        $owner = User::query()->whereKey(ProviderMenuAccess::providerOwnerId($branchUser))->first();

        return $owner ? $this->ensureInternalThread(ProviderMenuAccess::providerOwnerId($branchUser), $branchUser, $owner) : null;
    }

    private function activeProviderBranchThread(int $providerId, int $branchUserId, ?int $openedByUserId = null): ChatThread
    {
        $owner = User::query()->whereKey($providerId)->first();
        $branchUser = User::query()->whereKey($branchUserId)->first();

        if ($owner && $branchUser) {
            return $this->ensureInternalThread($providerId, $owner, $branchUser);
        }

        $thread = ChatThread::query()
            ->where('provider_id', $providerId)
            ->where('conversation_type', self::TYPE_PROVIDER_BRANCH)
            ->where('branch_user_id', $branchUserId)
            ->where('ticket_status', '!=', 'closed')
            ->latest('updated_at')
            ->first();

        if ($thread) {
            return $thread;
        }

        return ChatThread::query()->create([
            'provider_id' => $providerId,
            'provider_user_id' => $providerId,
            'branch_user_id' => $branchUserId,
            'conversation_type' => self::TYPE_PROVIDER_BRANCH,
            'status' => 'open',
            'ticket_status' => 'none',
            'opened_by_user_id' => $openedByUserId ?: $branchUserId,
        ]);
    }

    private function ensureInternalThread(int $providerId, User $firstUser, User $secondUser): ChatThread
    {
        abort_if((int) $firstUser->id === (int) $secondUser->id, 422, 'Kontak chat tidak valid.');
        abort_if(ProviderMenuAccess::providerOwnerId($firstUser) !== $providerId, 403);
        abort_if(ProviderMenuAccess::providerOwnerId($secondUser) !== $providerId, 403);

        [$slotAUserId, $slotBUserId] = $this->internalThreadPair($providerId, (int) $firstUser->id, (int) $secondUser->id);

        $thread = ChatThread::query()
            ->where('provider_id', $providerId)
            ->where('conversation_type', self::TYPE_PROVIDER_BRANCH)
            ->where('provider_user_id', $slotAUserId)
            ->where('branch_user_id', $slotBUserId)
            ->first();

        $payload = [
            'provider_user_id' => $slotAUserId,
            'branch_user_id' => $slotBUserId,
            'conversation_type' => self::TYPE_PROVIDER_BRANCH,
            'ticket_status' => 'approved',
            'ticket_subject' => 'Chat internal provider',
            'ticket_body' => 'Percakapan internal provider.',
            'ticket_rejection_reason' => null,
            'ticket_requested_at' => $thread?->ticket_requested_at ?: now(),
            'ticket_reviewed_at' => $thread?->ticket_reviewed_at ?: now(),
            'ticket_reviewed_by' => $thread?->ticket_reviewed_by,
            'closed_by_user_id' => null,
            'closed_at' => null,
            'status' => 'open',
        ];

        if ($thread) {
            $thread->forceFill($payload)->save();

            return $thread->refresh()->load(['provider', 'providerUser.providerBranch', 'branchUser.providerBranch']);
        }

        return ChatThread::query()->create(array_merge($payload, [
            'provider_id' => $providerId,
            'opened_by_user_id' => $firstUser->id,
        ]))->load(['provider', 'providerUser.providerBranch', 'branchUser.providerBranch']);
    }

    private function internalThreadPair(int $providerId, int $firstUserId, int $secondUserId): array
    {
        if ($firstUserId === $providerId || $secondUserId === $providerId) {
            return [$providerId, $firstUserId === $providerId ? $secondUserId : $firstUserId];
        }

        $ids = [$firstUserId, $secondUserId];
        sort($ids);

        return $ids;
    }

    private function internalTicketsForOwner(int $providerId): Collection
    {
        return ChatThread::query()
            ->with(['provider', 'branchUser.providerBranch', 'ticketReviewer', 'lastMessage.sender'])
            ->where('provider_id', $providerId)
            ->where('conversation_type', self::TYPE_PROVIDER_BRANCH)
            ->whereIn('ticket_status', ['pending', 'approved', 'rejected', 'closed'])
            ->get()
            ->sortByDesc(fn (ChatThread $thread) => optional(
                $thread->ticket_requested_at ?: $thread->ticket_reviewed_at ?: $thread->last_message_at ?: $thread->updated_at
            )->timestamp ?: 0)
            ->values();
    }

    private function providerChatThreads(User $user): Collection
    {
        $providerId = ProviderMenuAccess::providerOwnerId($user);
        $isProviderOwner = ProviderMenuAccess::isProviderOwner($user);

        return ChatThread::query()
            ->with(['provider.providerProfile', 'providerUser.providerBranch', 'branchUser.providerBranch', 'lastMessage.sender'])
            ->where(function ($query) use ($user, $providerId, $isProviderOwner) {
                $query->where(function ($adminQuery) use ($user, $providerId, $isProviderOwner) {
                    $adminQuery->where('conversation_type', self::TYPE_PROVIDER_ADMIN)
                        ->where('provider_id', $providerId)
                        ->where('ticket_status', 'approved')
                        ->whereNull('closed_at')
                        ->where(function ($participantQuery) use ($user, $isProviderOwner) {
                            $participantQuery->where('provider_user_id', $user->id);

                            if ($isProviderOwner) {
                                $participantQuery->orWhereNull('provider_user_id');
                            }
                        });
                });

                $query->orWhere(function ($internalQuery) use ($user, $providerId) {
                    $internalQuery->where('conversation_type', self::TYPE_PROVIDER_BRANCH)
                        ->where('provider_id', $providerId)
                        ->where('ticket_status', 'approved')
                        ->where(function ($participantQuery) use ($user) {
                            $participantQuery->where('provider_user_id', $user->id)
                                ->orWhere('branch_user_id', $user->id);
                        });
                });
            })
            ->get()
            ->sortByDesc(fn (ChatThread $thread) => optional(
                $thread->last_message_at ?: $thread->ticket_reviewed_at ?: $thread->ticket_requested_at ?: $thread->updated_at
            )->timestamp ?: 0)
            ->values();
    }

    private function threadChatApproved(ChatThread $thread): bool
    {
        if ($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH) {
            return $thread->ticket_status === 'approved';
        }

        return $thread->ticket_status === 'approved'
            && $thread->status !== 'closed'
            && $thread->closed_at === null;
    }

    private function messagesFor(ChatThread $thread, User $viewer): Collection
    {
        return $thread->messages()
            ->with('sender')
            ->oldest()
            ->get()
            ->map(fn (ChatMessage $message) => ChatMessagePresenter::make($message, $viewer, $thread));
    }

    private function storeMessage(Request $request, ChatThread $thread, string $senderRole): JsonResponse
    {
        $validated = $request->validate([
            'body' => ['nullable', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:4096'],
        ]);

        $user = $request->user();
        $body = trim((string) ($validated['body'] ?? ''));
        $image = $request->file('image');

        if ($body === '' && ! $image) {
            return response()->json([
                'message' => 'Pesan atau gambar tidak boleh kosong.',
            ], 422);
        }

        $attachment = null;

        if ($image) {
            $attachment = [
                'path' => $image->store('chat-images', 'public'),
                'name' => $image->getClientOriginalName(),
                'mime' => $image->getMimeType(),
                'size' => $image->getSize(),
            ];
        }

        try {
            $message = DB::transaction(function () use ($thread, $user, $senderRole, $body, $attachment) {
                $payload = [
                    'sender_id' => $user->id,
                    'sender_role' => $senderRole,
                    'body' => $body,
                ];

                if ($attachment) {
                    $payload = array_merge($payload, [
                        'attachment_path' => $attachment['path'],
                        'attachment_name' => $attachment['name'],
                        'attachment_mime' => $attachment['mime'],
                        'attachment_size' => $attachment['size'],
                    ]);
                }

                $message = $thread->messages()->create($payload);

                $thread->forceFill([
                    'last_message_id' => $message->id,
                    'last_message_at' => $message->created_at,
                    $this->readColumnForRole($senderRole) => now(),
                ])->save();

                return $message;
            });
        } catch (\Throwable $error) {
            if ($attachment) {
                Storage::disk('public')->delete($attachment['path']);
            }

            throw $error;
        }

        $message->load('sender');

        try {
            broadcast(new ChatMessageSent($message))->toOthers();
        } catch (\Throwable) {
            // The sender should still receive a successful JSON response if realtime is offline.
        }

        try {
            $this->notifyChatRecipients($thread, $message, $senderRole, $user);
        } catch (\Throwable) {
            // Chat delivery is already persisted; notification failures must not block the composer.
        }

        return response()->json([
            'message' => ChatMessagePresenter::make($message, $user, $thread),
        ]);
    }

    private function messagesResponse(Request $request, ChatThread $thread, User $viewer): JsonResponse
    {
        $afterId = max(0, (int) $request->query('after_id', 0));
        $messages = $thread->messages()
            ->with('sender')
            ->when($afterId > 0, fn ($query) => $query->where('id', '>', $afterId))
            ->oldest()
            ->get()
            ->map(fn (ChatMessage $message) => ChatMessagePresenter::make($message, $viewer, $thread))
            ->values();

        return response()->json([
            'messages' => $messages,
            'thread' => $this->threadStatePayload($thread),
        ]);
    }

    private function threadStatePayload(ChatThread $thread): array
    {
        return [
            'id' => (int) $thread->id,
            'conversation_type' => $thread->conversation_type ?: self::TYPE_PROVIDER_ADMIN,
            'ticket_status' => $thread->ticket_status ?: 'none',
            'status' => $thread->status ?: 'open',
            'ticket_rejection_reason' => $thread->ticket_rejection_reason,
            'closed_at' => $thread->closed_at?->toIso8601String(),
            'last_admin_read_at' => $thread->last_admin_read_at?->toIso8601String(),
            'last_provider_read_at' => $thread->last_provider_read_at?->toIso8601String(),
            'last_branch_read_at' => $thread->last_branch_read_at?->toIso8601String(),
            'read_receipts' => [
                'last_admin_read_at' => $thread->last_admin_read_at?->toIso8601String(),
                'last_provider_read_at' => $thread->last_provider_read_at?->toIso8601String(),
                'last_branch_read_at' => $thread->last_branch_read_at?->toIso8601String(),
            ],
        ];
    }

    private function closeThread(Request $request, ChatThread $thread, User $closer, string $defaultReason, string $readerRole): string
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $reason = trim((string) ($validated['reason'] ?? ''));
        $reason = $reason !== '' ? $reason : $defaultReason;

        $thread->forceFill([
            'ticket_status' => 'closed',
            'status' => 'closed',
            'ticket_rejection_reason' => $reason,
            'ticket_reviewed_at' => now(),
            'ticket_reviewed_by' => $closer->id,
            'closed_by_user_id' => $closer->id,
            'closed_at' => now(),
            $this->readColumnForRole($readerRole) => now(),
        ])->save();

        try {
            broadcast(new ChatThreadUpdated($thread));
        } catch (\Throwable) {
            // Polling will still pick up the closed state when realtime is unavailable.
        }

        return $reason;
    }

    private function notifyChatRecipients(ChatThread $thread, ChatMessage $message, string $senderRole, User $sender): void
    {
        $preview = $message->body !== ''
            ? Str::limit($message->body, 120)
            : ($message->attachment_path ? 'Mengirim gambar' : '');

        if ($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN) {
            if ($senderRole === 'provider') {
                $this->notificationService()->createForUsers(
                    $this->notificationService()->adminRecipients(),
                    'chat.message',
                    'Pesan baru dari ' . ($sender->name ?: 'Provider'),
                    $preview,
                    route('admin.chat.index', ['thread' => $thread->id]),
                    [
                        'thread_id' => (int) $thread->id,
                        'message_id' => (int) $message->id,
                        'provider_id' => (int) $thread->provider_id,
                    ],
                    (int) $sender->id
                );

                return;
            }

            $this->notifyProviderParticipants(
                $thread,
                'chat',
                'chat.message',
                'Pesan baru dari admin',
                $preview,
                'provider.chat.index',
                ['thread' => $thread->id],
                (int) $sender->id,
                ['message_id' => (int) $message->id]
            );

            return;
        }

        $recipient = $senderRole === 'provider_owner' ? $thread->branchUser : $thread->providerUser;

        if (! $recipient || (int) $recipient->id === (int) $sender->id) {
            return;
        }

        $this->notificationService()->createForUser(
            $recipient,
            'chat.message',
            'Pesan baru dari ' . ($sender->name ?: 'Provider'),
            $preview,
            provider_route('provider.chat.index', ['thread' => $thread->id], true, ! ProviderMenuAccess::isProviderOwner($recipient)),
            [
                'thread_id' => (int) $thread->id,
                'message_id' => (int) $message->id,
                'provider_id' => (int) $thread->provider_id,
            ]
        );
    }

    private function notifyProviderParticipants(
        ChatThread $thread,
        string $menuKey,
        string $type,
        string $title,
        string $body,
        string $routeName,
        array $routeParams = [],
        ?int $exceptUserId = null,
        array $extraData = []
    ): void {
        $this->providerParticipants($thread, $menuKey)
            ->filter(fn (User $user) => ! $exceptUserId || (int) $user->id !== (int) $exceptUserId)
            ->each(function (User $user) use ($thread, $type, $title, $body, $routeName, $routeParams, $extraData) {
                $this->notificationService()->createForUser(
                    $user,
                    $type,
                    $title,
                    $body,
                    provider_route($routeName, $routeParams, true, ! ProviderMenuAccess::isProviderOwner($user)),
                    array_merge([
                        'thread_id' => (int) $thread->id,
                        'provider_id' => (int) $thread->provider_id,
                    ], $extraData)
                );
            });
    }

    private function providerParticipants(ChatThread $thread, ?string $menuKey = null): Collection
    {
        if ($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH) {
            return collect([$thread->providerUser ?: $thread->provider, $thread->branchUser])
                ->filter(fn (?User $user) => $user && ProviderMenuAccess::userCanAccess($user, $menuKey))
                ->unique(fn (User $user) => (int) $user->id)
                ->values();
        }

        $participant = $thread->providerUser ?: $thread->provider;

        return collect([$participant])
            ->filter(fn (?User $user) => $user && ProviderMenuAccess::userCanAccess($user, $menuKey))
            ->unique(fn (User $user) => (int) $user->id)
            ->values();
    }

    private function notificationService(): AppNotificationService
    {
        return app(AppNotificationService::class);
    }

    private function unreadCount(ChatThread $thread, string $readerRole): int
    {
        $readAt = $thread->{$this->readColumnForRole($readerRole)};
        $oppositeRoles = $this->oppositeSenderRoles($thread, $readerRole);

        return $thread->messages()
            ->whereIn('sender_role', $oppositeRoles)
            ->when($readAt, fn ($query) => $query->where('created_at', '>', $readAt))
            ->count();
    }

    private function markThreadRead(ChatThread $thread, string $readerRole): void
    {
        $column = $this->readColumnForRole($readerRole);
        $now = now();

        ChatThread::query()
            ->whereKey($thread->getKey())
            ->update([
                $column => $now,
                'updated_at' => $now,
            ]);

        $thread->setAttribute($column, $now);
        $thread->setAttribute('updated_at', $now);
        $thread->syncOriginalAttributes([$column, 'updated_at']);
    }

    private function providerCanChatThread(?User $user, ChatThread $thread): bool
    {
        return $user?->role === 'provider'
            && ProviderMenuAccess::userCanAccess($user, 'chat')
            && $this->threadChatApproved($thread)
            && $this->providerCanAccessThread($user, $thread);
    }

    private function providerCanEndThread(?User $user, ChatThread $thread): bool
    {
        return false;
    }

    private function providerCanAccessThread(User $user, ChatThread $thread): bool
    {
        if ($this->threadType($thread) === self::TYPE_PROVIDER_ADMIN) {
            if ($thread->provider_user_id) {
                return (int) $thread->provider_user_id === (int) $user->id;
            }

            return ProviderMenuAccess::isProviderOwner($user)
                && (int) $thread->provider_id === (int) $user->id;
        }

        return in_array((int) $user->id, [
                (int) $thread->provider_user_id,
                (int) $thread->branch_user_id,
            ], true)
            && ProviderMenuAccess::providerOwnerId($user) === (int) $thread->provider_id;
    }

    private function readerRoleForUser(ChatThread $thread, User $user): string
    {
        if ($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH) {
            return (int) $thread->provider_user_id === (int) $user->id
                ? 'provider_owner'
                : 'provider_branch';
        }

        return 'provider';
    }

    private function senderRoleForUser(ChatThread $thread, User $user): string
    {
        return $this->readerRoleForUser($thread, $user);
    }

    private function readColumnForRole(string $role): string
    {
        return match ($role) {
            'admin' => 'last_admin_read_at',
            'provider_branch' => 'last_branch_read_at',
            default => 'last_provider_read_at',
        };
    }

    private function oppositeSenderRoles(ChatThread $thread, string $readerRole): array
    {
        if ($this->threadType($thread) === self::TYPE_PROVIDER_BRANCH) {
            return $readerRole === 'provider_branch'
                ? ['provider_owner']
                : ['provider_branch'];
        }

        return $readerRole === 'admin'
            ? ['provider']
            : ['admin'];
    }

    private function threadType(ChatThread $thread): string
    {
        return $thread->conversation_type ?: self::TYPE_PROVIDER_ADMIN;
    }

    private function chatConfig(User $user, ?ChatThread $activeThread, array $threadIds, ?string $sendUrl, ?string $readUrl, ?string $messagesUrl = null): array
    {
        $connection = config('broadcasting.connections.reverb', []);
        $options = $connection['options'] ?? [];
        $scheme = (string) ($options['scheme'] ?? 'http');
        $host = (string) ($options['host'] ?? request()->getHost());

        return [
            'currentUserId' => (int) $user->id,
            'activeThreadId' => $activeThread ? (int) $activeThread->id : null,
            'conversationType' => $activeThread ? $this->threadType($activeThread) : null,
            'threadIds' => array_values(array_unique($threadIds)),
            'sendUrl' => $sendUrl,
            'readUrl' => $readUrl,
            'messagesUrl' => $messagesUrl,
            'readReceipts' => $activeThread ? $this->threadStatePayload($activeThread)['read_receipts'] : [],
            'csrfToken' => csrf_token(),
            'authEndpoint' => url('/broadcasting/auth'),
            'closedMessage' => 'Chat sudah diakhiri. Ajukan tiket baru untuk membuka sesi chat berikutnya.',
            'broadcast' => [
                'key' => (string) ($connection['key'] ?? ''),
                'host' => $host !== '' ? $host : request()->getHost(),
                'port' => (int) ($options['port'] ?? 8080),
                'scheme' => $scheme,
            ],
        ];
    }
}
