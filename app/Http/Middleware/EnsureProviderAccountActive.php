<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderAccountActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->role !== 'provider') {
            return $next($request);
        }

        $profile = $user->providerProfile;

        if (!$profile || $profile->status !== 'active') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('provider.landing')
                ->with('error', 'Akun provider belum diaktifkan oleh admin.');
        }

        return $next($request);
    }
}