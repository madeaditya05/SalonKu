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
            abort(403, 'Akses ditolak.');
        }

        $status = $request->get('status', 'all');
        $search = $request->get('search');

        $query = Booking::with([
            'provider',
            'customer',
            'service',
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
                    });
            });
        }

        $bookings = $query->paginate(10)->withQueryString();

        $tabs = [
            'all' => 'All Bookings',
            'pending' => 'Pending',
            'inprogress' => 'Inprogress',
            'completed' => 'Completed',
            'order_completed' => 'Order Completed',
            'refund_completed' => 'Refund completed',
            'provider_cancelled' => 'Provider Cancelled',
            'customer_cancelled' => 'Customer Cancelled',
            'rescheduled' => 'Rescheduled',
        ];

        return view('admin.bookings.index', compact(
            'bookings',
            'tabs',
            'status',
            'search'
        ));
    }
}