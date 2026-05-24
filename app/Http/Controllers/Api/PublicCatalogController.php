<?php

namespace App\Http\Controllers\Api;

use App\Models\ProviderBranch;
use App\Models\ProviderStaff;
use App\Models\Service;
use App\Models\ServiceCategory;
use App\Models\User;
use App\Services\BookingFlowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class PublicCatalogController extends ApiController
{
    public function __construct(private readonly BookingFlowService $bookingFlow)
    {
    }

    public function categories(Request $request): JsonResponse
    {
        $categories = ServiceCategory::query()
            ->where('status', 'active')
            ->when($request->query('featured'), fn ($query) => $query->where('is_featured', $request->boolean('featured')))
            ->when($request->query('search'), fn ($query, $search) => $query->where('name', 'like', "%{$search}%"))
            ->orderByDesc('is_featured')
            ->orderBy('name')
            ->paginate($this->perPage($request));

        return response()->json($categories);
    }

    public function services(Request $request): JsonResponse
    {
        $services = Service::query()
            ->with(['provider.providerProfile', 'serviceCategory'])
            ->where('status', 'active')
            ->whereHas('provider', fn ($providerQuery) => $this->activeApprovedProviderQuery($providerQuery))
            ->when($request->query('provider_id'), fn ($query, $providerId) => $query->where('provider_id', $providerId))
            ->when($request->query('category'), function ($query, $category) {
                $query->where(function ($nested) use ($category) {
                    $nested->where('category', $category)
                        ->orWhereHas('serviceCategory', fn ($categoryQuery) => $categoryQuery->where('name', $category));
                });
            })
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%")
                        ->orWhere('category', 'like', "%{$search}%")
                        ->orWhereHas('serviceCategory', fn ($categoryQuery) => $categoryQuery->where('name', 'like', "%{$search}%"));
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        $services->setCollection($services->getCollection()->map(fn (Service $service) => $this->servicePayload($service)));

        return response()->json($services);
    }

    public function locations(Request $request): JsonResponse
    {
        $locations = ProviderBranch::query()
            ->where('status', 'active')
            ->whereHas('provider', fn ($providerQuery) => $this->activeApprovedProviderQuery($providerQuery))
            ->whereNotNull('city_id')
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('city_id', 'like', "%{$search}%")
                        ->orWhere('state_id', 'like', "%{$search}%")
                        ->orWhere('country_id', 'like', "%{$search}%");
                });
            })
            ->select('country_id', 'state_id', 'city_id')
            ->distinct()
            ->orderBy('country_id')
            ->orderBy('state_id')
            ->orderBy('city_id')
            ->get()
            ->map(fn (ProviderBranch $branch) => [
                'country' => $branch->country_id,
                'state' => $branch->state_id,
                'city' => $branch->city_id,
                'label' => collect([$branch->city_id, $branch->state_id, $branch->country_id])->filter()->implode(', '),
            ])
            ->values();

        return response()->json(['data' => $locations]);
    }

    public function branches(Request $request): JsonResponse
    {
        $request->validate([
            'booking_date' => ['nullable', 'date'],
        ]);

        $perPage = $this->perPage($request);
        $bookingDate = $request->filled('booking_date')
            ? Carbon::parse($request->query('booking_date'))->startOfDay()
            : null;

        if ($bookingDate && $bookingDate->lt(now()->startOfDay())) {
            return $this->emptyBranchPaginator($request, $perPage);
        }

        $latitude = $request->filled('lat') ? (float) $request->query('lat') : null;
        $longitude = $request->filled('lng') ? (float) $request->query('lng') : null;
        $radiusKm = $request->filled('radius_km')
            ? max(0, (float) $request->query('radius_km'))
            : ($request->filled('radius') ? max(0, (float) $request->query('radius')) : null);

        $query = ProviderBranch::query()
            ->with([
                'provider:id,name,email',
                'provider.providerProfile:user_id,status,document_status,image',
            ])
            ->withCount(['staffs' => fn ($query) => $query->where('status', 'active')])
            ->where('status', 'active')
            ->whereHas('provider', fn ($providerQuery) => $this->activeApprovedProviderQuery($providerQuery))
            ->when($request->query('country'), fn ($query, $country) => $query->where('country_id', $country))
            ->when($request->query('state'), fn ($query, $state) => $query->where('state_id', $state))
            ->when($request->query('city'), fn ($query, $city) => $query->where('city_id', $city))
            ->when($request->query('search'), fn ($query, $search) => $this->applyBranchSearch($query, $search))
            ->when($request->query('category'), function ($query, $category) {
                $query->whereHas('provider.services', function ($serviceQuery) use ($category) {
                    $serviceQuery->where('status', 'active')
                        ->where(function ($nested) use ($category) {
                            $nested->where('category', $category)
                                ->orWhereHas('serviceCategory', fn ($categoryQuery) => $categoryQuery->where('name', $category));
                        });
                });
            })
            ->orderBy('city_id')
            ->orderBy('branch_name');

        if ($latitude !== null && $longitude !== null) {
            $payloads = $query->get()
                ->map(fn (ProviderBranch $branch) => $this->branchPayload($branch, $latitude, $longitude))
                ->filter(fn (array $branch) => $radiusKm === null || (
                    isset($branch['distance_km'])
                    && $branch['distance_km'] <= $radiusKm
                ))
                ->sortBy(fn (array $branch) => $branch['distance_km'] ?? PHP_FLOAT_MAX)
                ->values();

            $page = LengthAwarePaginator::resolveCurrentPage();
            $items = $payloads->slice(($page - 1) * $perPage, $perPage)->values();

            return response()->json(new LengthAwarePaginator(
                $items,
                $payloads->count(),
                $perPage,
                $page,
                ['path' => $request->url(), 'query' => $request->query()]
            ));
        }

        $branches = $query->paginate($perPage);
        $branches->setCollection($branches->getCollection()->map(fn (ProviderBranch $branch) => $this->branchPayload($branch)));

        return response()->json($branches);
    }

    private function emptyBranchPaginator(Request $request, int $perPage): JsonResponse
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return response()->json(new LengthAwarePaginator(
            collect(),
            0,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        ));
    }

    public function branch(ProviderBranch $branch): JsonResponse
    {
        abort_unless($this->bookingFlow->branchIsBookable($branch), 404);

        $branch->load([
            'provider:id,name,email',
            'provider.providerProfile:user_id,status,document_status,image',
            'staffs.skills',
            'staffs.schedules',
        ]);

        $services = $this->servicesForBranch($branch)->map(fn (Service $service) => $this->servicePayload($service))->values();
        $staff = $branch->staffs
            ->where('status', 'active')
            ->map(fn (ProviderStaff $staff) => $this->staffPayload($staff))
            ->values();

        $payload = $this->branchPayload($branch);
        $payload['services'] = $services;
        $payload['staff'] = $staff;
        $payload['service_groups'] = $this->groupServicesByCategory($services);
        $payload['available_booking_modes'] = [
            'scheduled' => $services->contains(fn ($service) => (bool) ($service['is_scheduled_enabled'] ?? false)),
            'queue' => $services->contains(fn ($service) => (bool) ($service['is_queue_enabled'] ?? false)),
        ];

        return response()->json(['data' => $payload]);
    }

    public function branchServices(ProviderBranch $branch): JsonResponse
    {
        abort_unless($this->bookingFlow->branchIsBookable($branch), 404);

        $services = $this->servicesForBranch($branch)
            ->map(fn (Service $service) => $this->servicePayload($service))
            ->values();

        return response()->json([
            'data' => $services,
            'grouped' => $this->groupServicesByCategory($services),
        ]);
    }

    public function branchStaff(ProviderBranch $branch): JsonResponse
    {
        abort_unless($this->bookingFlow->branchIsBookable($branch), 404);

        $staff = ProviderStaff::query()
            ->with(['skills.serviceCategory', 'schedules'])
            ->where('branch_id', $branch->id)
            ->where('provider_id', $branch->provider_id)
            ->where('status', 'active')
            ->orderBy('first_name')
            ->get()
            ->map(fn (ProviderStaff $staff) => $this->staffPayload($staff))
            ->values();

        return response()->json(['data' => $staff]);
    }

    public function service(Service $service): JsonResponse
    {
        abort_unless($service->status === 'active', 404);
        abort_unless(
            $service->provider?->role === 'provider'
                && optional($service->provider?->providerProfile)->status === 'active'
                && optional($service->provider?->providerProfile)->document_status === 'verified',
            404
        );

        return response()->json([
            'data' => $this->servicePayload($service->load(['provider.providerProfile', 'serviceCategory'])),
        ]);
    }

    public function providers(Request $request): JsonResponse
    {
        $providers = User::query()
            ->with('providerProfile')
            ->where('role', 'provider')
            ->whereHas('providerProfile', fn ($query) => $query->where('status', 'active')->where('document_status', 'verified'))
            ->when($request->query('search'), function ($query, $search) {
                $query->where(function ($nested) use ($search) {
                    $nested->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($providers);
    }

    private function activeApprovedProviderQuery($query)
    {
        return $query->where('role', 'provider')
            ->whereHas('providerProfile', fn ($profileQuery) => $profileQuery
                ->where('status', 'active')
                ->where('document_status', 'verified'));
    }

    private function applyBranchSearch($query, string $search): void
    {
        $terms = collect(preg_split('/[,]+/', $search))
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->values();

        if ($terms->isEmpty()) {
            $terms = collect([trim($search)])->filter();
        }

        $terms->each(function (string $term) use ($query) {
            $query->where(function ($nested) use ($term) {
                $nested->where('branch_name', 'like', "%{$term}%")
                    ->orWhere('address', 'like', "%{$term}%")
                    ->orWhere('city_id', 'like', "%{$term}%")
                    ->orWhere('state_id', 'like', "%{$term}%")
                    ->orWhere('country_id', 'like', "%{$term}%")
                    ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$term}%"));
            });
        });
    }

    private function branchPayload(ProviderBranch $branch, ?float $latitude = null, ?float $longitude = null): array
    {
        $services = $this->servicesForBranch($branch);
        $minPrice = $services->min('price');
        $serviceCategories = $services
            ->map(fn (Service $service) => $service->serviceCategory?->name ?? $service->category)
            ->filter()
            ->unique()
            ->values();
        $serviceTitles = $services
            ->pluck('title')
            ->filter()
            ->unique()
            ->values();
        $galleryImages = collect([$branch->image])
            ->merge($services->pluck('gallery_image'))
            ->filter()
            ->map(fn (?string $path) => $this->storageUrl($path))
            ->filter()
            ->unique()
            ->values();
        $payload = array_merge($branch->toArray(), [
            'provider' => $branch->provider,
            'services_count' => $services->count(),
            'staffs_count' => $branch->staffs_count ?? $branch->staffs()->where('status', 'active')->count(),
            'min_price' => $minPrice !== null ? (float) $minPrice : null,
            'next_available_slot' => $this->nextAvailableSlot($branch, $services),
            'rating' => 4.8,
            'review_count' => 0,
            'service_categories' => $serviceCategories,
            'service_titles' => $serviceTitles,
            'has_queue_service' => $services->contains(fn (Service $service) => (bool) $service->is_queue_enabled),
            'has_scheduled_service' => $services->contains(fn (Service $service) => (bool) $service->is_scheduled_enabled),
            'supports_pay_at_salon' => $services->contains(fn (Service $service) => ! (bool) $service->requires_dp),
            'location_label' => collect([$branch->city_id, $branch->state_id, $branch->country_id])->filter()->implode(', '),
            'image_url' => $this->storageUrl($branch->image),
            'gallery_images' => $galleryImages,
        ]);

        if ($latitude !== null && $longitude !== null && $branch->latitude !== null && $branch->longitude !== null) {
            $payload['distance_km'] = round($this->distanceBetween(
                $latitude,
                $longitude,
                (float) $branch->latitude,
                (float) $branch->longitude
            ), 1);
        }

        return $payload;
    }

    private function servicePayload(Service $service): array
    {
        $service->loadMissing('serviceCategory');

        return array_merge($service->toArray(), [
            'category_name' => $service->serviceCategory?->name ?? $service->category,
            'category' => $service->category,
            'price' => (float) ($service->price ?? 0),
            'minimum_duration' => (int) ($service->minimum_duration ?? 0),
            'estimated_duration' => (int) ($service->estimated_duration ?: 30),
            'maximum_duration' => (int) ($service->maximum_duration ?: 60),
            'is_queue_enabled' => (bool) $service->is_queue_enabled,
            'is_scheduled_enabled' => (bool) $service->is_scheduled_enabled,
            'requires_dp' => (bool) $service->requires_dp,
            'dp_amount' => $service->dp_amount !== null ? (float) $service->dp_amount : null,
            'payment_policy' => $service->payment_policy,
            'image_url' => $this->storageUrl($service->gallery_image),
        ]);
    }

    private function staffPayload(ProviderStaff $staff): array
    {
        $staff->loadMissing('skills', 'schedules');

        return [
            'id' => $staff->id,
            'name' => $staff->full_name ?: $staff->email,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'image' => $staff->image,
            'image_url' => $this->storageUrl($staff->image),
            'rating' => $staff->rating ? (float) $staff->rating : null,
            'current_status' => $staff->current_status,
            'status' => $staff->status,
            'branch_id' => $staff->branch_id,
            'skills' => $staff->skills->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
                'category_name' => $service->serviceCategory?->name ?? $service->category,
            ])->values(),
            'schedules' => $staff->schedules->map(fn ($schedule) => [
                'id' => $schedule->id,
                'day_of_week' => $schedule->day_of_week,
                'start_time' => substr((string) $schedule->start_time, 0, 5),
                'end_time' => substr((string) $schedule->end_time, 0, 5),
                'is_available' => (bool) $schedule->is_available,
            ])->values(),
        ];
    }

    private function groupServicesByCategory(Collection $services): array
    {
        return $services
            ->groupBy(fn ($service) => $service['category_name'] ?? $service['category'] ?? 'Lainnya')
            ->map(fn (Collection $items, string $category) => [
                'category' => $category,
                'services' => $items->values(),
            ])
            ->values()
            ->all();
    }

    private function servicesForBranch(ProviderBranch $branch): Collection
    {
        return Service::query()
            ->with(['provider.providerProfile', 'serviceCategory'])
            ->where('provider_id', $branch->provider_id)
            ->where('status', 'active')
            ->latest()
            ->get()
            ->filter(function (Service $service) use ($branch) {
                $branchIds = $service->branch_ids;

                if (empty($branchIds)) {
                    return true;
                }

                return in_array((int) $branch->id, array_map('intval', (array) $branchIds), true);
            })
            ->values();
    }

    private function nextAvailableSlot(ProviderBranch $branch, Collection $services): ?string
    {
        if ($services->isEmpty()) {
            return null;
        }

        $firstService = $services->first();

        if (! $firstService?->is_scheduled_enabled) {
            return null;
        }

        $slots = $this->bookingFlow->availableSlots($branch, collect([$firstService]), now()->toDateString());

        return $slots[0]['time'] ?? null;
    }

    private function storageUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return asset(str_starts_with($path, 'storage/') ? $path : 'storage/' . ltrim($path, '/'));
    }

    private function distanceBetween(float $fromLatitude, float $fromLongitude, float $toLatitude, float $toLongitude): float
    {
        $earthRadiusKm = 6371;
        $latitudeDelta = deg2rad($toLatitude - $fromLatitude);
        $longitudeDelta = deg2rad($toLongitude - $fromLongitude);

        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($fromLatitude)) * cos(deg2rad($toLatitude)) * sin($longitudeDelta / 2) ** 2;

        return $earthRadiusKm * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
