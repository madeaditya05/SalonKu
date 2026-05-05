<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\Auth\LoginController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\ServiceCategoryController;
use App\Http\Controllers\Admin\ServiceSubCategoryController;
use App\Http\Controllers\Admin\CouponController;
use App\Http\Controllers\Admin\ProviderController;
use App\Http\Controllers\Admin\UserController;

/*
|--------------------------------------------------------------------------
| Provider Controllers
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Provider\ProviderLandingController;
use App\Http\Controllers\Provider\DashboardController as ProviderDashboardController;
use App\Http\Controllers\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Provider\StaffController as ProviderStaffController;
use App\Http\Controllers\Provider\BranchController;
use App\Http\Controllers\Provider\ProfileController as ProviderProfileController;

/*
|--------------------------------------------------------------------------
| Customer Controllers
|--------------------------------------------------------------------------
*/
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Customer\LandingPageController as CustomerLandingPageController;
use App\Http\Controllers\Customer\Auth\LoginController as CustomerLoginController;
use App\Http\Controllers\Customer\Auth\RegisterController as CustomerRegisterController;

/*
|--------------------------------------------------------------------------
| Customer Public Landing Page Routes
|--------------------------------------------------------------------------
| Saat ini customer baru punya landing page.
| Login, register, logout, dan dashboard customer belum dibuat.
*/

Route::get('/', [CustomerLandingPageController::class, 'index'])
    ->name('home');

Route::get('/customer', [CustomerLandingPageController::class, 'index'])
    ->name('customer.landing');

Route::get('/customer/landing', [CustomerLandingPageController::class, 'index'])
    ->name('customer.landing.page');

/*
|--------------------------------------------------------------------------
| Customer Auth Popup Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/customer/login', function () {
        return redirect()->route('home')->with('auth_modal', 'signin');
    })->name('customer.login');

    Route::get('/customer/signup', function () {
        return redirect()->route('home')->with('auth_modal', 'signup');
    })->name('customer.register');

    Route::post('/customer/signin', [CustomerLoginController::class, 'signin'])
        ->name('customer.signin');

    Route::post('/customer/signup', [CustomerRegisterController::class, 'signup'])
        ->name('customer.signup');
});

Route::post('/customer/logout', function () {
    Auth::logout();

    request()->session()->invalidate();
    request()->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('customer.logout');

Route::get('/login', function () {
    return redirect()->route('home')->with('auth_modal', 'signin');
})->name('login');
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

/*
|--------------------------------------------------------------------------
| Provider Public Routes
|--------------------------------------------------------------------------
| /providers = halaman publik provider
| /provider  = alias halaman publik provider
*/

Route::get('/providers', [ProviderLandingController::class, 'index'])
    ->name('provider.landing');

Route::get('/provider', [ProviderLandingController::class, 'index'])
    ->name('provider.landing.alias');

Route::get('/provider/landing', [ProviderLandingController::class, 'index'])
    ->name('provider.landing.provider');

Route::post('/provider/register', [ProviderLandingController::class, 'register'])
    ->name('provider.register');

Route::post('/provider/signin', [ProviderLandingController::class, 'signin'])
    ->name('provider.signin');

/*
|--------------------------------------------------------------------------
| Provider Dashboard Routes
|--------------------------------------------------------------------------
*/

Route::prefix('provider')
    ->name('provider.')
    ->middleware(['auth', 'prevent-back-history', 'provider.account.active'])
    ->group(function () {
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

            /*
            |--------------------------------------------------------------------------
            | Provider Staffs
            |--------------------------------------------------------------------------
            */

            Route::get('/staff-list', [ProviderStaffController::class, 'index'])
                ->name('staffs.index');

            Route::post('/staff-list', [ProviderStaffController::class, 'store'])
                ->name('staffs.store');

            Route::put('/staff-list/{staff}', [ProviderStaffController::class, 'update'])
                ->name('staffs.update');

            Route::delete('/staff-list/{staff}', [ProviderStaffController::class, 'destroy'])
                ->name('staffs.destroy');

            /*
            |--------------------------------------------------------------------------
            | Provider Branch
            |--------------------------------------------------------------------------
            */

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
        | Provider Logout
        |--------------------------------------------------------------------------
        */

        Route::post('/logout', [ProviderLandingController::class, 'logout'])
            ->name('logout');
    });

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

        Route::middleware('guest')->group(function () {
            Route::get('/login', [LoginController::class, 'showLoginForm'])
                ->name('login');

            Route::post('/login', [LoginController::class, 'login'])
                ->name('login.post');
        });

        /*
        |--------------------------------------------------------------------------
        | Admin Protected Routes
        |--------------------------------------------------------------------------
        */

        Route::middleware(['auth', 'prevent-back-history'])->group(function () {
            Route::post('/logout', [LoginController::class, 'logout'])
                ->name('logout');

            Route::get('/dashboard', [DashboardController::class, 'index'])
                ->name('dashboard');

            /*
            |--------------------------------------------------------------------------
            | Admin Bookings & Calendar
            |--------------------------------------------------------------------------
            */

            Route::get('/bookings', [BookingController::class, 'index'])
                ->name('bookings.index');

            Route::get('/calendar', [CalendarController::class, 'index'])
                ->name('calendar.index');

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
            | Admin Service Sub Categories
            |--------------------------------------------------------------------------
            */

            Route::get('/service/subcategories', [ServiceSubCategoryController::class, 'index'])
                ->name('service-subcategories.index');

            Route::post('/service/subcategories', [ServiceSubCategoryController::class, 'store'])
                ->name('service-subcategories.store');

            Route::put('/service/subcategories/{subCategory}', [ServiceSubCategoryController::class, 'update'])
                ->name('service-subcategories.update');

            Route::patch('/service/subcategories/{subCategory}/toggle-featured', [ServiceSubCategoryController::class, 'toggleFeatured'])
                ->name('service-subcategories.toggle-featured');

            Route::patch('/service/subcategories/{subCategory}/toggle-status', [ServiceSubCategoryController::class, 'toggleStatus'])
                ->name('service-subcategories.toggle-status');

            Route::delete('/service/subcategories/{subCategory}', [ServiceSubCategoryController::class, 'destroy'])
                ->name('service-subcategories.destroy');

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

            Route::get('/profile', function () {
                return view('admin.profile.index');
            })->name('profile');
        });
    });