<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Access denied.');
        }

        $status = $request->get('status', 'all');
        $search = $request->get('search');
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $query = Booking::with([
            'provider',
            'customer',
            'service',
            'services',
            'branch',
            'staff',
            'payment',
        ])->orderBy('booking_date', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('booking_code', 'like', '%' . $search . '%')
                    ->orWhereHas('provider', function ($providerQuery) use ($search) {
                        $providerQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('customer', function ($customerQuery) use ($search) {
                        $customerQuery->where('name', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('service', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('services', function ($serviceQuery) use ($search) {
                        $serviceQuery->where('title', 'like', '%' . $search . '%');
                    })
                    ->orWhereHas('branch', function ($branchQuery) use ($search) {
                        $branchQuery->where('branch_name', 'like', '%' . $search . '%')
                            ->orWhere('city_id', 'like', '%' . $search . '%');
                    });
            });
        }

        $bookings = $query->paginate($perPage)->withQueryString();

        $tabs = [
            'all' => 'All Bookings',
            'pending_payment' => 'Pending Payment',
            'confirmed' => 'Confirmed',
            'waiting' => 'Waiting',
            'checked_in' => 'Checked In',
            'in_progress' => 'In Progress',
            'pending' => 'Pending',
            'inprogress' => 'Inprogress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No-show',
        ];

        return view('admin.bookings.index', compact(
            'bookings',
            'tabs',
            'status',
            'search',
            'perPage'
        ));
    }
}
