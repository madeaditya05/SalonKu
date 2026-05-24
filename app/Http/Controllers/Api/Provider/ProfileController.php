<?php

namespace App\Http\Controllers\Api\Provider;

use App\Http\Controllers\Api\ApiController;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\AppNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends ApiController
{
    public function show(Request $request): JsonResponse
    {
        $providerId = $this->providerId($request);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $providerId],
            ['status' => 'active', 'document_status' => 'pending']
        );

        return response()->json([
            'data' => $request->user()->load('providerProfile'),
            'profile' => $profile,
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        abort_if($this->isProviderBranchAccount($request), 403, 'Akun cabang tidak boleh mengubah profil utama provider.');

        $providerId = $this->providerId($request);
        $user = User::findOrFail($providerId);
        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['status' => 'active', 'document_status' => 'pending']
        );

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'username' => ['nullable', 'string', 'max:255', Rule::unique('users', 'username')->ignore($user->id)],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone_number' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        DB::transaction(function () use ($request, $user, $profile, $validated) {
            $user->update([
                'name' => $validated['name'],
                'username' => $validated['username'] ?? null,
                'email' => $validated['email'],
            ]);

            $profile->update([
                'phone_number' => $validated['phone_number'] ?? null,
                'image' => $this->replaceUploadedFile($request, 'image', $profile->image, 'provider/profile'),
            ]);
        });

        return response()->json(['message' => 'Profile berhasil diperbarui.', 'data' => $user->refresh()->load('providerProfile')]);
    }

    public function updateDocuments(Request $request): JsonResponse
    {
        abort_if($this->isProviderBranchAccount($request), 403, 'Akun cabang tidak boleh mengubah dokumen provider.');

        $providerId = $this->providerId($request);
        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $providerId],
            ['status' => 'active', 'document_status' => 'pending']
        );

        if ($profile->document_status === 'verified') {
            return response()->json(['message' => 'Dokumen sudah verified dan tidak bisa dimodifikasi lagi.'], 422);
        }

        $request->validate([
            'ktp_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'business_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        abort_unless($request->hasFile('ktp_image') || $request->hasFile('business_image'), 422, 'Pilih minimal satu dokumen.');

        $willHaveKtp = $request->hasFile('ktp_image') || ! empty($profile->ktp_image);
        $willHaveBusinessImage = $request->hasFile('business_image') || ! empty($profile->business_image);

        abort_unless($willHaveKtp && $willHaveBusinessImage, 422, 'Upload Foto KTP dan Foto Usaha terlebih dahulu.');

        $profile->update([
            'ktp_image' => $this->replaceUploadedFile($request, 'ktp_image', $profile->ktp_image, 'provider/documents'),
            'business_image' => $this->replaceUploadedFile($request, 'business_image', $profile->business_image, 'provider/documents'),
            'document_status' => 'submitted',
            'document_note' => null,
        ]);

        $provider = User::query()->find($providerId);

        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->adminRecipients(),
            'provider.document.submitted',
            'Dokumen provider dikirim',
            (($provider?->name ?: $request->user()?->name) ?: 'Provider') . ' mengirim dokumen untuk diverifikasi.',
            route('admin.providers.show', $providerId),
            [
                'provider_id' => (int) $providerId,
            ],
            (int) $request->user()?->id
        );

        return response()->json(['message' => 'Dokumen berhasil dikirim.', 'data' => $profile->refresh()]);
    }

    public function updatePassword(Request $request): JsonResponse
    {
        $this->providerId($request);
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        abort_unless(Hash::check($validated['current_password'], $user->password), 422, 'Password lama tidak sesuai.');

        $user->update(['password' => Hash::make($validated['password'])]);

        return response()->json(['message' => 'Password berhasil diperbarui.']);
    }
}
