<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderBranch;
use App\Models\ProviderStaff;
use App\Support\ProviderAccountScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BranchController extends Controller
{
    private function providerId(): int
    {
        $user = Auth::user();

        if (!$user) {
            abort(401);
        }

        return ProviderAccountScope::providerId($user);
    }

    private function branchId(): ?int
    {
        return ProviderAccountScope::branchId(Auth::user());
    }

    private function isBranchAccount(): bool
    {
        return ProviderAccountScope::isBranchAccount(Auth::user());
    }

    public function index()
    {
        $branches = ProviderBranch::withCount('staffs')
            ->where('provider_id', $this->providerId())
            ->latest();
        ProviderAccountScope::applyBranchModelScope($branches, $this->branchId());

        $branches = $branches->get();
        $isBranchAccount = $this->isBranchAccount();

        return view('provider.pages.branches.index', compact('branches', 'isBranchAccount'));
    }

    public function create(Request $request)
    {
        abort_if($this->isBranchAccount(), 403, 'Branch accounts cannot create a new branch.');

        $step = $request->get('step', 'branch');

        if ($step === 'staff' && !session()->has('branch_draft')) {
            return provider_route_redirect('provider.branch.create')
                ->with('error', 'Complete Branch Information first, then click Continue.');
        }

        $data = $this->dropdownData();

        return view('provider.pages.branches.form', array_merge($data, [
            'mode' => 'create',
            'step' => $step,
            'branch' => null,
            'draft' => session('branch_draft', []),
            'selectedStaffIds' => old('staff_ids', []),
        ]));
    }

    public function continue(Request $request)
    {
        abort_if($this->isBranchAccount(), 403, 'Branch accounts cannot create a new branch.');

        $validated = $this->validateBranch($request);

        $validated['holidays'] = array_values(array_filter($validated['holidays'] ?? []));

        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('branch-images', 'public');
        } else {
            $oldDraft = session('branch_draft', []);
            $validated['image'] = $oldDraft['image'] ?? null;
        }

        session([
            'branch_draft' => $validated,
        ]);

        return provider_route_redirect('provider.branch.create', ['step' => 'staff'])
            ->with('success', 'Branch information has been saved temporarily.');
    }

    public function store(Request $request)
    {
        abort_if($this->isBranchAccount(), 403, 'Branch accounts cannot create a new branch.');

        if (!session()->has('branch_draft')) {
            return provider_route_redirect('provider.branch.create')
                ->with('error', 'Branch information has not been completed.');
        }

        $request->validate([
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer'],
        ]);

        $draft = session('branch_draft');
        $staffIds = $this->validStaffIds($request->input('staff_ids', []));

        DB::transaction(function () use ($draft, $staffIds) {
            $branch = ProviderBranch::create(array_merge($draft, [
                'provider_id' => $this->providerId(),
            ]));

            if (!empty($staffIds)) {
                ProviderStaff::where('provider_id', $this->providerId())
                    ->whereIn('id', $staffIds)
                    ->update([
                        'branch_id' => $branch->id,
                    ]);
            }
        });

        session()->forget('branch_draft');

        return provider_route_redirect('provider.branch.index')
            ->with('success', 'Branch has been added.');
    }

    public function edit(Request $request, ProviderBranch $branch)
    {
        $this->authorizeBranch($branch);

        $step = $request->get('step', 'branch');
        $data = $this->dropdownData();

        return view('provider.pages.branches.form', array_merge($data, [
            'mode' => 'edit',
            'step' => $step,
            'branch' => $branch->load('staffs'),
            'draft' => [],
            'selectedStaffIds' => old(
                'staff_ids',
                $branch->staffs->pluck('id')->toArray()
            ),
        ]));
    }

    public function update(Request $request, ProviderBranch $branch)
    {
        $this->authorizeBranch($branch);

        $validated = $this->validateBranch($request, $branch->id);

        $validated['holidays'] = array_values(array_filter($validated['holidays'] ?? []));

        if ($request->hasFile('image')) {
            if ($branch->image) {
                Storage::disk('public')->delete($branch->image);
            }

            $validated['image'] = $request->file('image')->store('branch-images', 'public');
        } else {
            $validated['image'] = $branch->image;
        }

        $branch->update($validated);

        return provider_route_redirect('provider.branch.edit', [
                'branch' => $branch->id,
                'step' => 'staff',
            ])
            ->with('success', 'Branch information has been updated.');
    }

    public function updateStaff(Request $request, ProviderBranch $branch)
    {
        $this->authorizeBranch($branch);

        $request->validate([
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer'],
        ]);

        $staffIds = $this->validStaffIds($request->input('staff_ids', []));

        DB::transaction(function () use ($branch, $staffIds) {
            ProviderStaff::where('provider_id', $this->providerId())
                ->where('branch_id', $branch->id)
                ->update([
                    'branch_id' => null,
                ]);

            if (!empty($staffIds)) {
                ProviderStaff::where('provider_id', $this->providerId())
                    ->whereIn('id', $staffIds)
                    ->update([
                        'branch_id' => $branch->id,
                    ]);
            }
        });

        return provider_route_redirect('provider.branch.index')
            ->with('success', 'Branch staff has been updated.');
    }

    public function destroy(ProviderBranch $branch)
    {
        $this->authorizeBranch($branch);
        abort_if($this->isBranchAccount(), 403, 'Branch accounts cannot delete branches.');

        DB::transaction(function () use ($branch) {
            ProviderStaff::where('provider_id', $this->providerId())
                ->where('branch_id', $branch->id)
                ->update([
                    'branch_id' => null,
                ]);

            if ($branch->image) {
                Storage::disk('public')->delete($branch->image);
            }

            $branch->delete();
        });

        return provider_route_redirect('provider.branch.index')
            ->with('success', 'Branch has been deleted.');
    }

    private function validateBranch(Request $request, ?int $branchId = null): array
    {
        return $request->validate([
            'branch_name' => ['required', 'string', 'max:255'],

            'email' => ['required', 'email', 'max:255'],

            'phone_code' => ['required', 'string', 'max:20'],
            'phone_number' => ['required', 'string', 'max:30'],

            'address' => ['required', 'string'],

            /*
             * Country, State, City sekarang dari API,
             * jadi value-nya adalah string:
             * Indonesia, East Kalimantan, Kota Bontang, dll.
             */
            'country_id' => ['required', 'string', 'max:255'],
            'state_id' => ['required', 'string', 'max:255'],
            'city_id' => ['required', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],

            'zip_code' => ['required', 'string', 'max:20'],

            'working_start_hour' => ['required', 'date_format:H:i'],
            'working_end_hour' => ['required', 'date_format:H:i'],

            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['required', 'string', 'max:20'],

            'holidays' => ['nullable', 'array'],
            'holidays.*' => ['nullable', 'date'],

            'image' => [
                $branchId ? 'nullable' : 'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:2048',
            ],
        ]);
    }

    private function dropdownData(): array
    {
        /*
         * Country, State, and City are no longer loaded from the database.
         * The data is loaded from the API through public/provider/js/branch.js.
         */
        $countries = collect();
        $states = collect();
        $cities = collect();

        $staffs = ProviderStaff::where('provider_id', $this->providerId())
            ->latest();
        ProviderAccountScope::applyBranchScope($staffs, $this->branchId());

        $staffs = $staffs->get();

        return compact('countries', 'states', 'cities', 'staffs');
    }

    private function validStaffIds(array $staffIds): array
    {
        return ProviderStaff::where('provider_id', $this->providerId())
            ->when($this->branchId() !== null, fn ($query) => $query->where('branch_id', $this->branchId()))
            ->whereIn('id', $staffIds)
            ->pluck('id')
            ->toArray();
    }

    private function authorizeBranch(ProviderBranch $branch): void
    {
        abort_if($branch->provider_id !== $this->providerId(), 403);
        abort_if($this->branchId() !== null && (int) $branch->id !== $this->branchId(), 403);
    }
}
