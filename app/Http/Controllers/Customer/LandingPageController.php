<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LandingPageController extends Controller
{
    public function index()
    {
        $categories = $this->getCategories();
        $recommendedServices = $this->getRecommendedServices();
        $featuredServices = $recommendedServices->take(6);
        $topProviders = $this->getTopProviders();

        $stats = [
            'services' => $this->countActiveServices(),
            'providers' => $this->countProviders(),
            'categories' => $categories->count(),
            'bookings' => Schema::hasTable('bookings') ? DB::table('bookings')->count() : 0,
        ];

        return view('customer.landing.index', compact(
            'categories',
            'recommendedServices',
            'featuredServices',
            'topProviders',
            'stats'
        ));
    }

    private function getCategories()
    {
        $fallback = collect([
            (object) ['name' => 'Cleaning', 'slug' => 'cleaning', 'icon' => '🧹', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Service AC', 'slug' => 'service-ac', 'icon' => '❄️', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Plumbing', 'slug' => 'plumbing', 'icon' => '🔧', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Electrical', 'slug' => 'electrical', 'icon' => '💡', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Beauty', 'slug' => 'beauty', 'icon' => '✨', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Home Repair', 'slug' => 'home-repair', 'icon' => '🏠', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Salon', 'slug' => 'salon', 'icon' => '💇', 'image_url' => null, 'total_services' => 0],
            (object) ['name' => 'Laundry', 'slug' => 'laundry', 'icon' => '👕', 'image_url' => null, 'total_services' => 0],
        ]);

        if (!Schema::hasTable('service_categories')) {
            return $fallback;
        }

        $query = DB::table('service_categories');

        if (Schema::hasColumn('service_categories', 'status')) {
            $query->where('status', 'active');
        }

        $rows = $query
            ->when(Schema::hasColumn('service_categories', 'is_featured'), function ($query) {
                $query->orderByDesc('is_featured');
            })
            ->when(Schema::hasColumn('service_categories', 'created_at'), function ($query) {
                $query->orderByDesc('created_at');
            })
            ->limit(12)
            ->get();

        if ($rows->isEmpty()) {
            return $fallback;
        }

        return $rows->map(function ($category, $index) {
            $icons = ['🧹', '❄️', '🔧', '💡', '✨', '🏠', '💇', '👕', '🚗', '🎨', '🌿', '📦'];

            return (object) [
                'name' => $category->name ?? 'Category',
                'slug' => $category->slug ?? Str::slug($category->name ?? 'category'),
                'icon' => !empty($category->icon) ? $category->icon : $icons[$index % count($icons)],
                'image_url' => !empty($category->image) ? $this->imageUrl($category->image) : null,
                'total_services' => $this->countServicesByCategory($category->name ?? null),
            ];
        });
    }

    private function getRecommendedServices()
    {
        $fallback = collect([
            (object) [
                'id' => null,
                'title' => 'Deep Home Cleaning',
                'category' => 'Cleaning',
                'sub_category' => 'Home Service',
                'provider' => 'JasaKu Partner',
                'price' => 150000,
                'rating' => '4.9',
                'image_url' => null,
                'description' => 'Layanan bersih-bersih rumah dengan tenaga profesional.',
            ],
            (object) [
                'id' => null,
                'title' => 'Service AC Rumah',
                'category' => 'Service AC',
                'sub_category' => 'Maintenance',
                'provider' => 'JasaKu Partner',
                'price' => 125000,
                'rating' => '4.8',
                'image_url' => null,
                'description' => 'Cuci AC, pengecekan, dan perbaikan AC rumah.',
            ],
            (object) [
                'id' => null,
                'title' => 'Perbaikan Listrik',
                'category' => 'Electrical',
                'sub_category' => 'Repair',
                'provider' => 'JasaKu Partner',
                'price' => 100000,
                'rating' => '4.7',
                'image_url' => null,
                'description' => 'Perbaikan saklar, lampu, instalasi, dan kebutuhan listrik.',
            ],
            (object) [
                'id' => null,
                'title' => 'Perbaikan Pipa Bocor',
                'category' => 'Plumbing',
                'sub_category' => 'Repair',
                'provider' => 'JasaKu Partner',
                'price' => 95000,
                'rating' => '4.8',
                'image_url' => null,
                'description' => 'Perbaikan kran, pipa bocor, wastafel, dan saluran air.',
            ],
        ]);

        if (!Schema::hasTable('services')) {
            return $fallback;
        }

        $query = DB::table('services')
            ->leftJoin('users', 'services.provider_id', '=', 'users.id')
            ->select('services.*', 'users.name as provider_name');

        if (Schema::hasColumn('services', 'status')) {
            $query->where('services.status', 'active');
        }

        if (Schema::hasColumn('services', 'verify_status')) {
            $query->where('services.verify_status', 'verified');
        }

        $rows = $query
            ->when(Schema::hasColumn('services', 'created_at'), function ($query) {
                $query->orderByDesc('services.created_at');
            })
            ->limit(20)
            ->get();

        if ($rows->isEmpty()) {
            return $fallback;
        }

        return $rows->map(function ($service) {
            return (object) [
                'id' => $service->id ?? null,
                'title' => $service->title ?? 'Service',
                'category' => $service->category ?? 'Service',
                'sub_category' => $service->sub_category ?? null,
                'provider' => $service->provider_name ?? 'JasaKu Partner',
                'price' => (float) ($service->price ?? 0),
                'rating' => '4.8',
                'image_url' => $this->getServiceImage($service->gallery_image ?? null),
                'description' => Str::limit(strip_tags($service->description ?? 'Layanan terpercaya dari provider profesional.'), 90),
            ];
        });
    }

    private function getTopProviders()
    {
        $fallback = collect([
            (object) ['name' => 'JasaKu Cleaning', 'image_url' => null, 'status' => 'Verified Provider', 'total_services' => 0],
            (object) ['name' => 'JasaKu Repair', 'image_url' => null, 'status' => 'Verified Provider', 'total_services' => 0],
            (object) ['name' => 'JasaKu Home Care', 'image_url' => null, 'status' => 'Verified Provider', 'total_services' => 0],
            (object) ['name' => 'JasaKu Maintenance', 'image_url' => null, 'status' => 'Verified Provider', 'total_services' => 0],
        ]);

        if (!Schema::hasTable('users') || !Schema::hasTable('provider_profiles')) {
            return $fallback;
        }

        $query = DB::table('users')
            ->leftJoin('provider_profiles', 'users.id', '=', 'provider_profiles.user_id')
            ->where('users.role', 'provider')
            ->select(
                'users.id',
                'users.name',
                'provider_profiles.image',
                'provider_profiles.business_image',
                'provider_profiles.status',
                'provider_profiles.document_status'
            );

        if (Schema::hasColumn('provider_profiles', 'status')) {
            $query->where('provider_profiles.status', 'active');
        }

        $rows = $query->limit(8)->get();

        if ($rows->isEmpty()) {
            return $fallback;
        }

        return $rows->map(function ($provider) {
            return (object) [
                'name' => $provider->name ?? 'Provider',
                'image_url' => $this->imageUrl($provider->business_image ?: $provider->image),
                'status' => 'Verified Provider',
                'total_services' => $this->countProviderServices($provider->id),
            ];
        });
    }

    private function countActiveServices(): int
    {
        if (!Schema::hasTable('services')) {
            return 0;
        }

        $query = DB::table('services');

        if (Schema::hasColumn('services', 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn('services', 'verify_status')) {
            $query->where('verify_status', 'verified');
        }

        return $query->count();
    }

    private function countProviders(): int
    {
        if (!Schema::hasTable('users')) {
            return 0;
        }

        return DB::table('users')
            ->where('role', 'provider')
            ->count();
    }

    private function countServicesByCategory(?string $categoryName): int
    {
        if (!$categoryName || !Schema::hasTable('services')) {
            return 0;
        }

        $query = DB::table('services')
            ->where('category', $categoryName);

        if (Schema::hasColumn('services', 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn('services', 'verify_status')) {
            $query->where('verify_status', 'verified');
        }

        return $query->count();
    }

    private function countProviderServices($providerId): int
    {
        if (!$providerId || !Schema::hasTable('services')) {
            return 0;
        }

        $query = DB::table('services')
            ->where('provider_id', $providerId);

        if (Schema::hasColumn('services', 'status')) {
            $query->where('status', 'active');
        }

        if (Schema::hasColumn('services', 'verify_status')) {
            $query->where('verify_status', 'verified');
        }

        return $query->count();
    }

    private function getServiceImage($galleryImage): ?string
    {
        if (!$galleryImage) {
            return null;
        }

        $decoded = json_decode($galleryImage, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            $firstImage = $decoded[0] ?? null;

            if (is_array($firstImage)) {
                $firstImage = $firstImage['path'] ?? $firstImage['url'] ?? null;
            }

            return $this->imageUrl($firstImage);
        }

        if (Str::contains($galleryImage, ',')) {
            $images = explode(',', $galleryImage);
            return $this->imageUrl(trim($images[0] ?? ''));
        }

        return $this->imageUrl($galleryImage);
    }

    private function imageUrl($image): ?string
    {
        if (!$image) {
            return null;
        }

        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }

        if (Str::startsWith($image, ['storage/', 'admin/', 'provider/', 'customer/'])) {
            return asset($image);
        }

        return asset('storage/' . ltrim($image, '/'));
    }
}