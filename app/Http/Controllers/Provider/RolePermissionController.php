<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderBranch;
use App\Models\ProviderRole;
use App\Models\User;
use App\Support\ProviderMenuAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RolePermissionController extends Controller
{
    private function providerId(): int
    {
        return ProviderMenuAccess::providerOwnerId(Auth::user());
    }

    public function index()
    {
        $this->authorizeProviderOwner();

        $providerId = $this->providerId();

        $roles = ProviderRole::query()
            ->where('provider_id', $providerId)
            ->with([
                'branch:id,provider_id,branch_name,status',
                'menuPermissions:id,provider_role_id,menu_key',
                'users:id,name,email,provider_id,branch_id,provider_role_id',
            ])
            ->withCount('users')
            ->orderBy('role_name')
            ->get();

        $branches = ProviderBranch::query()
            ->where('provider_id', $providerId)
            ->orderBy('branch_name')
            ->get(['id', 'provider_id', 'branch_name', 'status']);

        $branchAccounts = User::query()
            ->where('provider_id', $providerId)
            ->where('role', 'provider')
            ->with([
                'providerBranch:id,provider_id,branch_name,status',
                'providerRole:id,role_name,status',
            ])
            ->orderBy('name')
            ->get([
                'id',
                'name',
                'email',
                'provider_id',
                'branch_id',
                'provider_role_id',
            ]);

        $menuSections = $this->branchAccountMenuSections();
        $menuLabels = ProviderMenuAccess::labels();

        return view('provider.pages.roles-permissions.index', compact(
            'roles',
            'branches',
            'branchAccounts',
            'menuSections',
            'menuLabels'
        ));
    }

    public function store(Request $request)
    {
        $this->authorizeProviderOwner();

        $validated = $this->validatedRoleData($request);
        $providerId = $this->providerId();

        DB::transaction(function () use ($validated, $providerId) {
            $role = ProviderRole::create([
                'provider_id' => $providerId,
                'branch_id' => $validated['branch_id'] ?? null,
                'role_name' => $validated['role_name'],
                'slug' => $this->uniqueSlug($validated['role_name'], $providerId),
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $this->syncPermissions($role, $validated['menu_keys'] ?? []);
            $this->createBranchAccount($role, $validated);
        });

        return provider_route_redirect('provider.roles-permissions.index')
            ->with('success', 'Akun cabang dan permission berhasil dibuat.');
    }

    public function update(Request $request, ProviderRole $role)
    {
        $this->authorizeProviderOwner();
        $this->authorizeProviderRole($role);

        $validated = $this->validatedRoleData($request, $role);
        $providerId = $this->providerId();

        DB::transaction(function () use ($validated, $providerId, $role) {
            $role->update([
                'branch_id' => $validated['branch_id'] ?? null,
                'role_name' => $validated['role_name'],
                'slug' => $this->uniqueSlug($validated['role_name'], $providerId, $role->id),
                'description' => $validated['description'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            $this->syncPermissions($role, $validated['menu_keys'] ?? []);
            $this->updateBranchAccount($role, $validated);
        });

        return provider_route_redirect('provider.roles-permissions.index')
            ->with('success', 'Akun cabang dan permission berhasil diperbarui.');
    }

    public function destroy(ProviderRole $role)
    {
        $this->authorizeProviderOwner();
        $this->authorizeProviderRole($role);

        DB::transaction(function () use ($role) {
            User::query()
                ->where('provider_id', $this->providerId())
                ->where('provider_role_id', $role->id)
                ->delete();

            $role->delete();
        });

        return provider_route_redirect('provider.roles-permissions.index')
            ->with('success', 'Akun cabang dan permission berhasil dihapus.');
    }

    private function validatedRoleData(Request $request, ?ProviderRole $role = null): array
    {
        $providerId = $this->providerId();
        $menuKeys = collect($this->branchAccountMenuSections())
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('key')
            ->all();

        $validated = $request->validate([
            'role_name' => ['required', 'string', 'max:120'],
            'account_name' => ['required', 'string', 'max:255'],
            'account_email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($role?->users()->value('id')),
            ],
            'account_password' => [
                $role ? 'nullable' : 'required',
                'string',
                'min:8',
            ],
            'branch_id' => [
                'required',
                Rule::exists('provider_branches', 'id')
                    ->where(fn ($query) => $query->where('provider_id', $providerId)),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'menu_keys' => ['nullable', 'array'],
            'menu_keys.*' => ['required', Rule::in($menuKeys)],
        ], [
            'role_name.required' => 'Nama role wajib diisi.',
            'account_name.required' => 'Nama akun cabang wajib diisi.',
            'account_email.required' => 'Email akun cabang wajib diisi.',
            'account_email.email' => 'Format email akun cabang tidak valid.',
            'account_email.unique' => 'Email akun cabang sudah digunakan.',
            'account_password.required' => 'Password akun cabang wajib diisi.',
            'account_password.min' => 'Password akun cabang minimal 8 karakter.',
            'branch_id.required' => 'Branch wajib dipilih.',
            'branch_id.exists' => 'Branch tidak valid atau bukan milik provider ini.',
            'menu_keys.*.in' => 'Menu permission tidak valid.',
        ]);

        $validated['menu_keys'] = collect($validated['menu_keys'] ?? [])
            ->map(fn ($key) => (string) $key)
            ->unique()
            ->values()
            ->all();

        return $validated;
    }

    private function syncPermissions(ProviderRole $role, array $menuKeys): void
    {
        $role->menuPermissions()->delete();

        $allowedMenuKeys = collect($this->branchAccountMenuSections())
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('key')
            ->all();

        $permissions = collect($menuKeys)
            ->intersect($allowedMenuKeys)
            ->unique()
            ->map(fn (string $menuKey) => ['menu_key' => $menuKey])
            ->values()
            ->all();

        if ($permissions !== []) {
            $role->menuPermissions()->createMany($permissions);
        }
    }

    private function createBranchAccount(ProviderRole $role, array $validated): void
    {
        User::create([
            'name' => $validated['account_name'],
            'username' => $this->uniqueUsername($validated['account_name']),
            'email' => $validated['account_email'],
            'password' => Hash::make($validated['account_password']),
            'role' => 'provider',
            'provider_id' => $this->providerId(),
            'branch_id' => $validated['branch_id'],
            'provider_role_id' => $role->id,
            'email_verified_at' => now(),
        ]);
    }

    private function updateBranchAccount(ProviderRole $role, array $validated): void
    {
        $account = $role->users()
            ->where('provider_id', $this->providerId())
            ->where('role', 'provider')
            ->first();

        $payload = [
            'name' => $validated['account_name'],
            'email' => $validated['account_email'],
            'branch_id' => $validated['branch_id'],
            'provider_id' => $this->providerId(),
            'role' => 'provider',
            'provider_role_id' => $role->id,
            'email_verified_at' => now(),
        ];

        if (! empty($validated['account_password'])) {
            $payload['password'] = Hash::make($validated['account_password']);
        }

        if ($account) {
            $account->update($payload);

            return;
        }

        User::create(array_merge($payload, [
            'username' => $this->uniqueUsername($validated['account_name']),
            'password' => Hash::make($validated['account_password'] ?: Str::random(16)),
        ]));
    }

    private function uniqueUsername(string $name): string
    {
        $base = Str::slug($name) ?: 'branch-account';
        $username = $base;
        $counter = 2;

        while (User::where('username', $username)->exists()) {
            $username = $base . '-' . $counter;
            $counter++;
        }

        return $username;
    }

    private function authorizeProviderOwner(): void
    {
        $user = Auth::user();

        abort_unless($user && ProviderMenuAccess::isProviderOwner($user), 403, 'Hanya akun provider pusat yang boleh mengatur akun cabang.');
    }

    private function uniqueSlug(string $roleName, int $providerId, ?int $ignoreId = null): string
    {
        $base = Str::slug($roleName) ?: 'role';
        $slug = $base;
        $counter = 2;

        while (
            ProviderRole::query()
                ->where('provider_id', $providerId)
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function authorizeProviderRole(ProviderRole $role): void
    {
        abort_unless((int) $role->provider_id === $this->providerId(), 403, 'Akses ditolak.');
    }

    private function branchAccountMenuSections(): array
    {
        return collect(ProviderMenuAccess::sections())
            ->map(function (array $section) {
                $section['items'] = collect($section['items'])
                    ->reject(fn (array $item) => ($item['key'] ?? null) === 'roles_permissions')
                    ->values()
                    ->all();

                return $section;
            })
            ->filter(fn (array $section) => ! empty($section['items']))
            ->values()
            ->all();
    }
}
