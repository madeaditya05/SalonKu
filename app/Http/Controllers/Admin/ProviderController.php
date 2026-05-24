<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderProfile;
use App\Models\User;
use App\Services\AppNotificationService;
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
            ->whereNull('provider_id')
            ->whereNull('provider_role_id')
            ->with([
                'providerProfile',
                'branchAccounts' => function ($query) {
                    $query
                        ->select('id', 'name', 'username', 'email', 'provider_id', 'branch_id', 'provider_role_id', 'created_at')
                        ->with([
                            'providerBranch:id,provider_id,branch_name,status',
                            'providerRole:id,provider_id,branch_id,role_name,status',
                            'providerRole.menuPermissions:id,provider_role_id,menu_key',
                        ])
                        ->orderBy('name');
                },
            ])
            ->withCount('branchAccounts')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhereHas('providerProfile', function ($profileQuery) use ($search) {
                            $profileQuery->where('phone_number', 'like', '%' . $search . '%')
                                ->orWhere('category', 'like', '%' . $search . '%')
                                ->orWhere('status', 'like', '%' . $search . '%')
                                ->orWhere('document_status', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('branchAccounts', function ($branchAccountQuery) use ($search) {
                            $branchAccountQuery
                                ->where('name', 'like', '%' . $search . '%')
                                ->orWhere('email', 'like', '%' . $search . '%')
                                ->orWhereHas('providerBranch', function ($branchQuery) use ($search) {
                                    $branchQuery->where('branch_name', 'like', '%' . $search . '%');
                                })
                                ->orWhereHas('providerRole', function ($roleQuery) use ($search) {
                                    $roleQuery->where('role_name', 'like', '%' . $search . '%');
                                });
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

        if ($user->provider_id) {
            return redirect()->route('admin.providers.show', $user->provider_id);
        }

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
        abort_if($user->provider_id, 404);

        $profile = ProviderProfile::firstOrCreate(
            ['user_id' => $user->id],
            [
                'status' => 'inactive',
                'document_status' => 'pending',
            ]
        );

        $newStatus = $profile->status === 'active' ? 'inactive' : 'active';

        $profile->update([
            'status' => $newStatus,
        ]);

        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->providerRecipients((int) $user->id),
            'provider.status.' . $newStatus,
            $newStatus === 'active' ? 'Akun provider aktif' : 'Akun provider dinonaktifkan',
            $newStatus === 'active'
                ? 'Admin mengaktifkan akun provider kamu.'
                : 'Admin menonaktifkan akun provider kamu.',
            route('provider.dashboard'),
            [
                'provider_id' => (int) $user->id,
                'status' => $newStatus,
            ],
            (int) request()->user()?->id
        );

        return back()->with('success', 'Status akun provider berhasil diperbarui.');
    }

    public function updateDocumentStatus(Request $request, User $user)
    {
        abort_if($user->role !== 'provider', 404);
        abort_if($user->provider_id, 404);

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

        $statusLabels = [
            'pending' => 'Pending',
            'submitted' => 'Submitted',
            'verified' => 'Verified',
            'rejected' => 'Rejected',
        ];
        $statusLabel = $statusLabels[$validated['document_status']] ?? ucfirst($validated['document_status']);
        $documentNote = trim((string) ($validated['document_note'] ?? ''));
        $body = $documentNote !== '' ? $documentNote : "Status dokumen provider diperbarui menjadi {$statusLabel}.";

        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->providerRecipients((int) $user->id, 'profile'),
            'provider.document.' . $validated['document_status'],
            'Status dokumen diperbarui',
            $body,
            route('provider.profile'),
            [
                'provider_id' => (int) $user->id,
                'document_status' => $validated['document_status'],
            ],
            (int) $request->user()?->id
        );

        return back()->with('success', 'Status dokumen provider berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        abort_if($user->role !== 'provider', 404);
        abort_if($user->provider_id, 404);

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
