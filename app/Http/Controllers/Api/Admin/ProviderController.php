<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProviderController extends ApiController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizeRole($request, 'admin');

        $providers = User::query()
            ->with('providerProfile')
            ->where('role', 'provider')
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('providerProfile', function ($profileQuery) use ($search) {
                            $profileQuery->where('phone_number', 'like', "%{$search}%")
                                ->orWhere('status', 'like', "%{$search}%")
                                ->orWhere('document_status', 'like', "%{$search}%");
                        });
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($providers);
    }

    public function show(Request $request, User $provider): JsonResponse
    {
        $this->authorizeRole($request, 'admin');
        abort_if($provider->role !== 'provider', 404);

        ProviderProfile::firstOrCreate(
            ['user_id' => $provider->id],
            ['status' => 'inactive', 'document_status' => 'pending']
        );

        return response()->json(['data' => $provider->load('providerProfile')]);
    }

    public function toggleStatus(Request $request, User $provider): JsonResponse
    {
        $this->authorizeRole($request, 'admin');
        abort_if($provider->role !== 'provider', 404);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $provider->id],
            ['status' => 'inactive', 'document_status' => 'pending']
        );

        $profile->update(['status' => $profile->status === 'active' ? 'inactive' : 'active']);

        return response()->json(['message' => 'Status akun provider berhasil diperbarui.', 'data' => $provider->load('providerProfile')]);
    }

    public function updateDocumentStatus(Request $request, User $provider): JsonResponse
    {
        $this->authorizeRole($request, 'admin');
        abort_if($provider->role !== 'provider', 404);

        $validated = $request->validate([
            'document_status' => ['required', Rule::in(['pending', 'submitted', 'verified', 'rejected'])],
            'document_note' => ['nullable', 'string'],
        ]);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $provider->id],
            ['status' => 'inactive', 'document_status' => 'pending']
        );

        $profileData = [
            'document_status' => $validated['document_status'],
            'document_note' => $validated['document_note'] ?? null,
        ];

        if ($validated['document_status'] === 'verified') {
            $profileData['status'] = 'active';
        }

        $profile->update($profileData);

        return response()->json(['message' => 'Status dokumen provider berhasil diperbarui.', 'data' => $provider->load('providerProfile')]);
    }

    public function destroy(Request $request, User $provider): JsonResponse
    {
        $this->authorizeRole($request, 'admin');
        abort_if($provider->role !== 'provider', 404);

        $profile = $provider->providerProfile;

        if ($profile) {
            $this->deleteStoredFile($profile->ktp_image);
            $this->deleteStoredFile($profile->business_image);
            $this->deleteStoredFile($profile->image);
            $profile->delete();
        }

        $provider->delete();

        return response()->json(['message' => 'Provider berhasil dihapus.']);
    }
}
