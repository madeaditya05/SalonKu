<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function signin(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'login.required' => 'Email atau username wajib diisi.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginType = filter_var($request->login, FILTER_VALIDATE_EMAIL)
            ? 'email'
            : 'username';

        $credentials = [
            $loginType => $request->login,
            'password' => $request->password,
            'role' => 'customer',
        ];

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            return redirect()
                ->route('home')
                ->withErrors([
                    'login' => 'Email/username atau password salah, atau akun bukan customer.',
                ])
                ->withInput($request->only('login'))
                ->with('auth_modal', 'signin');
        }

        $request->session()->regenerate();

        $customer = Auth::user();

        $profile = $customer->customerProfile;

        if (!$profile) {
            $profile = CustomerProfile::create([
                'user_id' => $customer->id,
                'status' => 'active',
            ]);
        }

        if ($profile->status !== 'active') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('home')
                ->withErrors([
                    'login' => 'Akun customer kamu sedang tidak aktif.',
                ])
                ->withInput($request->only('login'))
                ->with('auth_modal', 'signin');
        }

        return redirect()
            ->route('home')
            ->with('success', 'Berhasil masuk sebagai customer.');
    }
}