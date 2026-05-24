<?php

namespace App\Support;

use App\Models\ProviderRole;
use App\Models\User;
use Illuminate\Support\Str;

class ProviderMenuAccess
{
    private const ALWAYS_ALLOWED_KEYS = [
        'dashboard',
    ];

    private static array $permissionCache = [];

    public static function sections(): array
    {
        return [
            [
                'title' => 'Main Menu',
                'items' => [
                    [
                        'key' => 'dashboard',
                        'label' => 'Dashboard',
                        'description' => 'Ringkasan performa, booking, revenue, dan aktivitas provider.',
                    ],
                    [
                        'key' => 'leads',
                        'label' => 'Leads',
                        'description' => 'Akses data calon customer dan prospek masuk.',
                    ],
                ],
            ],
            [
                'title' => 'Finance',
                'items' => [
                    [
                        'key' => 'payments',
                        'label' => 'Pembayaran',
                        'description' => 'Melihat transaksi, status pembayaran, dan invoice booking.',
                    ],
                    [
                        'key' => 'payout',
                        'label' => 'Payout',
                        'description' => 'Mengelola saldo, withdraw, dan pencairan provider.',
                    ],
                ],
            ],
            [
                'title' => 'Business',
                'items' => [
                    [
                        'key' => 'services',
                        'label' => 'My Service',
                        'description' => 'Mengatur layanan, harga, durasi, galeri, dan status service.',
                    ],
                    [
                        'key' => 'bookings',
                        'label' => 'Booking Hari Ini',
                        'description' => 'Melihat booking masuk dan menjalankan status check-in sampai selesai.',
                    ],
                    [
                        'key' => 'calendar',
                        'label' => 'Kalender Staff',
                        'description' => 'Melihat jadwal booking dan agenda staff.',
                    ],
                    [
                        'key' => 'queue',
                        'label' => 'Antrian',
                        'description' => 'Mengelola antrian dan memanggil customer.',
                    ],
                    [
                        'key' => 'walk_in',
                        'label' => 'Walk-in',
                        'description' => 'Membuat booking customer offline atau datang langsung.',
                    ],
                    [
                        'key' => 'branch',
                        'label' => 'Branch',
                        'description' => 'Mengelola cabang, lokasi, dan staff di setiap cabang.',
                    ],
                    [
                        'key' => 'staffs',
                        'label' => 'Staffs',
                        'description' => 'Mengelola data staff, profil, kontak, cabang, dan status.',
                    ],
                    [
                        'key' => 'staff_skills',
                        'label' => 'Skill Staff',
                        'description' => 'Menentukan service yang bisa dikerjakan oleh setiap staff.',
                    ],
                    [
                        'key' => 'staff_schedules',
                        'label' => 'Jadwal Staff',
                        'description' => 'Mengatur hari dan jam kerja staff.',
                    ],
                ],
            ],
            [
                'title' => 'Marketing',
                'items' => [
                    [
                        'key' => 'subscription',
                        'label' => 'Subscription',
                        'description' => 'Akses paket langganan dan benefit marketing.',
                    ],
                    [
                        'key' => 'reviews',
                        'label' => 'Reviews',
                        'description' => 'Melihat rating, review, dan feedback customer.',
                    ],
                ],
            ],
            [
                'title' => 'Support',
                'items' => [
                    [
                        'key' => 'chat',
                        'label' => 'Chat',
                        'description' => 'Mengakses percakapan realtime dengan admin support.',
                    ],
                    [
                        'key' => 'notifications',
                        'label' => 'Notification',
                        'description' => 'Mengakses notifikasi dan alert operasional.',
                    ],
                    [
                        'key' => 'tickets',
                        'label' => 'Support Help',
                        'description' => 'Melihat FAQ dan mengajukan tiket chat support.',
                    ],
                ],
            ],
            [
                'title' => 'Access',
                'items' => [
                    [
                        'key' => 'roles_permissions',
                        'label' => 'Roles & Permissions',
                        'description' => 'Mengatur role cabang dan akses menu yang diberikan.',
                    ],
                ],
            ],
            [
                'title' => 'Preferences',
                'items' => [
                    [
                        'key' => 'profile',
                        'label' => 'My Profile',
                        'description' => 'Mengubah profil, dokumen, dan password akun.',
                    ],
                    [
                        'key' => 'settings_general',
                        'label' => 'General Settings',
                        'description' => 'Mengatur preferensi umum provider.',
                    ],
                    [
                        'key' => 'settings_payment',
                        'label' => 'Payment Settings',
                        'description' => 'Mengatur preferensi pembayaran.',
                    ],
                    [
                        'key' => 'settings_notification',
                        'label' => 'Notification Settings',
                        'description' => 'Mengatur preferensi notifikasi.',
                    ],
                ],
            ],
        ];
    }

    public static function keys(): array
    {
        return collect(self::sections())
            ->flatMap(fn (array $section) => $section['items'])
            ->pluck('key')
            ->all();
    }

    public static function labels(): array
    {
        return collect(self::sections())
            ->flatMap(fn (array $section) => $section['items'])
            ->mapWithKeys(fn (array $item) => [$item['key'] => $item['label']])
            ->all();
    }

    public static function userCanAccess(?User $user, ?string $menuKey): bool
    {
        if (! $menuKey || in_array($menuKey, self::ALWAYS_ALLOWED_KEYS, true)) {
            return true;
        }

        if (! $user || $user->role !== 'provider') {
            return true;
        }

        if ($menuKey === 'roles_permissions') {
            return self::isProviderOwner($user);
        }

        if (self::isProviderOwner($user)) {
            return true;
        }

        return in_array($menuKey, self::userPermissionKeys($user), true);
    }

    public static function routeMenuKey(?string $routeName): ?string
    {
        if (! $routeName) {
            return null;
        }

        if (Str::startsWith($routeName, 'provider-branch.')) {
            $routeName = 'provider.' . Str::after($routeName, 'provider-branch.');
        }

        $patterns = [
            'provider.dashboard' => 'dashboard',
            'provider.profile' => 'profile',
            'provider.profile.*' => 'profile',
            'provider.services.*' => 'services',
            'provider.staffs.*' => 'staffs',
            'provider.branch.*' => 'branch',
            'provider.bookings.*' => 'bookings',
            'provider.calendar.*' => 'calendar',
            'provider.queue.*' => 'queue',
            'provider.walk-in.*' => 'walk_in',
            'provider.staff.skills' => 'staff_skills',
            'provider.staff.skills.*' => 'staff_skills',
            'provider.staff.schedules' => 'staff_schedules',
            'provider.staff.schedules.*' => 'staff_schedules',
            'provider.payments.*' => 'payments',
            'provider.chat.*' => 'chat',
            'provider.tickets.*' => 'tickets',
            'provider.roles-permissions.*' => 'roles_permissions',
        ];

        foreach ($patterns as $pattern => $menuKey) {
            if (Str::is($pattern, $routeName)) {
                return $menuKey;
            }
        }

        return null;
    }

    public static function isProviderOwner(User $user): bool
    {
        $providerId = (int) ($user->provider_id ?: $user->id);

        return $user->role === 'provider'
            && $providerId === (int) $user->id
            && empty($user->provider_role_id);
    }

    public static function providerOwnerId(User $user): int
    {
        return (int) ($user->provider_id ?: $user->id);
    }

    private static function userPermissionKeys(User $user): array
    {
        $cacheKey = $user->getAuthIdentifier() . ':' . ($user->provider_role_id ?: 'none');

        if (array_key_exists($cacheKey, self::$permissionCache)) {
            return self::$permissionCache[$cacheKey];
        }

        if (! $user->provider_role_id) {
            return self::$permissionCache[$cacheKey] = [];
        }

        $providerId = self::providerOwnerId($user);

        $role = ProviderRole::query()
            ->with('menuPermissions:id,provider_role_id,menu_key')
            ->whereKey($user->provider_role_id)
            ->where('provider_id', $providerId)
            ->where('status', 'active')
            ->first();

        if (! $role) {
            return self::$permissionCache[$cacheKey] = [];
        }

        return self::$permissionCache[$cacheKey] = $role->menuPermissions
            ->pluck('menu_key')
            ->unique()
            ->values()
            ->all();
    }
}
