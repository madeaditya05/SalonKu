<?php

use App\Models\ChatThread;
use App\Models\User;
use App\Support\ProviderMenuAccess;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['admin', 'provider', 'provider_branch', 'web']]);

Broadcast::channel('notifications.user.{id}', function (User $user, int $id) {
    return (int) $user->id === (int) $id;
}, ['guards' => ['admin', 'provider', 'provider_branch', 'web']]);

Broadcast::channel('chat.thread.{threadId}', function (User $user, int $threadId) {
    $thread = ChatThread::query()->find($threadId);

    if (! $thread) {
        return false;
    }

    if ($thread->ticket_status !== 'approved') {
        return false;
    }

    if ($user->role === 'admin') {
        return ($thread->conversation_type ?: 'provider_admin') === 'provider_admin';
    }

    if ($user->role !== 'provider' || ! ProviderMenuAccess::userCanAccess($user, 'chat')) {
        return false;
    }

    if (($thread->conversation_type ?: 'provider_admin') === 'provider_branch') {
        return in_array((int) $user->id, [
                (int) $thread->provider_user_id,
                (int) $thread->branch_user_id,
            ], true)
            && ProviderMenuAccess::providerOwnerId($user) === (int) $thread->provider_id;
    }

    if ($thread->provider_user_id) {
        return (int) $thread->provider_user_id === (int) $user->id;
    }

    return ProviderMenuAccess::isProviderOwner($user)
        && (int) $thread->provider_id === (int) $user->id;
}, ['guards' => ['admin', 'provider', 'provider_branch', 'web']]);
