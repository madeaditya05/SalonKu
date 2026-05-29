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
            return back()->with('error', 'Branch accounts cannot update the main provider profile.');
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
                ->with('success', 'Profile has been updated.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Profile update failed. ' . $e->getMessage());
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
            return back()->with('error', 'Branch accounts cannot update provider documents.');
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
        | If documents are already verified, the provider can no longer change them.
        |--------------------------------------------------------------------------
        */

        if ($profile->document_status === 'verified') {
            return back()->with(
                'error',
                'Documents have already been verified by admin and can no longer be modified.'
            );
        }

        $request->validate([
            'ktp_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'business_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ], [
            'ktp_image.image' => 'The ID card file must be an image.',
            'ktp_image.mimes' => 'The ID card format must be jpg, jpeg, png, or webp.',
            'ktp_image.max' => 'The ID card file size must not exceed 4MB.',
            'business_image.image' => 'The business photo file must be an image.',
            'business_image.mimes' => 'The business photo format must be jpg, jpeg, png, or webp.',
            'business_image.max' => 'The business photo file size must not exceed 4MB.',
        ]);

        if (! $request->hasFile('ktp_image') && ! $request->hasFile('business_image')) {
            return back()->with('error', 'Select at least one document to upload.');
        }

        /*
        |--------------------------------------------------------------------------
        | Make sure the provider has 2 documents after submission:
        | 1. Foto KTP
        | 2. Foto Usaha
        |--------------------------------------------------------------------------
        */

        $willHaveKtp = $request->hasFile('ktp_image') || ! empty($profile->ktp_image);
        $willHaveBusinessImage = $request->hasFile('business_image') || ! empty($profile->business_image);

        if (! $willHaveKtp || ! $willHaveBusinessImage) {
            return back()->with(
                'error',
                'Upload the ID card photo and business photo before submitting documents.'
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
            | After documents are submitted successfully, status becomes submitted.
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
                'Provider documents submitted',
                ($user->name ?: 'Provider') . ' submitted documents for verification.',
                route('admin.providers.show', ProviderMenuAccess::providerOwnerId($user)),
                [
                    'provider_id' => ProviderMenuAccess::providerOwnerId($user),
                ],
                (int) $user->id
            );

            return provider_route_redirect('provider.profile')
                ->with('success', 'Documents submitted successfully. Document status is now Submitted and awaiting admin verification.');
        } catch (\Throwable $e) {
            DB::rollBack();

            return back()
                ->withInput()
                ->with('error', 'Document upload failed. ' . $e->getMessage());
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
            'current_password.required' => 'Current password is required.',
            'password.required' => 'New password is required.',
            'password.min' => 'New password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
        ]);

        if (! Hash::check($validated['current_password'], $user->password)) {
            return back()
                ->withErrors(['current_password' => 'Current password is incorrect.'])
                ->withInput();
        }

        User::query()
            ->whereKey($user->id)
            ->update([
                'password' => Hash::make($validated['password']),
            ]);

        return provider_route_redirect('provider.profile')
            ->with('success', 'Password has been updated.');
    }

    private function replaceFile(Request $request, string $field, ?string $oldPath, string $folder): string
    {
        /*
        |--------------------------------------------------------------------------
        | Store the new file first.
        |--------------------------------------------------------------------------
        | After the new file is stored successfully, delete the old file.
        */

        $newPath = $request->file($field)->store($folder, 'public');

        if ($oldPath && Storage::disk('public')->exists($oldPath)) {
            Storage::disk('public')->delete($oldPath);
        }

        return $newPath;
    }
}
