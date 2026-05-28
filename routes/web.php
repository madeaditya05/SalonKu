<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\UnifiedLoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\ProfileController as AdminProfileController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\SupportChatController;

/*
|--------------------------------------------------------------------------
| Provider Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Provider\StaffController as ProviderStaffController;
use App\Http\Controllers\Provider\BranchController;
use App\Http\Controllers\Provider\BookingController as ProviderBookingController;
use App\Http\Controllers\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Provider\RolePermissionController as ProviderRolePermissionController;
use App\Support\FrontendUrl;
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Frontend Entry
|--------------------------------------------------------------------------
| Landing page customer/provider akan ditangani React.
| Laravel tetap memberi respons ringan sampai frontend React dipasang.
*/

Route::get('/', function () {
    return redirect()->route('admin.login');
})->name('home');
/*
|--------------------------------------------------------------------------
| Default Login Redirect
|--------------------------------------------------------------------------
| Karena login customer belum dibuat, route /login sementara diarahkan
| ke admin login agar middleware auth Laravel tidak error.
*/

Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

Route::middleware('auth')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index'])
        ->name('notifications.index');

    Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])
        ->name('notifications.read-all');

    Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
        ->name('notifications.read');
});

/*
|--------------------------------------------------------------------------
| Customer Landing
|--------------------------------------------------------------------------
| Landing page customer ditangani React dan bisa di-host terpisah.
*/
$customerLanding = fn () => redirect()->away(FrontendUrl::customer(request()));

Route::get('/customer', $customerLanding)
    ->name('customer.landing');
Route::get('/customers', $customerLanding)
    ->name('customer.landing.alias');
Route::get('/customer/landing', $customerLanding)
    ->name('customer.landing.page');

/*
|--------------------------------------------------------------------------
| Provider Session Auth
|--------------------------------------------------------------------------
| Landing page provider akan ditangani React.
| Route ini tetap dipakai untuk membuat session Laravel sebelum masuk
| ke dashboard Blade provider.
*/
$providerFrontendUrl = function (array $query = []) {
    $url = FrontendUrl::provider(request());

    if ($query !== []) {
        $url .= (str_contains($url, '?') ? '&' : '?') . http_build_query($query);
    }

    return $url;
};

$providerLanding = function () use ($providerFrontendUrl) {
    if (Auth::guard('provider')->check() && Auth::guard('provider')->user()?->role === 'provider') {
        return redirect()->to(route('provider.dashboard', [], false));
    }

    if (Auth::guard('provider_branch')->check() && Auth::guard('provider_branch')->user()?->role === 'provider') {
        return redirect()->to(route('provider-branch.dashboard', [], false));
    }

    return redirect()->away($providerFrontendUrl());
};

Route::get('/provider', $providerLanding)
    ->name('provider.landing');
Route::get('/providers', $providerLanding)
    ->name('provider.landing.alias');
Route::get('/provider/landing', $providerLanding)
    ->name('provider.landing.page');

Route::get('/provider/login', function () use ($providerFrontendUrl) {
    if (Auth::guard('provider')->check() && Auth::guard('provider')->user()?->role === 'provider') {
        return redirect()->to(route('provider.dashboard', [], false));
    }

    if (Auth::guard('provider_branch')->check() && Auth::guard('provider_branch')->user()?->role === 'provider') {
        return redirect()->to(route('provider-branch.dashboard', [], false));
    }

    return redirect()->away($providerFrontendUrl(['login' => 'open']));
})->name('provider.login');

Route::get('/provider/register', function () use ($providerFrontendUrl) {
    if (Auth::guard('provider')->check() && Auth::guard('provider')->user()?->role === 'provider') {
        return redirect()->to(route('provider.dashboard', [], false));
    }

    if (Auth::guard('provider_branch')->check() && Auth::guard('provider_branch')->user()?->role === 'provider') {
        return redirect()->to(route('provider-branch.dashboard', [], false));
    }

    return redirect()->away($providerFrontendUrl(['register' => 'open']));
})->name('provider.register');

Route::post('/provider/signin', [UnifiedLoginController::class, 'providerSignin'])
    ->name('provider.signin');

/*
|--------------------------------------------------------------------------
| Provider Dashboard Routes
|--------------------------------------------------------------------------
*/

$registerProviderDashboardRoutes = function () {
        Route::get('/notifications', [NotificationController::class, 'index'])
            ->name('notifications.index');

        Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])
            ->name('notifications.read-all');

        Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
            ->name('notifications.read');

        /*
        |--------------------------------------------------------------------------
        | Provider Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [ProviderDashboardController::class, 'index'])
            ->name('dashboard');

        /*
        |--------------------------------------------------------------------------
        | Provider Profile
        |--------------------------------------------------------------------------
        | Provider yang belum verified tetap bisa membuka profile.
        */

        Route::middleware(['provider.menu:profile'])->group(function () {
            Route::get('/profile', [ProviderProfileController::class, 'show'])
                ->name('profile');

            Route::get('/profile/edit', [ProviderProfileController::class, 'edit'])
                ->name('profile.edit');

            Route::put('/profile', [ProviderProfileController::class, 'update'])
                ->name('profile.update');

            Route::post('/profile/documents', [ProviderProfileController::class, 'updateDocuments'])
                ->name('profile.documents.update');

            Route::put('/profile/password', [ProviderProfileController::class, 'updatePassword'])
                ->name('profile.password.update');
        });

        Route::middleware(['provider.menu:chat'])->group(function () {
            Route::get('/chat', [SupportChatController::class, 'providerIndex'])
                ->name('chat.index');

            Route::get('/chat/{thread}/messages', [SupportChatController::class, 'providerMessages'])
                ->name('chat.messages.index');

            Route::post('/chat/{thread}/messages', [SupportChatController::class, 'providerStore'])
                ->name('chat.messages.store');

            Route::post('/chat/{thread}/read', [SupportChatController::class, 'providerRead'])
                ->name('chat.read');

            Route::post('/chat/internal', [SupportChatController::class, 'providerInternalStart'])
                ->name('chat.internal.start');
        });

        Route::middleware(['provider.menu:tickets'])->group(function () {
            Route::get('/tickets', [SupportChatController::class, 'providerTicketsIndex'])
                ->name('tickets.index');

            Route::post('/tickets', [SupportChatController::class, 'providerTicketStore'])
                ->name('tickets.store');
        });

        /*
        |--------------------------------------------------------------------------
        | Provider Routes yang Butuh Dokumen Verified
        |--------------------------------------------------------------------------
        */

        Route::middleware(['provider.document.verified'])->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Provider Services
            |--------------------------------------------------------------------------
            */

            Route::middleware(['provider.menu:services'])->group(function () {
                Route::get('/service', [ProviderServiceController::class, 'index'])
                    ->name('services.index');

                Route::get('/service/create', [ProviderServiceController::class, 'create'])
                    ->name('services.create');

                Route::post('/service/continue-information', [ProviderServiceController::class, 'continueInformation'])
                    ->name('services.continue.information');

                Route::post('/service/continue-branch', [ProviderServiceController::class, 'continueBranch'])
                    ->name('services.continue.branch');

                Route::post('/service/store', [ProviderServiceController::class, 'store'])
                    ->name('services.store');

                Route::get('/service/{service}/edit', [ProviderServiceController::class, 'edit'])
                    ->name('services.edit');

                Route::put('/service/{service}', [ProviderServiceController::class, 'update'])
                    ->name('services.update');

                Route::put('/service/{service}/branch', [ProviderServiceController::class, 'updateBranch'])
                    ->name('services.update.branch');

                Route::put('/service/{service}/gallery', [ProviderServiceController::class, 'updateGallery'])
                    ->name('services.update.gallery');

                Route::patch('/service/{service}/toggle-status', [ProviderServiceController::class, 'toggleStatus'])
                    ->name('services.toggle-status');

                Route::delete('/service/{service}', [ProviderServiceController::class, 'destroy'])
                    ->name('services.destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Provider Staffs
            |--------------------------------------------------------------------------
            */

            Route::middleware(['provider.menu:staffs'])->group(function () {
                Route::get('/staff-list', [ProviderStaffController::class, 'index'])
                    ->name('staffs.index');

                Route::post('/staff-list', [ProviderStaffController::class, 'store'])
                    ->name('staffs.store');

                Route::put('/staff-list/{staff}', [ProviderStaffController::class, 'update'])
                    ->name('staffs.update');

                Route::delete('/staff-list/{staff}', [ProviderStaffController::class, 'destroy'])
                    ->name('staffs.destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Provider Roles & Permissions
            |--------------------------------------------------------------------------
            */

            Route::middleware(['provider.menu:roles_permissions'])->group(function () {
                Route::get('/roles-permissions', [ProviderRolePermissionController::class, 'index'])
                    ->name('roles-permissions.index');

                Route::post('/roles-permissions', [ProviderRolePermissionController::class, 'store'])
                    ->name('roles-permissions.store');

                Route::put('/roles-permissions/{role}', [ProviderRolePermissionController::class, 'update'])
                    ->name('roles-permissions.update');

                Route::delete('/roles-permissions/{role}', [ProviderRolePermissionController::class, 'destroy'])
                    ->name('roles-permissions.destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Provider Branch
            |--------------------------------------------------------------------------
            */

            Route::middleware(['provider.menu:branch'])->group(function () {
                Route::get('/branch', [BranchController::class, 'index'])
                    ->name('branch.index');

                Route::get('/add-branch', [BranchController::class, 'create'])
                    ->name('branch.create');

                Route::post('/branch/continue', [BranchController::class, 'continue'])
                    ->name('branch.continue');

                Route::post('/branch', [BranchController::class, 'store'])
                    ->name('branch.store');

                Route::get('/branch/{branch}/edit', [BranchController::class, 'edit'])
                    ->name('branch.edit');

                Route::put('/branch/{branch}', [BranchController::class, 'update'])
                    ->name('branch.update');

                Route::put('/branch/{branch}/staff', [BranchController::class, 'updateStaff'])
                    ->name('branch.staff.update');

                Route::delete('/branch/{branch}', [BranchController::class, 'destroy'])
                    ->name('branch.destroy');
            });

            /*
            |--------------------------------------------------------------------------
            | Provider Booking Flow
            |--------------------------------------------------------------------------
            */

            Route::middleware(['provider.menu:bookings'])->group(function () {
                Route::get('/bookings', [ProviderBookingController::class, 'index'])
                    ->name('bookings.index');

                Route::post('/bookings/{booking}/check-in', [ProviderBookingController::class, 'checkIn'])
                    ->name('bookings.check-in');
                Route::post('/bookings/{booking}/start', [ProviderBookingController::class, 'start'])
                    ->name('bookings.start');
                Route::post('/bookings/{booking}/complete', [ProviderBookingController::class, 'complete'])
                    ->name('bookings.complete');
                Route::post('/bookings/{booking}/cancel', [ProviderBookingController::class, 'cancel'])
                    ->name('bookings.cancel');
                Route::post('/bookings/{booking}/no-show', [ProviderBookingController::class, 'noShow'])
                    ->name('bookings.no-show');
            });

            Route::get('/calendar', [ProviderBookingController::class, 'calendar'])
                ->middleware(['provider.menu:calendar'])
                ->name('calendar.index');

            Route::middleware(['provider.menu:queue'])->group(function () {
                Route::get('/queue', [ProviderBookingController::class, 'queue'])
                    ->name('queue.index');
                Route::post('/queue/{booking}/call', [ProviderBookingController::class, 'call'])
                    ->name('queue.call');
            });

            Route::middleware(['provider.menu:walk_in'])->group(function () {
                Route::get('/walk-in', [ProviderBookingController::class, 'walkIn'])
                    ->name('walk-in.index');
                Route::post('/walk-in', [ProviderBookingController::class, 'storeWalkIn'])
                    ->name('walk-in.store');
            });

            Route::middleware(['provider.menu:staff_skills'])->group(function () {
                Route::get('/staff/skills', [ProviderBookingController::class, 'skills'])
                    ->name('staff.skills');
                Route::post('/staff/skills', [ProviderBookingController::class, 'updateSkills'])
                    ->name('staff.skills.update');
            });

            Route::middleware(['provider.menu:staff_schedules'])->group(function () {
                Route::get('/staff/schedules', [ProviderBookingController::class, 'schedules'])
                    ->name('staff.schedules');
                Route::post('/staff/schedules', [ProviderBookingController::class, 'updateSchedules'])
                    ->name('staff.schedules.update');
            });

            Route::get('/payments', [ProviderBookingController::class, 'payments'])
                ->middleware(['provider.menu:payments'])
                ->name('payments.index');

        });

        /*
        |--------------------------------------------------------------------------
        | Provider Logout
        |--------------------------------------------------------------------------
        */

        Route::post('/logout', [UnifiedLoginController::class, 'providerLogout'])
            ->name('logout');
};

Route::prefix('provider')
    ->name('provider.')
    ->middleware(['auth:provider', 'prevent-back-history', 'provider.account.active'])
    ->group($registerProviderDashboardRoutes);

Route::prefix('provider-branch')
    ->name('provider-branch.')
    ->middleware(['auth:provider_branch', 'prevent-back-history', 'provider.account.active'])
    ->group($registerProviderDashboardRoutes);

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/

Route::prefix('admin')
    ->name('admin.')
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Admin Auth
        |--------------------------------------------------------------------------
        */

        Route::middleware('guest:admin')->group(function () {
            Route::get('/login', [UnifiedLoginController::class, 'showLoginForm'])
                ->name('login');

            Route::post('/login', [UnifiedLoginController::class, 'login'])
                ->name('login.post');
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Protected Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware(['auth:admin', 'prevent-back-history'])->group(function () {
            Route::get('/notifications', [NotificationController::class, 'index'])
                ->name('notifications.index');

            Route::post('/notifications/read', [NotificationController::class, 'markAllRead'])
                ->name('notifications.read-all');

            Route::post('/notifications/{notification}/read', [NotificationController::class, 'markRead'])
                ->name('notifications.read');

            Route::post('/logout', [UnifiedLoginController::class, 'logout'])
                ->name('logout');

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            Route::get('/dashboard/export/{format}', [DashboardController::class, 'export'])
                ->whereIn('format', ['pdf', 'csv', 'excel'])
                ->name('dashboard.export');

            /*
            |--------------------------------------------------------------------------
            | Admin Bookings & Calendar
            |--------------------------------------------------------------------------
            */

            Route::get('/bookings', [BookingController::class, 'index'])
                ->name('bookings.index');

            Route::get('/calendar', [CalendarController::class, 'index'])
                ->name('calendar.index');

            Route::get('/chat', [SupportChatController::class, 'adminIndex'])
                ->name('chat.index');

            Route::get('/chat/{thread}/messages', [SupportChatController::class, 'adminMessages'])
                ->name('chat.messages.index');

            Route::post('/chat/{thread}/messages', [SupportChatController::class, 'adminStore'])
                ->name('chat.messages.store');

            Route::post('/chat/{thread}/read', [SupportChatController::class, 'adminRead'])
                ->name('chat.read');

            Route::post('/chat/{thread}/ticket/end', [SupportChatController::class, 'adminTicketEnd'])
                ->name('chat.ticket.end');

            Route::get('/tickets', [SupportChatController::class, 'adminTicketsIndex'])
                ->name('tickets.index');

            Route::post('/tickets/{thread}/approve', [SupportChatController::class, 'adminTicketApprove'])
                ->name('tickets.approve');

            Route::post('/tickets/{thread}/reject', [SupportChatController::class, 'adminTicketReject'])
                ->name('tickets.reject');

            /*
            |--------------------------------------------------------------------------
            | Admin Services
            |--------------------------------------------------------------------------
            */

            Route::get('/services', [ServiceController::class, 'index'])
                ->name('services.index');

            Route::patch('/services/{service}/toggle-status', [ServiceController::class, 'toggleStatus'])
                ->name('services.toggle-status');

            /*
            |--------------------------------------------------------------------------
            | Admin Service Categories
            |--------------------------------------------------------------------------
            */

            Route::get('/service/categories', [ServiceCategoryController::class, 'index'])
                ->name('service-categories.index');

            Route::post('/service/categories', [ServiceCategoryController::class, 'store'])
                ->name('service-categories.store');

            Route::put('/service/categories/{category}', [ServiceCategoryController::class, 'update'])
                ->name('service-categories.update');

            Route::patch('/service/categories/{category}/toggle-featured', [ServiceCategoryController::class, 'toggleFeatured'])
                ->name('service-categories.toggle-featured');

            Route::patch('/service/categories/{category}/toggle-status', [ServiceCategoryController::class, 'toggleStatus'])
                ->name('service-categories.toggle-status');

            Route::delete('/service/categories/{category}', [ServiceCategoryController::class, 'destroy'])
                ->name('service-categories.destroy');

            /*
            |--------------------------------------------------------------------------
            | Admin Coupons
            |--------------------------------------------------------------------------
            */

            Route::get('/coupons', [CouponController::class, 'index'])
                ->name('coupons.index');

            Route::get('/create-coupon', [CouponController::class, 'create'])
                ->name('coupons.create');

            Route::post('/coupons', [CouponController::class, 'store'])
                ->name('coupons.store');

            Route::get('/coupons/{coupon}/edit', [CouponController::class, 'edit'])
                ->name('coupons.edit');

            Route::put('/coupons/{coupon}', [CouponController::class, 'update'])
                ->name('coupons.update');

            Route::delete('/coupons/{coupon}', [CouponController::class, 'destroy'])
                ->name('coupons.destroy');

            /*
            |--------------------------------------------------------------------------
            | Admin Providers
            |--------------------------------------------------------------------------
            */

            Route::get('/providers', [ProviderController::class, 'index'])
                ->name('providers.index');

            Route::get('/provider/view/{user}', [ProviderController::class, 'show'])
                ->name('providers.show');

            Route::get('/providers/{user}', [ProviderController::class, 'show'])
                ->name('providers.view');

            Route::patch('/providers/{user}/toggle-status', [ProviderController::class, 'toggleStatus'])
                ->name('providers.toggle-status');

            Route::patch('/providers/{user}/document-status', [ProviderController::class, 'updateDocumentStatus'])
                ->name('providers.document-status');

            Route::delete('/providers/{user}', [ProviderController::class, 'destroy'])
                ->name('providers.destroy');

            /*
            |--------------------------------------------------------------------------
            | Admin Customers / Users
            |--------------------------------------------------------------------------
            | Controller tetap UserController karena tabel database masih users.
            | Ini hanya route admin, bukan route public /user.
            */

            Route::get('/users', [UserController::class, 'index'])
                ->name('users.index');

            Route::get('/users/{user}', [UserController::class, 'show'])
                ->name('users.show');

            Route::patch('/users/{user}/toggle-status', [UserController::class, 'toggleStatus'])
                ->name('users.toggle-status');

            Route::delete('/users/{user}', [UserController::class, 'destroy'])
                ->name('users.destroy');

            /*
            |--------------------------------------------------------------------------
            | Admin Profile
            |--------------------------------------------------------------------------
            */

            Route::get('/profile', [AdminProfileController::class, 'show'])
                ->name('profile');

            Route::patch('/profile', [AdminProfileController::class, 'update'])
                ->name('profile.update');

            Route::put('/profile/password', [AdminProfileController::class, 'updatePassword'])
                ->name('profile.password.update');
        });
    });
