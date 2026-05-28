<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show()
    {
        $user = $this->adminUser();
        $profile = $this->profileFor($user);

        return view('admin.profile.index', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $user = $this->adminUser();
        $profile = $this->profileFor($user);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'phone_number' => ['nullable', 'string', 'max:50'],
            'position' => ['nullable', 'string', 'max:120'],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], [
            'name.required' => 'Nama admin wajib diisi.',
            'email.required' => 'Email admin wajib diisi.',
            'email.email' => 'Format email belum valid.',
            'email.unique' => 'Email sudah digunakan akun lain.',
            'username.unique' => 'Username sudah digunakan akun lain.',
            'avatar.image' => 'Foto profile harus berupa gambar.',
            'avatar.mimes' => 'Format foto profile harus jpg, jpeg, png, atau webp.',
            'avatar.max' => 'Ukuran foto profile maksimal 2MB.',
        ]);

        DB::beginTransaction();

        try {
            User::query()
                ->whereKey($user->id)
                ->update([
                    'name' => $validated['name'],
                    'username' => $validated['username'] ?? null,
                    'email' => $validated['email'],
                ]);

            $profilePayload = [
                'phone_number' => $validated['phone_number'] ?? null,
                'position' => $validated['position'] ?? null,
                'bio' => $validated['bio'] ?? null,
            ];

            if ($request->hasFile('avatar')) {
                $profilePayload['avatar'] = $this->replaceFile(
                    $request,
                    'avatar',
                    $profile->avatar,
                    'admin/profile'
                );
            }

            AdminProfile::query()
                ->whereKey($profile->id)
                ->update($profilePayload);

            DB::commit();

            return redirect()
                ->route('admin.profile')
                ->with('success', 'Profile admin berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Profile admin gagal diperbarui. ' . $e->getMessage());
        }
    }

    public function updatePassword(Request $request)
    {
        $user = $this->adminUser();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'current_password.required' => 'Password lama wajib diisi.',
            'password.required' => 'Password baru wajib diisi.',
            'password.min' => 'Password baru minimal 8 karakter.',
            'password.confirmed' => 'Konfirmasi password tidak sama.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Password lama tidak sesuai.'])
                ->withInput();
        }

        User::query()
            ->whereKey($user->id)
            ->update([
                'password' => Hash::make($validated['password']),
            ]);

        return redirect()
            ->route('admin.profile')
            ->with('success', 'Password admin berhasil diperbarui.');
    }

    private function adminUser(): User
    {
        $user = Auth::user();

        abort_if(! $user || $user->role !== 'admin', 403, 'Akses ditolak.');

        return $user;
    }

    private function profileFor(User $user): AdminProfile
    {
        return AdminProfile::query()->firstOrCreate(
            ['user_id' => $user->id],
            [
                'phone_number' => null,
                'position' => 'Administrator',
                'avatar' => null,
                'bio' => null,
            ]
        );
    }

    private function replaceFile(Request $request, string $field, ?string $oldPath, string $folder): string
    {
        $newPath = $request->file($field)->store($folder, 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }
}
