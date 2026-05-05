<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Service;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function index()
    {
        $provider = Auth::user();

        if (! $provider || $provider->role !== 'provider') {
            abort(403, 'Akses ditolak.');
        }

        $stats = [
            'upcoming_bookings' => 0,
            'completed_bookings' => 0,
            'order_completed' => 0,
            'canceled_bookings' => 0,
            'total_earnings' => 0,
            'total_income' => 0,
            'total_due' => 0,
        ];

        $topServices = collect();
        $recentBookings = collect();

        if (Schema::hasTable('bookings') && Schema::hasColumn('bookings', 'provider_id')) {
            $bookingQuery = Booking::where('provider_id', $provider->id);

            if (Schema::hasColumn('bookings', 'status')) {
                $stats['upcoming_bookings'] = (clone $bookingQuery)
                    ->whereIn('status', ['open', 'pending', 'inprogress'])
                    ->count();

                $stats['completed_bookings'] = (clone $bookingQuery)
                    ->whereIn('status', ['completed', 'order_completed'])
                    ->count();

                $stats['order_completed'] = (clone $bookingQuery)
                    ->whereIn('status', ['order_completed', 'completed'])
                    ->count();

                $stats['canceled_bookings'] = (clone $bookingQuery)
                    ->whereIn('status', ['cancelled', 'canceled', 'provider_cancelled', 'customer_cancelled'])
                    ->count();
            } else {
                $stats['upcoming_bookings'] = (clone $bookingQuery)->count();
            }

            if (Schema::hasColumn('bookings', 'amount')) {
                $stats['total_income'] = (clone $bookingQuery)->sum('amount');

                if (Schema::hasColumn('bookings', 'status')) {
                    $stats['total_earnings'] = (clone $bookingQuery)
                        ->whereIn('status', ['completed', 'order_completed'])
                        ->sum('amount');

                    $stats['total_due'] = (clone $bookingQuery)
                        ->whereIn('status', ['open', 'pending', 'inprogress'])
                        ->sum('amount');
                } else {
                    $stats['total_earnings'] = $stats['total_income'];
                    $stats['total_due'] = 0;
                }
            }

            $recentBookings = (clone $bookingQuery)
                ->with(['customer', 'service'])
                ->latest('id')
                ->take(5)
                ->get();
        }

        if (Schema::hasTable('services') && Schema::hasColumn('services', 'provider_id')) {
            $topServices = Service::where('provider_id', $provider->id)
                ->latest('id')
                ->take(4)
                ->get();
        }

        $currentPlan = [
            'label' => 'Current Plan',
            'name' => 'Life Time',
            'price' => 0,
            'description' => 'Your current active subscription plan.',
        ];

        $allPlanPreview = [
            'name' => 'Life Time',
            'price' => 0,
            'description' => 'Our most popular plan for small teams.',
        ];

        return view('provider.dashboard.index', compact(
            'provider',
            'stats',
            'topServices',
            'recentBookings',
            'currentPlan',
            'allPlanPreview'
        ));
    }
}