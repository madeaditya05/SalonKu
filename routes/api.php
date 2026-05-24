<?php

use App\Http\Controllers\Api\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\Api\Admin\CouponController as AdminCouponController;
use App\Http\Controllers\Api\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Api\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Api\Admin\ServiceCategoryController as AdminServiceCategoryController;
use App\Http\Controllers\Api\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CouponValidationController;
use App\Http\Controllers\Api\Customer\BookingController as CustomerBookingController;
use App\Http\Controllers\Api\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Api\MidtransNotificationController;
use App\Http\Controllers\Api\Provider\BranchController as ProviderBranchController;
use App\Http\Controllers\Api\Provider\ProfileController as ProviderProfileController;
use App\Http\Controllers\Api\Provider\ServiceController as ProviderServiceController;
use App\Http\Controllers\Api\Provider\StaffController as ProviderStaffController;
use App\Http\Controllers\Api\PublicCatalogController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('/register/customer', [AuthController::class, 'registerCustomer'])
        ->name('api.auth.register-customer');
    Route::post('/register/provider', [AuthController::class, 'registerProvider'])
        ->name('api.auth.register-provider');
    Route::post('/login', [AuthController::class, 'login'])
        ->name('api.auth.login');
});

Route::get('/categories', [PublicCatalogController::class, 'categories'])
    ->name('api.categories.index');
Route::get('/locations', [PublicCatalogController::class, 'locations'])
    ->name('api.locations.index');
Route::get('/branches', [PublicCatalogController::class, 'branches'])
    ->name('api.branches.index');
Route::get('/branches/{branch}/services', [PublicCatalogController::class, 'branchServices'])
    ->name('api.branches.services');
Route::get('/branches/{branch}/staff', [PublicCatalogController::class, 'branchStaff'])
    ->name('api.branches.staff');
Route::get('/branches/{branch}', [PublicCatalogController::class, 'branch'])
    ->name('api.branches.show');
Route::get('/services', [PublicCatalogController::class, 'services'])
    ->name('api.services.index');
Route::get('/services/{service}', [PublicCatalogController::class, 'service'])
    ->name('api.services.show');
Route::get('/providers', [PublicCatalogController::class, 'providers'])
    ->name('api.providers.index');
Route::get('/coupons', [CouponValidationController::class, 'index'])
    ->name('api.coupons.index');
Route::post('/customer/booking/check-availability', [CustomerBookingController::class, 'checkAvailability'])
    ->name('api.customer.booking.check-availability');
Route::post('/coupons/validate', [CouponValidationController::class, 'validate'])
    ->name('api.coupons.validate');
Route::post('/midtrans/notification', MidtransNotificationController::class)
    ->name('api.midtrans.notification');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me'])
        ->name('api.auth.me');
    Route::post('/auth/logout', [AuthController::class, 'logout'])
        ->name('api.auth.logout');

    Route::prefix('customer')->name('api.customer.')->group(function () {
        Route::get('/bookings', [CustomerBookingController::class, 'index'])
            ->name('bookings.index');
        Route::post('/bookings', [CustomerBookingController::class, 'store'])
            ->name('bookings.store');
        Route::get('/bookings/{booking}', [CustomerBookingController::class, 'show'])
            ->name('bookings.show');
        Route::post('/bookings/{booking}/payment/charge', [CustomerPaymentController::class, 'charge'])
            ->name('bookings.payment.charge');
        Route::get('/bookings/{booking}/payment/status', [CustomerPaymentController::class, 'status'])
            ->name('bookings.payment.status');
        Route::patch('/bookings/{booking}/cancel', [CustomerBookingController::class, 'cancel'])
            ->name('bookings.cancel');
    });

    Route::prefix('admin')->name('api.admin.')->group(function () {
        Route::get('/bookings', [AdminBookingController::class, 'index'])
            ->name('bookings.index');
        Route::get('/bookings/{booking}', [AdminBookingController::class, 'show'])
            ->name('bookings.show');
        Route::patch('/bookings/{booking}/status', [AdminBookingController::class, 'updateStatus'])
            ->name('bookings.status');

        Route::apiResource('service-categories', AdminServiceCategoryController::class)
            ->parameters(['service-categories' => 'serviceCategory']);
        Route::patch('/service-categories/{serviceCategory}/toggle-featured', [AdminServiceCategoryController::class, 'toggleFeatured'])
            ->name('service-categories.toggle-featured');
        Route::patch('/service-categories/{serviceCategory}/toggle-status', [AdminServiceCategoryController::class, 'toggleStatus'])
            ->name('service-categories.toggle-status');

        Route::get('/services', [AdminServiceController::class, 'index'])
            ->name('services.index');
        Route::get('/services/{service}', [AdminServiceController::class, 'show'])
            ->name('services.show');
        Route::patch('/services/{service}/toggle-status', [AdminServiceController::class, 'toggleStatus'])
            ->name('services.toggle-status');

        Route::apiResource('coupons', AdminCouponController::class);

        Route::get('/providers', [AdminProviderController::class, 'index'])
            ->name('providers.index');
        Route::get('/providers/{provider}', [AdminProviderController::class, 'show'])
            ->name('providers.show');
        Route::patch('/providers/{provider}/toggle-status', [AdminProviderController::class, 'toggleStatus'])
            ->name('providers.toggle-status');
        Route::patch('/providers/{provider}/document-status', [AdminProviderController::class, 'updateDocumentStatus'])
            ->name('providers.document-status');
        Route::delete('/providers/{provider}', [AdminProviderController::class, 'destroy'])
            ->name('providers.destroy');

        Route::get('/customers', [AdminCustomerController::class, 'index'])
            ->name('customers.index');
        Route::get('/customers/{customer}', [AdminCustomerController::class, 'show'])
            ->name('customers.show');
        Route::patch('/customers/{customer}/toggle-status', [AdminCustomerController::class, 'toggleStatus'])
            ->name('customers.toggle-status');
        Route::delete('/customers/{customer}', [AdminCustomerController::class, 'destroy'])
            ->name('customers.destroy');
    });

    Route::prefix('provider')->name('api.provider.')->group(function () {
        Route::get('/profile', [ProviderProfileController::class, 'show'])
            ->name('profile.show');
        Route::put('/profile', [ProviderProfileController::class, 'update'])
            ->name('profile.update');
        Route::post('/profile/documents', [ProviderProfileController::class, 'updateDocuments'])
            ->name('profile.documents');
        Route::put('/profile/password', [ProviderProfileController::class, 'updatePassword'])
            ->name('profile.password');

        Route::apiResource('services', ProviderServiceController::class);
        Route::put('/services/{service}/branch', [ProviderServiceController::class, 'updateBranch'])
            ->name('services.branch');
        Route::put('/services/{service}/gallery', [ProviderServiceController::class, 'updateGallery'])
            ->name('services.gallery');
        Route::patch('/services/{service}/toggle-status', [ProviderServiceController::class, 'toggleStatus'])
            ->name('services.toggle-status');

        Route::apiResource('staff', ProviderStaffController::class)
            ->parameters(['staff' => 'staff']);

        Route::apiResource('branches', ProviderBranchController::class)
            ->parameters(['branches' => 'branch']);
        Route::put('/branches/{branch}/staff', [ProviderBranchController::class, 'updateStaff'])
            ->name('branches.staff');
    });
});
