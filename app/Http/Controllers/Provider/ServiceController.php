<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderBranch;
use App\Models\Service;
use App\Support\ProviderAccountScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ServiceController extends Controller
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

    public function index()
    {
        $services = Service::with('provider.providerProfile')
            ->where('provider_id', $this->providerId())
            ->latest();
        ProviderAccountScope::applyServiceBranchScope($services, $this->branchId());

        $services = $services->get();

        return view('provider.pages.services.index', compact('services'));
    }

    public function create(Request $request)
    {
        $step = $request->get('step', 'service');

        if (in_array($step, ['branch', 'gallery']) && !session()->has('service_draft')) {
            return provider_route_redirect('provider.services.create')
                ->with('error', 'Isi Service Information dulu, lalu klik Continue.');
        }

        if ($step === 'gallery' && !session()->has('service_branch_draft')) {
            return provider_route_redirect('provider.services.create', ['step' => 'branch'])
                ->with('error', 'Pilih Branch dulu, lalu klik Continue.');
        }

        $data = $this->formData();

        return view('provider.pages.services.create', array_merge($data, [
            'mode' => 'create',
            'step' => $step,
            'service' => null,
            'draft' => session('service_draft', []),
            'branchDraft' => session('service_branch_draft', $this->branchId() !== null && $this->branchId() > 0 ? [
                'branch_ids' => [$this->branchId()],
            ] : []),
        ]));
    }

    public function continueInformation(Request $request)
    {
        $validated = $this->validateServiceInformation($request);

        session([
            'service_draft' => $validated,
        ]);

        return provider_route_redirect('provider.services.create', ['step' => 'branch'])
            ->with('success', 'Service Information berhasil disimpan sementara.');
    }

    public function continueBranch(Request $request)
    {
        $validated = $request->validate([
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
        ]);

        $validated['branch_ids'] = $this->validBranchIds($validated['branch_ids'] ?? []);

        session([
            'service_branch_draft' => $validated,
        ]);

        return provider_route_redirect('provider.services.create', ['step' => 'gallery'])
            ->with('success', 'Branch Information berhasil disimpan sementara.');
    }

    public function store(Request $request)
    {
        if (!session()->has('service_draft')) {
            return provider_route_redirect('provider.services.create')
                ->with('error', 'Service Information belum diisi.');
        }

        if (!session()->has('service_branch_draft')) {
            return provider_route_redirect('provider.services.create', ['step' => 'branch'])
                ->with('error', 'Branch Information belum diisi.');
        }

        $galleryData = $this->validateGallery($request);

        $draft = session('service_draft');
        $branchDraft = session('service_branch_draft');

        $imagePath = null;

        if ($request->hasFile('gallery_image')) {
            $imagePath = $request->file('gallery_image')->store('service-gallery', 'public');
        }

        $title = $draft['title'];
        $slug = $this->uniqueSlug($title);

        Service::create([
            'provider_id' => $this->providerId(),
            'title' => $title,
            'slug' => $slug,
            'category' => $draft['category'],
            'category_id' => $draft['category_id'] ?? null,
            'code' => $draft['code'] ?? null,
            'description' => $draft['description'] ?? null,
            'includes' => $draft['includes'] ?? null,
            'price_type' => $draft['price_type'] ?? null,
            'price' => $draft['price'] ?? 0,
            'minimum_duration' => $draft['minimum_duration'] ?? 0,
            'estimated_duration' => $draft['estimated_duration'] ?? 30,
            'maximum_duration' => $draft['maximum_duration'] ?? 60,
            'is_queue_enabled' => $draft['is_queue_enabled'] ?? true,
            'is_scheduled_enabled' => $draft['is_scheduled_enabled'] ?? true,
            'requires_dp' => $draft['requires_dp'] ?? false,
            'dp_amount' => $draft['dp_amount'] ?? null,
            'payment_policy' => $draft['payment_policy'] ?? null,
            'slots' => $draft['slots'] ?? [],
            'additional_services' => $draft['additional_services'] ?? [],
            'holidays' => $draft['holidays'] ?? [],
            'branch_ids' => $branchDraft['branch_ids'] ?? [],
            'gallery_image' => $imagePath,
            'video_url' => $galleryData['video_url'] ?? null,
            'status' => 'active',

            /*
             * Kolom verify_status di services tidak dipakai lagi untuk tampilan My Service.
             * Status verifikasi sekarang diambil dari provider_profiles.document_status.
             * Ini tetap diisi hanya supaya aman kalau kolom masih required/default lama.
             */
            'verify_status' => 'pending',
        ]);

        session()->forget([
            'service_draft',
            'service_branch_draft',
        ]);

        return provider_route_redirect('provider.services.index')
            ->with('success', 'Service berhasil ditambahkan.');
    }

    public function edit(Request $request, Service $service)
    {
        $this->authorizeService($service);

        $step = $request->get('step', 'service');
        $data = $this->formData();

        return view('provider.pages.services.create', array_merge($data, [
            'mode' => 'edit',
            'step' => $step,
            'service' => $service,
            'draft' => [],
            'branchDraft' => [
                'branch_ids' => $service->branch_ids ?? [],
            ],
        ]));
    }

    public function update(Request $request, Service $service)
    {
        $this->authorizeService($service);

        $validated = $this->validateServiceInformation($request);

        $newSlug = $service->title === $validated['title']
            ? $service->slug
            : $this->uniqueSlug($validated['title'], $service->id);

        $service->update([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'category' => $validated['category'],
            'category_id' => $validated['category_id'] ?? null,
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'includes' => $validated['includes'] ?? null,
            'price_type' => $validated['price_type'] ?? null,
            'price' => $validated['price'],
            'minimum_duration' => $validated['minimum_duration'] ?? 0,
            'estimated_duration' => $validated['estimated_duration'] ?? 30,
            'maximum_duration' => $validated['maximum_duration'] ?? 60,
            'is_queue_enabled' => $validated['is_queue_enabled'] ?? true,
            'is_scheduled_enabled' => $validated['is_scheduled_enabled'] ?? true,
            'requires_dp' => $validated['requires_dp'] ?? false,
            'dp_amount' => $validated['dp_amount'] ?? null,
            'payment_policy' => $validated['payment_policy'] ?? null,
            'slots' => $validated['slots'] ?? [],
            'additional_services' => $validated['additional_services'] ?? [],
            'holidays' => $validated['holidays'] ?? [],
        ]);

        return provider_route_redirect('provider.services.edit', [
                'service' => $service->id,
                'step' => 'branch',
            ])
            ->with('success', 'Service Information berhasil diperbarui.');
    }

    public function updateBranch(Request $request, Service $service)
    {
        $this->authorizeService($service);

        $validated = $request->validate([
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
        ]);

        $branchIds = $this->validBranchIds($validated['branch_ids'] ?? []);

        if ($this->branchId() !== null) {
            $existingBranchIds = collect($service->branch_ids ?? [])
                ->map(fn ($id) => (int) $id)
                ->filter()
                ->values();

            $branchIds = $existingBranchIds->isEmpty()
                ? []
                : $existingBranchIds->merge($branchIds)->unique()->values()->all();
        }

        $service->update([
            'branch_ids' => $branchIds,
        ]);

        return provider_route_redirect('provider.services.edit', [
                'service' => $service->id,
                'step' => 'gallery',
            ])
            ->with('success', 'Branch Information berhasil diperbarui.');
    }

    public function updateGallery(Request $request, Service $service)
    {
        $this->authorizeService($service);

        $validated = $this->validateGallery($request);

        $imagePath = $service->gallery_image;

        if ($request->hasFile('gallery_image')) {
            if ($service->gallery_image) {
                Storage::disk('public')->delete($service->gallery_image);
            }

            $imagePath = $request->file('gallery_image')->store('service-gallery', 'public');
        }

        $service->update([
            'gallery_image' => $imagePath,
            'video_url' => $validated['video_url'] ?? null,
        ]);

        return provider_route_redirect('provider.services.index')
            ->with('success', 'Service berhasil diperbarui.');
    }

    public function toggleStatus(Service $service)
    {
        $this->authorizeService($service);

        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);

        return provider_route_redirect('provider.services.index')
            ->with('success', 'Status service berhasil diperbarui.');
    }

    public function destroy(Service $service)
    {
        $this->authorizeService($service);

        if ($service->gallery_image) {
            Storage::disk('public')->delete($service->gallery_image);
        }

        $service->delete();

        return provider_route_redirect('provider.services.index')
            ->with('success', 'Service berhasil dihapus.');
    }

    private function validateServiceInformation(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'category_id' => ['nullable', 'integer', 'exists:service_categories,id'],
            'description' => ['nullable', 'string'],
            'includes' => ['nullable', 'string'],
            'price_type' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],
            'minimum_duration' => ['nullable', 'integer', 'min:0'],
            'estimated_duration' => ['nullable', 'integer', 'min:1'],
            'maximum_duration' => ['nullable', 'integer', 'min:1'],
            'is_queue_enabled' => ['nullable', 'boolean'],
            'is_scheduled_enabled' => ['nullable', 'boolean'],
            'requires_dp' => ['nullable', 'boolean'],
            'dp_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_policy' => ['nullable', 'string', 'max:1000'],

            'slots' => ['nullable', 'array'],
            'slots.*' => ['nullable', 'array'],

            'additional_services' => ['nullable', 'array'],
            'additional_services.*.name' => ['nullable', 'string', 'max:255'],
            'additional_services.*.price' => ['nullable', 'numeric', 'min:0'],
            'additional_services.*.description' => ['nullable', 'string', 'max:255'],

            'holidays' => ['nullable', 'array'],
            'holidays.*.date' => ['nullable', 'date'],
            'holidays.*.full_day' => ['nullable'],
        ]);

        $validated['slots'] = $this->cleanSlots($validated['slots'] ?? []);
        $validated['additional_services'] = $this->cleanAdditionalServices($validated['additional_services'] ?? []);
        $validated['holidays'] = $this->cleanHolidays($validated['holidays'] ?? []);

        return $validated;
    }

    private function validateGallery(Request $request): array
    {
        return $request->validate([
            'gallery_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'video_url' => ['nullable', 'url', 'max:255'],
        ]);
    }

    private function cleanSlots(array $slots): array
    {
        $cleaned = [];

        foreach ($slots as $day => $rows) {
            if (!is_array($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $start = $row['start'] ?? null;
                $end = $row['end'] ?? null;

                if ($start && $end) {
                    $cleaned[$day][] = [
                        'start' => $start,
                        'end' => $end,
                    ];
                }
            }
        }

        return $cleaned;
    }

    private function cleanAdditionalServices(array $rows): array
    {
        return collect($rows)
            ->filter(function ($row) {
                $name = trim($row['name'] ?? '');
                $price = trim((string) ($row['price'] ?? ''));
                $description = trim($row['description'] ?? '');

                return $name !== '' || $price !== '' || $description !== '';
            })
            ->map(function ($row) {
                return [
                    'name' => $row['name'] ?? null,
                    'price' => $row['price'] ?? null,
                    'description' => $row['description'] ?? null,
                ];
            })
            ->values()
            ->toArray();
    }

    private function cleanHolidays(array $rows): array
    {
        return collect($rows)
            ->filter(function ($row) {
                return !empty($row['date']);
            })
            ->map(function ($row) {
                return [
                    'date' => $row['date'],
                    'full_day' => !empty($row['full_day']) ? 1 : 0,
                ];
            })
            ->values()
            ->toArray();
    }

    private function validBranchIds(array $branchIds): array
    {
        if ($this->branchId() !== null) {
            abort_if($this->branchId() < 1, 403, 'Akun cabang belum terhubung ke branch.');

            return [$this->branchId()];
        }

        return ProviderBranch::where('provider_id', $this->providerId())
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private function formData(): array
    {
        $categories = collect();
        $branches = collect();

        if (Schema::hasTable('service_categories')) {
            $categories = DB::table('service_categories')
                ->select('id', 'name')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        if (Schema::hasTable('provider_branches')) {
            $branches = ProviderBranch::where('provider_id', $this->providerId())
                ->with('staffs')
                ->latest();
            ProviderAccountScope::applyBranchModelScope($branches, $this->branchId());

            $branches = $branches->get();
        }

        return compact('categories', 'branches');
    }

    private function authorizeService(Service $service): void
    {
        abort_if((int) $service->provider_id !== $this->providerId(), 403);
        abort_unless(ProviderAccountScope::serviceBelongsToBranch($service, $this->branchId()), 403);
    }

    private function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $baseSlug = Str::slug($title);

        if (!$baseSlug) {
            $baseSlug = 'service';
        }

        $slug = $baseSlug;
        $counter = 1;

        while (
            Service::where('provider_id', $this->providerId())
                ->where('slug', $slug)
                ->when($ignoreId, function ($query) use ($ignoreId) {
                    $query->where('id', '!=', $ignoreId);
                })
                ->exists()
        ) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }
}
