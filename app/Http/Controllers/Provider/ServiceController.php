<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\ProviderBranch;
use App\Models\Service;
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

        return (int) (data_get($user, 'provider_id') ?: $user->getAuthIdentifier());
    }

    public function index()
    {
        $services = Service::with('provider.providerProfile')
            ->where('provider_id', $this->providerId())
            ->latest()
            ->get();

        return view('provider.page.services.index', compact('services'));
    }

    public function create(Request $request)
    {
        $step = $request->get('step', 'service');

        if (in_array($step, ['branch', 'gallery']) && !session()->has('service_draft')) {
            return redirect()
                ->route('provider.services.create')
                ->with('error', 'Isi Service Information dulu, lalu klik Continue.');
        }

        if ($step === 'gallery' && !session()->has('service_branch_draft')) {
            return redirect()
                ->route('provider.services.create', ['step' => 'branch'])
                ->with('error', 'Pilih Branch dulu, lalu klik Continue.');
        }

        $data = $this->formData();

        return view('provider.page.services.create', array_merge($data, [
            'mode' => 'create',
            'step' => $step,
            'service' => null,
            'draft' => session('service_draft', []),
            'branchDraft' => session('service_branch_draft', []),
        ]));
    }

    public function continueInformation(Request $request)
    {
        $validated = $this->validateServiceInformation($request);

        session([
            'service_draft' => $validated,
        ]);

        return redirect()
            ->route('provider.services.create', ['step' => 'branch'])
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

        return redirect()
            ->route('provider.services.create', ['step' => 'gallery'])
            ->with('success', 'Branch Information berhasil disimpan sementara.');
    }

    public function store(Request $request)
    {
        if (!session()->has('service_draft')) {
            return redirect()
                ->route('provider.services.create')
                ->with('error', 'Service Information belum diisi.');
        }

        if (!session()->has('service_branch_draft')) {
            return redirect()
                ->route('provider.services.create', ['step' => 'branch'])
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
            'sub_category' => $draft['sub_category'] ?? null,
            'code' => $draft['code'] ?? null,
            'description' => $draft['description'] ?? null,
            'includes' => $draft['includes'] ?? null,
            'price_type' => $draft['price_type'] ?? null,
            'price' => $draft['price'] ?? 0,
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

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service berhasil ditambahkan.');
    }

    public function edit(Request $request, Service $service)
    {
        abort_if($service->provider_id !== $this->providerId(), 403);

        $step = $request->get('step', 'service');
        $data = $this->formData();

        return view('provider.page.services.create', array_merge($data, [
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
        abort_if($service->provider_id !== $this->providerId(), 403);

        $validated = $this->validateServiceInformation($request);

        $newSlug = $service->title === $validated['title']
            ? $service->slug
            : $this->uniqueSlug($validated['title'], $service->id);

        $service->update([
            'title' => $validated['title'],
            'slug' => $newSlug,
            'category' => $validated['category'],
            'sub_category' => $validated['sub_category'] ?? null,
            'code' => $validated['code'] ?? null,
            'description' => $validated['description'] ?? null,
            'includes' => $validated['includes'] ?? null,
            'price_type' => $validated['price_type'] ?? null,
            'price' => $validated['price'],
            'slots' => $validated['slots'] ?? [],
            'additional_services' => $validated['additional_services'] ?? [],
            'holidays' => $validated['holidays'] ?? [],
        ]);

        return redirect()
            ->route('provider.services.edit', [
                'service' => $service->id,
                'step' => 'branch',
            ])
            ->with('success', 'Service Information berhasil diperbarui.');
    }

    public function updateBranch(Request $request, Service $service)
    {
        abort_if($service->provider_id !== $this->providerId(), 403);

        $validated = $request->validate([
            'branch_ids' => ['nullable', 'array'],
            'branch_ids.*' => ['integer'],
        ]);

        $branchIds = $this->validBranchIds($validated['branch_ids'] ?? []);

        $service->update([
            'branch_ids' => $branchIds,
        ]);

        return redirect()
            ->route('provider.services.edit', [
                'service' => $service->id,
                'step' => 'gallery',
            ])
            ->with('success', 'Branch Information berhasil diperbarui.');
    }

    public function updateGallery(Request $request, Service $service)
    {
        abort_if($service->provider_id !== $this->providerId(), 403);

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

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service berhasil diperbarui.');
    }

    public function toggleStatus(Service $service)
    {
        abort_if($service->provider_id !== $this->providerId(), 403);

        $service->update([
            'status' => $service->status === 'active' ? 'inactive' : 'active',
        ]);

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Status service berhasil diperbarui.');
    }

    public function destroy(Service $service)
    {
        abort_if($service->provider_id !== $this->providerId(), 403);

        if ($service->gallery_image) {
            Storage::disk('public')->delete($service->gallery_image);
        }

        $service->delete();

        return redirect()
            ->route('provider.services.index')
            ->with('success', 'Service berhasil dihapus.');
    }

    private function validateServiceInformation(Request $request): array
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'includes' => ['nullable', 'string'],
            'price_type' => ['nullable', 'string', 'max:255'],
            'price' => ['required', 'numeric', 'min:0'],

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
        return ProviderBranch::where('provider_id', $this->providerId())
            ->whereIn('id', $branchIds)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();
    }

    private function formData(): array
    {
        $categories = collect();
        $subCategories = collect();
        $branches = collect();

        if (Schema::hasTable('service_categories')) {
            $categories = DB::table('service_categories')
                ->select('id', 'name')
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        if (Schema::hasTable('service_sub_categories')) {
            $subCategories = DB::table('service_sub_categories')
                ->select(
                    'id',
                    'name',
                    DB::raw('service_category_id as category_id')
                )
                ->where('status', 'active')
                ->orderBy('name')
                ->get();
        }

        if (Schema::hasTable('provider_branches')) {
            $branches = ProviderBranch::where('provider_id', $this->providerId())
                ->with('staffs')
                ->latest()
                ->get();
        }

        return compact('categories', 'subCategories', 'branches');
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