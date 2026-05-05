<?php

namespace App\Http\Controllers\Customer\Auth;

use App\Http\Controllers\Controller;
use App\Models\CustomerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function signup(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Nama lengkap wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique' => 'Username sudah digunakan.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        try {
            DB::beginTransaction();

            $customer = User::create([
                'name' => $request->name,
                'username' => $request->username,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'customer',
            ]);

            CustomerProfile::create([
                'user_id' => $customer->id,
                'phone_number' => $request->phone_number,
                'status' => 'active',
            ]);

            Auth::login($customer);

            $request->session()->regenerate();

            DB::commit();

            return redirect()
                ->route('home')
                ->with('success', 'Akun customer berhasil dibuat.');
        } catch (\Throwable $th) {
            DB::rollBack();

            return redirect()
                ->route('home')
                ->withErrors([
                    'signup' => 'Terjadi kesalahan saat membuat akun customer.',
                ])
                ->withInput()
                ->with('auth_modal', 'signup');
        }
    }
}