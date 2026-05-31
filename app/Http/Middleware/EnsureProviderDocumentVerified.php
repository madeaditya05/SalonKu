<?php

namespace App\Http\Middleware;

use App\Models\ProviderProfile;
use App\Models\User;
use App\Support\ProviderMenuAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderDocumentVerified
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var User|null $user */
        $user = Auth::guard($request->routeIs('provider-branch.*') ? 'provider_branch' : 'provider')->user() ?: Auth::user();

        if (!$user) {
            return redirect()->route('provider.login');
        }

        if ($user->role !== 'provider') {
            return $next($request);
        }

        $profile = ProviderProfile::where('user_id', ProviderMenuAccess::providerOwnerId($user))->first();

        if (!$profile || $profile->document_status !== 'verified') {
            return provider_route_redirect('provider.profile.edit')
                ->with('error', 'Lengkapi dan tunggu verifikasi dokumen terlebih dahulu agar semua menu terbuka.');
        }

        return $next($request);
    }
}
