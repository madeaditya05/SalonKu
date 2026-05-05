<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderDocumentVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'provider') {
            return $next($request);
        }

        $profile = $user->providerProfile()->first();

        if (!$profile || $profile->document_status !== 'verified') {
            return redirect()
                ->route('provider.profile')
                ->with('error', 'Lengkapi dan tunggu verifikasi dokumen terlebih dahulu agar semua menu terbuka.');
        }

        return $next($request);
    }
}