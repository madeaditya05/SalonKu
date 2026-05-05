<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        if (Auth::user()->role !== 'admin') {
            abort(403, 'Anda tidak memiliki akses ke halaman admin.');
        }

        $stats = [
            'total_providers' => 4,
            'active_providers' => 4,
            'inactive_providers' => 0,

            'total_services' => 15,
            'active_services' => 15,
            'inactive_services' => 0,

            'total_bookings' => 11,
            'completed_bookings' => 6,
            'pending_bookings' => 5,

            'total_amount' => 1425.6,
            'completed_amount' => 831.6,
            'pending_amount' => 594,
        ];

        return view('admin.dashboard.index', compact('stats'));
    }
}