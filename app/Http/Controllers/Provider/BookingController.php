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
        $date = $request->get('date', now()->toDateString());
        $status = $request->get('status', 'all');

        $bookings = $this->providerBookings()
            ->with($this->bookingFlow->bookingRelations())
            ->whereDate('booking_date', $date)
            ->when($status !== 'all', fn ($query) => $query->where('status', $status))
            ->orderByRaw('COALESCE(start_time, booking_time, "23:59:59")')
            ->orderBy('queue_number')
            ->get();

        $stats = [
            'total' => $bookings->count(),
            'waiting' => $bookings->where('status', 'waiting')->count(),
            'in_progress' => $bookings->whereIn('status', ['in_progress', 'inprogress'])->count(),
            'completed' => $bookings->whereIn('status', ['completed', 'order_completed'])->count(),
        ];

        return view('provider.pages.bookings.index', compact('bookings', 'date', 'status', 'stats'));
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
        $payments = Payment::query()
            ->with(['booking.provider', 'booking.customer', 'booking.branch', 'booking.services'])
            ->whereHas('booking', function ($query) {
                $query->where('provider_id', $this->providerId());
                ProviderAccountScope::applyBranchScope($query, $this->branchId());
            })
            ->latest()
            ->paginate(25);

        return view('provider.pages.payments.index', compact('payments'));
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
