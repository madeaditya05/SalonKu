<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\AppNotificationService;
use App\Support\ProviderMenuAccess;
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
        $authId = Auth::id();

        if (! $authId) {
            return redirect()->route('provider.login');
        }

        $user = User::query()->findOrFail($authId);

        $profile = ProviderProfile::query()->firstOrCreate(
            ['user_id' => ProviderMenuAccess::providerOwnerId($user)],
            [
                'status' => 'active',
                'document_status' => 'pending',
            ]
        );

        return view('provider.pages.profile.show', compact('user', 'profile'));
    }

    public function edit()
    {
        $authId = Auth::id();

        if (! $authId) {
            return redirect()->route('provider.login');
        }

        $user = User::query()->findOrFail($authId);

        $profile = ProviderProfile::query()->firstOrCreate(
            ['user_id' => ProviderMenuAccess::providerOwnerId($user)],
            [
                'status' => 'active',
                'document_status' => 'pending',
            ]
        );

        return view('provider.pages.profile.edit', compact('user', 'profile'));
    }

    public function update(Request $request)
    {
        $authId = Auth::id();

        if (! $authId) {
            return redirect()->route('provider.login');
        }

        $user = User::query()->findOrFail($authId);

        if (! ProviderMenuAccess::isProviderOwner($user)) {
            return back()->with('error', 'Akun cabang tidak boleh mengubah profil utama provider.');
        }

        $profile = ProviderProfile::query()->firstOrCreate(
            ['user_id' => ProviderMenuAccess::providerOwnerId($user)],
            [
                'status' => 'active',
                'document_status' => 'pending',
            ]
        );

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

            'phone_number' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
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

            $profileData = [
                'phone_number' => $validated['phone_number'] ?? null,
            ];

            if ($request->hasFile('image')) {
                $profileData['image'] = $this->replaceFile(
                    $request,
                    'image',
                    $profile->image,
                    'provider/profile'
                );
            }

            ProviderProfile::query()
                ->whereKey($profile->id)
                ->update($profileData);

            DB::commit();

            return provider_route_redirect('provider.profile')
                ->with('success', 'Profile berhasil diperbarui.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Profile gagal diperbarui. ' . $e->getMessage());
        }
    }

    public function updateDocuments(Request $request)
    {
        $authId = Auth::id();

        if (! $authId) {
            return redirect()->route('provider.login');
        }

        $user = User::query()->findOrFail($authId);

        if (! ProviderMenuAccess::isProviderOwner($user)) {
            return back()->with('error', 'Akun cabang tidak boleh mengubah dokumen provider.');
        }

        $profile = ProviderProfile::query()->firstOrCreate(
            ['user_id' => ProviderMenuAccess::providerOwnerId($user)],
            [
                'status' => 'active',
                'document_status' => 'pending',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Kalau dokumen sudah verified, provider tidak boleh ubah lagi
        |--------------------------------------------------------------------------
        */

        if ($profile->document_status === 'verified') {
            return back()->with(
                'error',
                'Dokumen sudah verified oleh admin dan tidak bisa dimodifikasi lagi.'
            );
        }

        $request->validate([
            'ktp_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'business_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'ktp_image.image' => 'File KTP harus berupa gambar.',
            'ktp_image.mimes' => 'Format KTP harus jpg, jpeg, png, atau webp.',
            'ktp_image.max' => 'Ukuran KTP maksimal 4MB.',
            'business_image.image' => 'File usaha harus berupa gambar.',
            'business_image.mimes' => 'Format foto usaha harus jpg, jpeg, png, atau webp.',
            'business_image.max' => 'Ukuran foto usaha maksimal 4MB.',
        ]);

        if (! $request->hasFile('ktp_image') && ! $request->hasFile('business_image')) {
            return back()->with('error', 'Pilih minimal satu dokumen untuk diupload.');
        }

        /*
        |--------------------------------------------------------------------------
        | Pastikan setelah submit provider punya 2 dokumen:
        | 1. Foto KTP
        | 2. Foto Usaha
        |--------------------------------------------------------------------------
        */

        $willHaveKtp = $request->hasFile('ktp_image') || ! empty($profile->ktp_image);
        $willHaveBusinessImage = $request->hasFile('business_image') || ! empty($profile->business_image);

        if (! $willHaveKtp || ! $willHaveBusinessImage) {
            return back()->with(
                'error',
                'Upload Foto KTP dan Foto Usaha terlebih dahulu sebelum dokumen dikirim.'
            );
        }

        DB::beginTransaction();

        try {
            $profileData = [];

            if ($request->hasFile('ktp_image')) {
                $profileData['ktp_image'] = $this->replaceFile(
                    $request,
                    'ktp_image',
                    $profile->ktp_image,
                    'provider/documents'
                );
            }

            if ($request->hasFile('business_image')) {
                $profileData['business_image'] = $this->replaceFile(
                    $request,
                    'business_image',
                    $profile->business_image,
                    'provider/documents'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | INI BAGIAN PENTING
            |--------------------------------------------------------------------------
            | Setelah dokumen berhasil dikirim, status menjadi submitted.
            | Bukan pending lagi.
            */

            $profileData['document_status'] = 'submitted';
            $profileData['document_note'] = null;

            ProviderProfile::query()
                ->whereKey($profile->id)
                ->update($profileData);

            DB::commit();

            app(AppNotificationService::class)->createForUsers(
                app(AppNotificationService::class)->adminRecipients(),
                'provider.document.submitted',
                'Dokumen provider dikirim',
                ($user->name ?: 'Provider') . ' mengirim dokumen untuk diverifikasi.',
                route('admin.providers.show', ProviderMenuAccess::providerOwnerId($user)),
                [
                    'provider_id' => ProviderMenuAccess::providerOwnerId($user),
                ],
                (int) $user->id
            );

            return provider_route_redirect('provider.profile')
                ->with('success', 'Dokumen berhasil dikirim. Status dokumen sekarang Submitted dan menunggu verifikasi admin.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Dokumen gagal diupload. ' . $e->getMessage());
        }
    }

    public function updatePassword(Request $request)
    {
        $authId = Auth::id();

        if (! $authId) {
            return redirect()->route('provider.login');
        }

        $user = User::query()->findOrFail($authId);

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

        return provider_route_redirect('provider.profile')
            ->with('success', 'Password berhasil diperbarui.');
    }

    private function replaceFile(Request $request, string $field, ?string $oldPath, string $folder): string
    {
        /*
        |--------------------------------------------------------------------------
        | Simpan file baru dulu
        |--------------------------------------------------------------------------
        | Setelah file baru berhasil tersimpan, baru hapus file lama.
        */

        $newPath = $request->file($field)->store($folder, 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }
}
