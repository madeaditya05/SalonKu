<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\ApiController;
use App\Models\Booking;
use App\Services\BookingFlowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class BookingController extends ApiController
{
    public function __construct(private readonly BookingFlowService $bookingFlow)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorizeRole($request, 'admin');

        $bookings = Booking::query()
            ->with($this->bookingFlow->bookingRelations())
            ->when($request->query('status') && $request->query('status') !== 'all', fn ($query) => $query->where('status', $request->query('status')))
            ->when($request->query('search'), function ($query, $search) {
                $query->where('booking_code', 'like', "%{$search}%")
                    ->orWhereHas('provider', fn ($providerQuery) => $providerQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->where('title', 'like', "%{$search}%"))
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('branch_name', 'like', "%{$search}%"));
            })
            ->orderByDesc('booking_date')
            ->paginate($this->perPage($request));

        return response()->json($bookings);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeRole($request, 'admin');

        return response()->json(['data' => $booking->load($this->bookingFlow->bookingRelations())]);
    }

    public function updateStatus(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeRole($request, 'admin');

        $validated = $request->validate([
            'status' => ['required', Rule::in([
                'open',
                'pending',
                'inprogress',
                'completed',
                'order_completed',
                'refund_completed',
                'provider_cancelled',
                'customer_cancelled',
                'rescheduled',
                'pending_payment',
                'confirmed',
                'waiting',
                'checked_in',
                'in_progress',
                'cancelled',
                'no_show',
            ])],
        ]);

        $booking->update($validated);

        return response()->json(['message' => 'Booking status has been updated.', 'data' => $booking->refresh()]);
    }
}
