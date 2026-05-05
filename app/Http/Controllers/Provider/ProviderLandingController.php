<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProviderLandingController extends Controller
{
    public function index()
    {
        return view('provider.landing');
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'string', 'max:100', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'country_code' => ['required', 'string', 'max:15'],
            'phone_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8'],
            'terms' => ['accepted'],
        ], [
            'first_name.required' => 'First name wajib diisi.',
            'last_name.required' => 'Last name wajib diisi.',
            'username.required' => 'User name wajib diisi.',
            'username.unique' => 'User name sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah terdaftar.',
            'country_code.required' => 'Kode negara wajib dipilih.',
            'phone_number.required' => 'Phone number wajib diisi.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'terms.accepted' => 'Kamu harus menyetujui Terms and Conditions.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'register')
                ->withInput();
        }

        $validated = $validator->validated();

        $fullName = trim($validated['first_name'] . ' ' . $validated['last_name']);
        $phoneNumber = preg_replace('/\s+/', '', $validated['phone_number']);
        $fullPhoneNumber = trim($validated['country_code'] . ' ' . $phoneNumber);

        $user = User::create([
            'name' => $fullName,
            'username' => $validated['username'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'provider',
        ]);

        ProviderProfile::create([
            'user_id' => $user->id,
            'phone_number' => $fullPhoneNumber,
            'status' => 'inactive',
            'document_status' => 'pending',
        ]);

        return redirect()
            ->route('provider.landing')
            ->with('register_success', 'Registration berhasil. Akun provider kamu akan direview admin terlebih dahulu.');
    }

    public function signin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'login_email' => ['required', 'email'],
            'login_password' => ['required', 'string'],
        ], [
            'login_email.required' => 'Email wajib diisi.',
            'login_email.email' => 'Format email tidak valid.',
            'login_password.required' => 'Password wajib diisi.',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator, 'signin')
                ->withInput();
        }

        $credentials = [
            'email' => $request->login_email,
            'password' => $request->login_password,
            'role' => 'provider',
        ];

        $remember = $request->boolean('remember');

        if (!Auth::attempt($credentials, $remember)) {
            return back()
                ->withErrors([
                    'login_email' => 'Email atau password salah.',
                ], 'signin')
                ->withInput();
        }

        $request->session()->regenerate();

        $user = Auth::user();

        if (!$user || $user->role !== 'provider') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'login_email' => 'Akun ini bukan akun provider.',
                ], 'signin')
                ->withInput();
        }

        $providerProfile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'inactive',
                'document_status' => 'pending',
            ]
        );

        if ($providerProfile->status !== 'active') {
            Auth::logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withErrors([
                    'login_email' => 'Akun provider kamu masih menunggu ACC admin.',
                ], 'signin')
                ->withInput();
        }

        return redirect()->route('provider.dashboard');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('provider.landing');
    }
}