<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 10);
        $allowedPerPage = [10, 25, 50, 100];

        if (!in_array($perPage, $allowedPerPage)) {
            $perPage = 10;
        }

        $search = $request->get('search');

        $providers = User::where('role', 'provider')
            ->with('providerProfile')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('providerProfile', function ($profileQuery) use ($search) {
                            $profileQuery->where('phone_number', 'like', '%' . $search . '%')
                                ->orWhere('category', 'like', '%' . $search . '%')
                                ->orWhere('status', 'like', '%' . $search . '%')
                                ->orWhere('document_status', 'like', '%' . $search . '%');
                        });
                });
            })
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('admin.providers.index', compact('providers', 'perPage', 'search'));
    }

    public function show(User $user)
    {
        abort_if($user->role !== 'provider', 404);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'inactive',
                'document_status' => 'pending',
            ]
        );

        $provider = $user->load('providerProfile');

        return view('admin.providers.show', compact('provider', 'profile'));
    }

    public function toggleStatus(User $user)
    {
        abort_if($user->role !== 'provider', 404);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'inactive',
                'document_status' => 'pending',
            ]
        );

        $profile->update([
            'status' => $profile->status === 'active' ? 'inactive' : 'active',
        ]);

        return back()->with('success', 'Status akun provider berhasil diperbarui.');
    }

    public function updateDocumentStatus(Request $request, User $user)
    {
        abort_if($user->role !== 'provider', 404);

        $validated = $request->validate([
            'document_status' => ['required', 'in:pending,submitted,verified,rejected'],
            'document_note' => ['nullable', 'string'],
        ]);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'inactive',
                'document_status' => 'pending',
            ]
        );

        $profile->update([
            'document_status' => $validated['document_status'],
            'document_note' => $validated['document_note'] ?? null,
        ]);

        return back()->with('success', 'Status dokumen provider berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->role !== 'provider', 404);

        $profile = $user->providerProfile;

        if ($profile) {
            if ($profile->ktp_image) {
                Storage::disk('public')->delete($profile->ktp_image);
            }

            if ($profile->business_image) {
                Storage::disk('public')->delete($profile->business_image);
            }

            $profile->delete();
        }

        $user->delete();

        return redirect()
            ->route('admin.providers.index')
            ->with('success', 'Provider berhasil dihapus.');
    }
}