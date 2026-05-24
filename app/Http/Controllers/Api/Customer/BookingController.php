<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Api\ApiController;
use App\Models\Booking;
use App\Models\ProviderBranch;
use App\Services\AppNotificationService;
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
        $this->authorizeRole($request, 'customer');

        $bookings = Booking::query()
            ->with($this->bookingFlow->bookingRelations())
            ->where('customer_id', $request->user()->id)
            ->when($request->query('status') && $request->query('status') !== 'all', fn ($query) => $query->where('status', $request->query('status')))
            ->latest('booking_date')
            ->latest()
            ->paginate($this->perPage($request));

        return response()->json($bookings);
    }

    public function checkAvailability(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('provider_branches', 'id')],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', Rule::exists('services', 'id')],
            'booking_date' => ['nullable', 'date', 'after_or_equal:today'],
            'staff_id' => ['nullable', 'integer', Rule::exists('provider_staffs', 'id')],
            'booking_type' => ['required', Rule::in(['scheduled', 'queue'])],
        ]);

        $branch = ProviderBranch::query()
            ->with('provider.providerProfile')
            ->whereKey($validated['branch_id'])
            ->where('status', 'active')
            ->firstOrFail();

        abort_unless($this->bookingFlow->branchIsBookable($branch), 404);

        $services = $this->bookingFlow->servicesForBooking(
            $branch,
            $this->bookingFlow->normalizedServiceIds($validated),
            $validated['booking_type']
        );

        return response()->json([
            'data' => $this->bookingFlow->availabilityPayload(
                $branch,
                $services,
                $validated['booking_date'] ?? null,
                $validated['staff_id'] ?? null,
                $validated['booking_type']
            ),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeRole($request, 'customer');

        $validated = $request->validate([
            'branch_id' => ['required', 'integer', Rule::exists('provider_branches', 'id')],
            'service_ids' => ['required_without:service_id', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', Rule::exists('services', 'id')],
            'service_id' => ['nullable', 'integer', Rule::exists('services', 'id')],
            'booking_type' => ['required', Rule::in(['scheduled', 'queue'])],
            'staff_id' => ['nullable', 'integer', Rule::exists('provider_staffs', 'id')],
            'booking_date' => ['nullable', 'date', 'after_or_equal:today', 'required_if:booking_type,scheduled'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_if:booking_type,scheduled'],
            'booking_time' => ['nullable', 'date_format:H:i'],
            'payment_type' => ['required', Rule::in(['dp', 'full_payment', 'pay_at_salon'])],
            'coupon_code' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $booking = $this->bookingFlow->createBooking($validated, $request->user());
        $this->notifyProviderBookingCreated($booking, $request);

        return response()->json([
            'message' => 'Booking berhasil dibuat.',
            'data' => $booking,
        ], 201);
    }

    public function show(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeCustomerBooking($request, $booking);

        return response()->json([
            'data' => $booking->load($this->bookingFlow->bookingRelations()),
        ]);
    }

    public function cancel(Request $request, Booking $booking): JsonResponse
    {
        $this->authorizeCustomerBooking($request, $booking);

        abort_unless(
            in_array($booking->status, BookingFlowService::CANCELLABLE_STATUSES, true),
            422,
            'Booking ini tidak bisa dibatalkan.'
        );

        $booking = $this->bookingFlow->updateStatus($booking, 'cancelled');
        $this->notifyProviderBookingCancelled($booking, $request);

        return response()->json([
            'message' => 'Booking berhasil dibatalkan.',
            'data' => $booking,
        ]);
    }

    private function notifyProviderBookingCreated(Booking $booking, Request $request): void
    {
        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->providerRecipients((int) $booking->provider_id, 'bookings'),
            'booking.created',
            'Booking baru',
            (($booking->customer_name ?: $request->user()?->name) ?: 'Customer') . ' membuat booking ' . $booking->booking_code . '.',
            route('provider.bookings.index', ['date' => $booking->booking_date?->toDateString()]),
            [
                'booking_id' => (int) $booking->id,
                'booking_code' => $booking->booking_code,
                'provider_id' => (int) $booking->provider_id,
                'branch_id' => (int) $booking->branch_id,
            ],
            (int) $request->user()?->id
        );
    }

    private function notifyProviderBookingCancelled(Booking $booking, Request $request): void
    {
        app(AppNotificationService::class)->createForUsers(
            app(AppNotificationService::class)->providerRecipients((int) $booking->provider_id, 'bookings'),
            'booking.cancelled',
            'Booking dibatalkan',
            (($booking->customer_name ?: $request->user()?->name) ?: 'Customer') . ' membatalkan booking ' . $booking->booking_code . '.',
            route('provider.bookings.index', ['date' => $booking->booking_date?->toDateString()]),
            [
                'booking_id' => (int) $booking->id,
                'booking_code' => $booking->booking_code,
                'provider_id' => (int) $booking->provider_id,
                'branch_id' => (int) $booking->branch_id,
            ],
            (int) $request->user()?->id
        );
    }

    private function authorizeCustomerBooking(Request $request, Booking $booking): void
    {
        $this->authorizeRole($request, 'customer');

        abort_unless((int) $booking->customer_id === (int) $request->user()->id, 403, 'Access denied.');
    }
}
