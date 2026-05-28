<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderBranch;
use App\Models\ProviderStaff;
use App\Models\Service;
use App\Models\StaffSchedule;
use App\Services\BookingFlowService;
use App\Support\ProviderAccountScope;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private readonly BookingFlowService $bookingFlow)
    {
    }

    public function index(Request $request)
    {
        $status = (string) $request->get('status', 'all');
        $search = trim((string) $request->get('search', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100], true) ? $perPage : 10;

        $allowedStatuses = [
            'all',
            'open',
            'pending',
            'pending_payment',
            'confirmed',
            'waiting',
            'checked_in',
            'in_progress',
            'inprogress',
            'completed',
            'order_completed',
            'refund_completed',
            'provider_cancelled',
            'customer_cancelled',
            'rescheduled',
            'cancelled',
            'no_show',
        ];

        $paymentStatuses = [
            'all' => 'All Payments',
            'unpaid' => 'Unpaid',
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];

        $bookingTypes = [
            'all' => 'All Modes',
            'scheduled' => 'Scheduled',
            'queue' => 'Queue',
            'walk_in' => 'Walk In',
        ];

        $sortOptions = [
            'booking_date' => 'Appointment Date',
            'created_at' => 'Created Date',
            'amount' => 'Total Amount',
            'payment_status' => 'Payment Status',
            'status' => 'Booking Status',
            'booking_type' => 'Mode',
            'booking_code' => 'Booking Code',
        ];

        $status = in_array($status, $allowedStatuses, true) ? $status : 'all';

        $paymentStatus = (string) $request->get('payment_status', 'all');
        $paymentStatus = array_key_exists($paymentStatus, $paymentStatuses) ? $paymentStatus : 'all';

        $bookingType = (string) $request->get('booking_type', 'all');
        $bookingType = array_key_exists($bookingType, $bookingTypes) ? $bookingType : 'all';

        $legacyDate = $request->filled('date') ? (string) $request->get('date') : null;
        $dateFrom = $request->filled('date_from') ? (string) $request->get('date_from') : $legacyDate;
        $dateTo = $request->filled('date_to') ? (string) $request->get('date_to') : $legacyDate;

        if ($dateFrom && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            $dateFrom = null;
        }

        if ($dateTo && ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateTo)) {
            $dateTo = null;
        }

        $sortBy = (string) $request->get('sort_by', 'booking_date');
        $sortBy = array_key_exists($sortBy, $sortOptions) ? $sortBy : 'booking_date';

        $sortDirection = strtolower((string) $request->get('sort_direction', 'desc'));
        $sortDirection = in_array($sortDirection, ['asc', 'desc'], true) ? $sortDirection : 'desc';

        $filters = [
            'status' => $status,
            'search' => $search,
            'per_page' => $perPage,
            'payment_status' => $paymentStatus,
            'booking_type' => $bookingType,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'sort_by' => $sortBy,
            'sort_direction' => $sortDirection,
        ];

        $query = $this->applyBookingFilters($this->providerBookings(), $filters)
            ->with($this->bookingFlow->bookingRelations());

        $summaryQuery = $this->applyBookingFilters($this->providerBookings(), $filters);

        $summary = [
            'total' => (clone $summaryQuery)->count(),
            'paid' => (clone $summaryQuery)->where(function ($paidQuery) {
                $paidQuery->where('payment_status', 'paid')
                    ->orWhereHas('payment', fn ($paymentQuery) => $paymentQuery->where('status', 'paid'));
            })->count(),
            'pending' => (clone $summaryQuery)->whereIn('status', ['pending', 'pending_payment', 'confirmed', 'waiting', 'checked_in', 'in_progress', 'inprogress'])->count(),
            'completed' => (clone $summaryQuery)->whereIn('status', ['completed', 'order_completed'])->count(),
            'amount' => (float) (clone $summaryQuery)->selectRaw('COALESCE(SUM(COALESCE(total_price, amount, 0)), 0) as aggregate')->value('aggregate'),
        ];

        $sortExpressions = [
            'booking_date' => 'booking_date',
            'created_at' => 'created_at',
            'amount' => 'COALESCE(total_price, amount, 0)',
            'payment_status' => 'payment_status',
            'status' => 'status',
            'booking_type' => 'booking_type',
            'booking_code' => 'booking_code',
        ];

        $query->orderByRaw($sortExpressions[$sortBy] . ' ' . $sortDirection)
            ->orderByRaw('COALESCE(start_time, booking_time, "23:59:59") asc')
            ->orderBy('queue_number')
            ->orderByDesc('id');

        $bookings = $query->paginate($perPage)->withQueryString();

        $tabs = [
            'all' => 'All Bookings',
            'pending_payment' => 'Pending Payment',
            'confirmed' => 'Confirmed',
            'waiting' => 'Waiting',
            'checked_in' => 'Checked In',
            'in_progress' => 'In Progress',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            'no_show' => 'No-show',
        ];

        $hasActiveFilters = $search !== ''
            || $status !== 'all'
            || $paymentStatus !== 'all'
            || $bookingType !== 'all'
            || ! empty($dateFrom)
            || ! empty($dateTo)
            || $perPage !== 10
            || $sortBy !== 'booking_date'
            || $sortDirection !== 'desc';

        $date = $dateFrom ?: now()->toDateString();
        $stats = [
            'total' => $summary['total'],
            'waiting' => (clone $summaryQuery)->where('status', 'waiting')->count(),
            'in_progress' => (clone $summaryQuery)->whereIn('status', ['in_progress', 'inprogress'])->count(),
            'completed' => $summary['completed'],
        ];

        return view('provider.pages.bookings.index', compact(
            'bookings',
            'tabs',
            'status',
            'search',
            'perPage',
            'filters',
            'paymentStatuses',
            'bookingTypes',
            'sortOptions',
            'sortBy',
            'sortDirection',
            'summary',
            'hasActiveFilters',
            'date',
            'stats'
        ));
    }

    public function calendar(Request $request)
    {
        $date = $request->get('date', now()->toDateString());
        $staffs = ProviderStaff::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->with(['bookings' => fn ($query) => $query
                ->with(['services', 'branch', 'customer'])
                ->whereDate('booking_date', $date)
                ->when($this->branchId() !== null, fn ($bookingQuery) => $bookingQuery->where('branch_id', $this->branchId()))
                ->whereIn('status', BookingFlowService::ACTIVE_BOOKING_STATUSES)
                ->orderByRaw('COALESCE(start_time, booking_time, "23:59:59")')])
            ->orderBy('first_name');
        ProviderAccountScope::applyBranchScope($staffs, $this->branchId());

        $staffs = $staffs->get();

        $unassignedQueue = $this->providerBookings()
            ->with(['services', 'branch', 'customer'])
            ->whereDate('booking_date', $date)
            ->whereNull('staff_id')
            ->whereIn('booking_type', ['queue', 'walk_in'])
            ->whereIn('status', BookingFlowService::ACTIVE_BOOKING_STATUSES)
            ->orderBy('queue_number')
            ->get();

        return view('provider.pages.calendar.index', compact('staffs', 'unassignedQueue', 'date'));
    }

    public function queue(Request $request)
    {
        $date = $request->get('date', now()->toDateString());

        $queueBookings = $this->providerBookings()
            ->with($this->bookingFlow->bookingRelations())
            ->whereDate('booking_date', $date)
            ->whereIn('booking_type', ['queue', 'walk_in'])
            ->whereIn('status', ['waiting', 'checked_in', 'in_progress', 'inprogress'])
            ->orderBy('queue_number')
            ->get();

        return view('provider.pages.queue.index', compact('queueBookings', 'date'));
    }

    public function walkIn(Request $request)
    {
        return view('provider.pages.walk-in.index', $this->formData());
    }

    public function storeWalkIn(Request $request)
    {
        $validated = $request->validate([
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'branch_id' => [
                'required',
                Rule::exists('provider_branches', 'id')->where(function ($query) {
                    $query->where('provider_id', $this->providerId());
                    ProviderAccountScope::applyBranchScope($query, $this->branchId(), 'id');
                }),
            ],
            'service_ids' => ['required', 'array', 'min:1'],
            'service_ids.*' => ['required', 'integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('provider_id', $this->providerId()))],
            'staff_id' => ['nullable', 'integer', Rule::exists('provider_staffs', 'id')->where(function ($query) {
                $query->where('provider_id', $this->providerId());
                ProviderAccountScope::applyBranchScope($query, $this->branchId());
            })],
            'notes' => ['nullable', 'string', 'max:1000'],
            'payment_type' => ['nullable', Rule::in(['dp', 'full_payment', 'pay_at_salon'])],
        ]);

        $booking = $this->bookingFlow->createBooking(array_merge($validated, [
            'booking_type' => 'walk_in',
            'booking_date' => now()->toDateString(),
            'payment_type' => $validated['payment_type'] ?? 'pay_at_salon',
        ]), null, true);

        return provider_route_redirect('provider.queue.index')
            ->with('success', 'Walk-in berhasil masuk antrian #' . $booking->queue_number . '.');
    }

    public function skills()
    {
        $staffs = ProviderStaff::query()
            ->where('provider_id', $this->providerId())
            ->with(['branch', 'skills'])
            ->orderBy('first_name');
        ProviderAccountScope::applyBranchScope($staffs, $this->branchId());

        $staffs = $staffs->get();

        $services = Service::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->orderBy('title');
        ProviderAccountScope::applyServiceBranchScope($services, $this->branchId());

        $services = $services->get();

        return view('provider.pages.staff.skills', compact('staffs', 'services'));
    }

    public function updateSkills(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => ['required', Rule::exists('provider_staffs', 'id')->where(function ($query) {
                $query->where('provider_id', $this->providerId());
                ProviderAccountScope::applyBranchScope($query, $this->branchId());
            })],
            'skills' => ['nullable', 'array'],
            'skills.*' => ['nullable', 'array'],
            'skills.*.*' => ['integer', Rule::exists('services', 'id')->where(fn ($query) => $query->where('provider_id', $this->providerId()))],
        ]);

        $staffQuery = ProviderStaff::query()
            ->where('provider_id', $this->providerId())
            ->with('branch');
        ProviderAccountScope::applyBranchScope($staffQuery, $this->branchId());

        $staff = $staffQuery->findOrFail($validated['staff_id']);

        $serviceIds = collect(data_get($validated, 'skills.' . $staff->id, []))
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $validServiceQuery = Service::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->whereIn('id', $serviceIds);
        ProviderAccountScope::applyServiceBranchScope($validServiceQuery, $this->branchId());

        $validServiceIds = $validServiceQuery->get()
            ->filter(fn (Service $service) => $this->serviceIsAvailableForStaffBranch($service, $staff))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($validServiceIds->count() !== $serviceIds->count()) {
            return back()
                ->withErrors(['skills' => 'Ada service yang tidak tersedia di branch staff ini.'])
                ->withInput();
        }

        $staff->skills()->sync($validServiceIds->all());

        return provider_route_redirect('provider.staff.skills')
            ->with('success', 'Skill ' . ($staff->full_name ?: $staff->email) . ' berhasil diperbarui.')
            ->with('updated_staff_id', $staff->id);
    }

    public function schedules()
    {
        $staffs = ProviderStaff::query()
            ->where('provider_id', $this->providerId())
            ->with('schedules')
            ->orderBy('first_name');
        ProviderAccountScope::applyBranchScope($staffs, $this->branchId());

        $staffs = $staffs->get();

        return view('provider.pages.staff.schedules', compact('staffs'));
    }

    public function updateSchedules(Request $request)
    {
        $validated = $request->validate([
            'staff_id' => ['required', Rule::exists('provider_staffs', 'id')->where(function ($query) {
                $query->where('provider_id', $this->providerId());
                ProviderAccountScope::applyBranchScope($query, $this->branchId());
            })],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['required', 'string', 'max:20'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
        ]);

        DB::transaction(function () use ($validated) {
            StaffSchedule::where('staff_id', $validated['staff_id'])->delete();

            foreach ($validated['days'] as $day) {
                StaffSchedule::create([
                    'staff_id' => $validated['staff_id'],
                    'day_of_week' => $day,
                    'start_time' => $validated['start_time'],
                    'end_time' => $validated['end_time'],
                    'is_available' => true,
                ]);
            }
        });

        return provider_route_redirect('provider.staff.schedules')
            ->with('success', 'Jadwal staff berhasil diperbarui.');
    }

    public function payments(Request $request)
    {
        $paymentStatuses = [
            'all' => 'All Payments',
            'unpaid' => 'Unpaid',
            'pending' => 'Pending',
            'paid' => 'Paid',
            'failed' => 'Failed',
            'refunded' => 'Refunded',
        ];
        $paymentTypes = [
            'all' => 'All Types',
            'dp' => 'DP',
            'full_payment' => 'Full Payment',
            'pay_at_salon' => 'Pay at Salon',
        ];
        $sortOptions = [
            'created_at' => 'Created Date',
            'paid_at' => 'Paid Date',
            'amount' => 'Amount',
            'status' => 'Status',
            'payment_type' => 'Type',
        ];
        $perPageOptions = [10, 25, 50, 100];

        $filters = [
            'status' => array_key_exists((string) $request->get('status', 'all'), $paymentStatuses)
                ? (string) $request->get('status', 'all')
                : 'all',
            'payment_type' => array_key_exists((string) $request->get('payment_type', 'all'), $paymentTypes)
                ? (string) $request->get('payment_type', 'all')
                : 'all',
            'search' => trim((string) $request->get('search', '')),
            'date_from' => $request->get('date_from'),
            'date_to' => $request->get('date_to'),
            'per_page' => in_array((int) $request->get('per_page', 25), $perPageOptions, true)
                ? (int) $request->get('per_page', 25)
                : 25,
            'sort_by' => array_key_exists((string) $request->get('sort_by', 'created_at'), $sortOptions)
                ? (string) $request->get('sort_by', 'created_at')
                : 'created_at',
            'sort_direction' => $request->get('sort_direction') === 'asc' ? 'asc' : 'desc',
        ];

        $baseQuery = $this->providerPaymentsQuery();
        $filteredQuery = $this->applyPaymentFilters(clone $baseQuery, $filters);

        $summary = [
            'total' => (clone $filteredQuery)->count(),
            'amount' => (float) (clone $filteredQuery)->sum('amount'),
            'paid' => (clone $filteredQuery)->where('status', 'paid')->count(),
            'pending' => (clone $filteredQuery)->whereIn('status', ['unpaid', 'pending'])->count(),
        ];

        $statusBreakdownQuery = $this->applyPaymentFilters(clone $baseQuery, $filters, ['status']);
        $statusBreakdown = collect($paymentStatuses)
            ->reject(fn ($label, $status) => $status === 'all')
            ->map(fn ($label, $status) => [
                'key' => $status,
                'label' => $label,
                'count' => (clone $statusBreakdownQuery)->where('status', $status)->count(),
                'amount' => (float) (clone $statusBreakdownQuery)->where('status', $status)->sum('amount'),
            ])
            ->values()
            ->all();

        $typeBreakdownQuery = $this->applyPaymentFilters(clone $baseQuery, $filters, ['payment_type']);
        $typeBreakdown = collect($paymentTypes)
            ->reject(fn ($label, $type) => $type === 'all')
            ->map(fn ($label, $type) => [
                'key' => $type,
                'label' => $label,
                'count' => (clone $typeBreakdownQuery)->where('payment_type', $type)->count(),
                'amount' => (float) (clone $typeBreakdownQuery)->where('payment_type', $type)->sum('amount'),
            ])
            ->values()
            ->all();

        $tabCounts = collect($paymentStatuses)
            ->mapWithKeys(fn ($label, $status) => [
                $status => $status === 'all'
                    ? (clone $statusBreakdownQuery)->count()
                    : (clone $statusBreakdownQuery)->where('status', $status)->count(),
            ])
            ->all();

        $payments = $filteredQuery
            ->with(['booking.provider', 'booking.customer', 'booking.branch', 'booking.service', 'booking.services', 'booking.staff'])
            ->orderBy($filters['sort_by'], $filters['sort_direction'])
            ->paginate($filters['per_page'])
            ->withQueryString();

        $hasActiveFilters = $filters['status'] !== 'all'
            || $filters['payment_type'] !== 'all'
            || $filters['search'] !== ''
            || ! empty($filters['date_from'])
            || ! empty($filters['date_to'])
            || $filters['per_page'] !== 25
            || $filters['sort_by'] !== 'created_at'
            || $filters['sort_direction'] !== 'desc';

        return view('provider.pages.payments.index', compact(
            'payments',
            'filters',
            'paymentStatuses',
            'paymentTypes',
            'sortOptions',
            'summary',
            'statusBreakdown',
            'typeBreakdown',
            'tabCounts',
            'hasActiveFilters'
        ));
    }

    public function call(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking = $this->bookingFlow->updateStatus($booking, 'checked_in');

        return back()->with('success', 'Antrian #' . $booking->queue_number . ' dipanggil.');
    }

    public function checkIn(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $this->bookingFlow->updateStatus($booking, 'checked_in');

        return back()->with('success', 'Customer berhasil check-in.');
    }

    public function start(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $booking->load(['branch', 'services']);

        if (! $booking->staff_id && $booking->branch && $booking->services->isNotEmpty()) {
            $staff = $this->bookingFlow->chooseStaffForQueue($booking->branch, $booking->services, null, optional($booking->booking_date)->toDateString() ?: now()->toDateString());
            $booking->update(['staff_id' => $staff?->id]);
        }

        $this->bookingFlow->updateStatus($booking->refresh(), 'in_progress');

        return back()->with('success', 'Service dimulai.');
    }

    public function complete(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $this->bookingFlow->completeBooking($booking);

        return back()->with('success', 'Service selesai. Staff tersedia kembali.');
    }

    public function cancel(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $this->bookingFlow->updateStatus($booking, 'cancelled');

        return back()->with('success', 'Booking dibatalkan.');
    }

    public function noShow(Booking $booking)
    {
        $this->authorizeBooking($booking);
        $this->bookingFlow->updateStatus($booking, 'no_show');

        return back()->with('success', 'Booking ditandai no-show.');
    }

    private function formData(): array
    {
        $branches = ProviderBranch::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->orderBy('branch_name');
        ProviderAccountScope::applyBranchModelScope($branches, $this->branchId());

        $branches = $branches->get();

        $services = Service::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->with('serviceCategory')
            ->orderBy('title');
        ProviderAccountScope::applyServiceBranchScope($services, $this->branchId());

        $services = $services->get();

        $staffs = ProviderStaff::query()
            ->where('provider_id', $this->providerId())
            ->where('status', 'active')
            ->orderBy('first_name');
        ProviderAccountScope::applyBranchScope($staffs, $this->branchId());

        $staffs = $staffs->get();

        return compact('branches', 'services', 'staffs');
    }

    private function providerBookings()
    {
        $query = Booking::query()->where('provider_id', $this->providerId());
        ProviderAccountScope::applyBranchScope($query, $this->branchId());

        return $query;
    }

    private function applyBookingFilters($query, array $filters)
    {
        if (($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (($filters['payment_status'] ?? 'all') !== 'all') {
            $paymentStatus = $filters['payment_status'];

            $query->where(function ($paymentQuery) use ($paymentStatus) {
                $paymentQuery->where('payment_status', $paymentStatus)
                    ->orWhereHas('payment', fn ($relationQuery) => $relationQuery->where('status', $paymentStatus));
            });
        }

        if (($filters['booking_type'] ?? 'all') !== 'all') {
            $query->where('booking_type', $filters['booking_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('booking_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('booking_date', '<=', $filters['date_to']);
        }

        if (($filters['search'] ?? '') !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('booking_code', 'like', $search)
                    ->orWhere('customer_name', 'like', $search)
                    ->orWhere('customer_phone', 'like', $search)
                    ->orWhere('payment_status', 'like', $search)
                    ->orWhere('booking_type', 'like', $search)
                    ->orWhere('status', 'like', $search)
                    ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', $search)->orWhere('email', 'like', $search))
                    ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $search))
                    ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $search))
                    ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('branch_name', 'like', $search)->orWhere('city_id', 'like', $search))
                    ->orWhereHas('staff', function ($staffQuery) use ($search) {
                        $staffQuery
                            ->where('first_name', 'like', $search)
                            ->orWhere('last_name', 'like', $search)
                            ->orWhere('username', 'like', $search)
                            ->orWhere('email', 'like', $search);
                    });
            });
        }

        return $query;
    }

    private function providerPaymentsQuery()
    {
        return Payment::query()
            ->whereHas('booking', function ($query) {
                $query->where('provider_id', $this->providerId());
                ProviderAccountScope::applyBranchScope($query, $this->branchId());
            });
    }

    private function applyPaymentFilters($query, array $filters, array $except = [])
    {
        if (! in_array('status', $except, true) && ($filters['status'] ?? 'all') !== 'all') {
            $query->where('status', $filters['status']);
        }

        if (! in_array('payment_type', $except, true) && ($filters['payment_type'] ?? 'all') !== 'all') {
            $query->where('payment_type', $filters['payment_type']);
        }

        if (! in_array('date_from', $except, true) && ! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! in_array('date_to', $except, true) && ! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! in_array('search', $except, true) && ($filters['search'] ?? '') !== '') {
            $search = '%' . $filters['search'] . '%';

            $query->where(function ($searchQuery) use ($search) {
                $searchQuery
                    ->where('payment_method', 'like', $search)
                    ->orWhere('payment_channel', 'like', $search)
                    ->orWhere('payment_code', 'like', $search)
                    ->orWhere('payment_code_label', 'like', $search)
                    ->orWhere('midtrans_order_id', 'like', $search)
                    ->orWhere('midtrans_transaction_id', 'like', $search)
                    ->orWhereHas('booking', function ($bookingQuery) use ($search) {
                        $bookingQuery
                            ->where('booking_code', 'like', $search)
                            ->orWhere('customer_name', 'like', $search)
                            ->orWhere('customer_phone', 'like', $search)
                            ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('name', 'like', $search)->orWhere('email', 'like', $search))
                            ->orWhereHas('branch', fn ($branchQuery) => $branchQuery->where('branch_name', 'like', $search))
                            ->orWhereHas('service', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $search))
                            ->orWhereHas('services', fn ($serviceQuery) => $serviceQuery->where('title', 'like', $search));
                    });
            });
        }

        return $query;
    }

    private function authorizeBooking(Booking $booking): void
    {
        abort_unless((int) $booking->provider_id === $this->providerId(), 403);
        abort_if($this->branchId() !== null && (int) $booking->branch_id !== $this->branchId(), 403);
    }

    private function serviceIsAvailableForStaffBranch(Service $service, ProviderStaff $staff): bool
    {
        if (! $staff->branch_id) {
            return true;
        }

        $branchIds = $service->branch_ids;

        if (empty($branchIds)) {
            return true;
        }

        return in_array((int) $staff->branch_id, array_map('intval', (array) $branchIds), true);
    }

    private function providerId(): int
    {
        return ProviderAccountScope::providerId(Auth::user());
    }

    private function branchId(): ?int
    {
        return ProviderAccountScope::branchId(Auth::user());
    }
}
