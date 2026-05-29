<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Payment;
use App\Models\ProviderBranch;
use App\Models\ProviderStaff;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingFlowService
{
    public const ACTIVE_BOOKING_STATUSES = [
        'open',
        'pending',
        'pending_payment',
        'confirmed',
        'waiting',
        'checked_in',
        'inprogress',
        'in_progress',
        'rescheduled',
    ];

    public const CLOSED_BOOKING_STATUSES = [
        'completed',
        'order_completed',
        'refund_completed',
        'provider_cancelled',
        'customer_cancelled',
        'cancelled',
        'no_show',
    ];

    public const CANCELLABLE_STATUSES = [
        'open',
        'pending',
        'pending_payment',
        'confirmed',
        'waiting',
        'checked_in',
        'rescheduled',
    ];

    public function __construct(private readonly CouponService $coupons)
    {
    }

    public function normalizedServiceIds(array $payload): array
    {
        $serviceIds = $payload['service_ids'] ?? [];

        if (empty($serviceIds) && ! empty($payload['service_id'])) {
            $serviceIds = [$payload['service_id']];
        }

        return collect((array) $serviceIds)
            ->filter(fn ($id) => filled($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function branchIsBookable(ProviderBranch $branch): bool
    {
        $branch->loadMissing('provider.providerProfile');

        return $branch->status === 'active'
            && $branch->provider?->role === 'provider'
            && optional($branch->provider?->providerProfile)->status === 'active'
            && optional($branch->provider?->providerProfile)->document_status === 'verified';
    }

    public function servicesForBooking(ProviderBranch $branch, array $serviceIds, string $bookingType = 'scheduled'): Collection
    {
        if (empty($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'Pilih minimal satu service.',
            ]);
        }

        $services = Service::query()
            ->with('serviceCategory')
            ->whereIn('id', $serviceIds)
            ->get()
            ->sortBy(fn (Service $service) => array_search((int) $service->id, $serviceIds, true))
            ->values();

        if ($services->count() !== count($serviceIds)) {
            throw ValidationException::withMessages([
                'service_ids' => 'Ada service yang tidak ditemukan.',
            ]);
        }

        $invalidServices = $services->filter(fn (Service $service) => $service->status !== 'active');

        if ($invalidServices->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_ids' => 'Semua service yang dipilih harus aktif.',
            ]);
        }

        if ($services->contains(fn (Service $service) => (int) $service->provider_id !== (int) $branch->provider_id)) {
            throw ValidationException::withMessages([
                'service_ids' => 'Semua service harus berasal dari provider branch yang sama.',
            ]);
        }

        $unavailableAtBranch = $services->filter(fn (Service $service) => ! $this->serviceBelongsToBranch($service, $branch));

        if ($unavailableAtBranch->isNotEmpty()) {
            throw ValidationException::withMessages([
                'service_ids' => 'Ada service yang belum tersedia di branch ini.',
            ]);
        }

        if ($bookingType === 'scheduled' && $services->contains(fn (Service $service) => ! $service->is_scheduled_enabled)) {
            throw ValidationException::withMessages([
                'booking_type' => 'Ada service yang belum mendukung booking jam pasti.',
            ]);
        }

        if (in_array($bookingType, ['queue', 'walk_in'], true)
            && $services->contains(fn (Service $service) => ! $service->is_queue_enabled)) {
            throw ValidationException::withMessages([
                'booking_type' => 'Ada service yang belum mendukung antrian.',
            ]);
        }

        return $services;
    }

    public function totals(Collection $services): array
    {
        return [
            'total_price' => (float) $services->sum(fn (Service $service) => (float) ($service->price ?? 0)),
            'total_duration' => (int) $services->sum(fn (Service $service) => (int) ($service->estimated_duration ?: 30)),
        ];
    }

    public function availabilityPayload(
        ProviderBranch $branch,
        Collection $services,
        ?string $bookingDate,
        ?int $staffId,
        string $bookingType
    ): array {
        $totals = $this->totals($services);
        $date = $bookingDate ?: now()->toDateString();
        $eligibleStaff = $this->eligibleStaff($branch, $services, $date);

        return [
            'eligible_staff' => $eligibleStaff->map(fn (ProviderStaff $staff) => $this->staffPayload($staff))->values(),
            'available_slots' => $bookingType === 'scheduled'
                ? $this->availableSlots($branch, $services, $date, $staffId)
                : [],
            'estimated_duration' => $totals['total_duration'],
            'total_price' => $totals['total_price'],
            'queue_estimation' => $bookingType === 'queue'
                ? $this->queueEstimation($branch, $totals['total_duration'], $staffId)
                : null,
        ];
    }

    public function availableSlots(ProviderBranch $branch, Collection $services, string $date, ?int $staffId = null): array
    {
        $duration = $this->totals($services)['total_duration'];
        $eligibleStaff = $this->eligibleStaff($branch, $services, $date, $staffId);
        $activeBookings = Booking::query()
            ->whereIn('staff_id', $eligibleStaff->pluck('id'))
            ->whereDate('booking_date', $date)
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->get()
            ->groupBy('staff_id');
        $slots = [];
        $now = now();

        foreach ($eligibleStaff as $staff) {
            $staffBookings = $activeBookings->get($staff->id, collect());

            foreach ($this->workingWindows($branch, $staff, $date) as $window) {
                $cursor = Carbon::parse($date . ' ' . $window['start']);
                $windowEnd = Carbon::parse($date . ' ' . $window['end']);

                while ($cursor->copy()->addMinutes($duration)->lte($windowEnd)) {
                    $start = $cursor->format('H:i');
                    $end = $cursor->copy()->addMinutes($duration)->format('H:i');

                    if ($cursor->gt($now) && ! $this->slotConflictsWithBookings($staffBookings, $date, $start, $duration)) {
                        $slots[] = [
                            'time' => $start,
                            'staff_id' => $staff->id,
                            'staff_name' => $staff->full_name ?: $staff->email,
                            'estimated_end_time' => $end,
                        ];
                    }

                    $cursor->addMinutes(30);
                }
            }
        }

        return collect($slots)
            ->sortBy(['time', 'staff_name'])
            ->values()
            ->all();
    }

    public function createBooking(array $payload, ?User $customer = null, bool $walkIn = false): Booking
    {
        $branch = ProviderBranch::query()
            ->with('provider.providerProfile')
            ->whereKey($payload['branch_id'] ?? null)
            ->where('status', 'active')
            ->firstOrFail();

        if (! $this->branchIsBookable($branch)) {
            throw ValidationException::withMessages([
                'branch_id' => 'Branch belum tersedia untuk booking.',
            ]);
        }

        $bookingType = $walkIn ? 'walk_in' : ($payload['booking_type'] ?? 'scheduled');
        $serviceIds = $this->normalizedServiceIds($payload);
        $services = $this->servicesForBooking($branch, $serviceIds, $bookingType);
        $totals = $this->totals($services);
        $priceSummary = $this->coupons->priceSummary($services, $payload['coupon_code'] ?? null);
        $bookingDate = $payload['booking_date'] ?? now()->toDateString();
        $startTime = $payload['start_time'] ?? $payload['booking_time'] ?? null;
        $staffId = filled($payload['staff_id'] ?? null) ? (int) $payload['staff_id'] : null;

        if ($bookingType === 'scheduled') {
            if (blank($bookingDate) || blank($startTime)) {
                throw ValidationException::withMessages([
                    'booking_date' => 'Tanggal dan jam wajib diisi untuk booking jam pasti.',
                    'start_time' => 'Tanggal dan jam wajib diisi untuk booking jam pasti.',
                ]);
            }

            if (Carbon::parse($bookingDate)->isPast() && Carbon::parse($bookingDate)->isBefore(now()->startOfDay())) {
                throw ValidationException::withMessages([
                    'booking_date' => 'Tanggal booking tidak boleh di masa lalu.',
                ]);
            }

            if (Carbon::parse($bookingDate . ' ' . $startTime)->lte(now())) {
                throw ValidationException::withMessages([
                    'start_time' => 'Jam booking sudah lewat. Pilih jam berikutnya.',
                ]);
            }

            $staff = $this->chooseStaffForScheduled($branch, $services, $bookingDate, $startTime, $staffId);
        } else {
            $bookingDate = $bookingDate ?: now()->toDateString();
            $staff = $this->chooseStaffForQueue($branch, $services, $staffId, $bookingDate);
            $startTime = null;
        }

        $paymentType = $payload['payment_type'] ?? 'pay_at_salon';
        $payment = $this->paymentPayload($paymentType, $services, $priceSummary['payable_amount']);

        return DB::transaction(function () use ($payload, $customer, $branch, $services, $totals, $priceSummary, $bookingType, $bookingDate, $startTime, $staff, $payment) {
            $queueNumber = in_array($bookingType, ['queue', 'walk_in'], true)
                ? $this->nextQueueNumber($branch, $bookingDate)
                : null;

            $status = $payment['status'] === 'pending'
                ? 'pending_payment'
                : match ($bookingType) {
                    'queue', 'walk_in' => 'waiting',
                    default => 'confirmed',
                };

            $estimatedEndTime = $startTime
                ? Carbon::parse($bookingDate . ' ' . $startTime)->addMinutes($totals['total_duration'])->format('H:i')
                : null;

            $booking = Booking::create([
                'booking_code' => $this->uniqueBookingCode(),
                'booking_date' => $bookingDate,
                'booking_time' => $startTime,
                'start_time' => $startTime,
                'estimated_end_time' => $estimatedEndTime,
                'provider_id' => $branch->provider_id,
                'customer_id' => $customer?->id,
                'service_id' => $services->first()?->id,
                'branch_id' => $branch->id,
                'staff_id' => $staff?->id,
                'booking_type' => $bookingType,
                'amount' => $priceSummary['payable_amount'],
                'total_price' => $priceSummary['payable_amount'],
                'total_duration' => $totals['total_duration'],
                'payment_status' => $payment['status'],
                'customer_name' => $payload['customer_name'] ?? $customer?->name,
                'customer_phone' => $payload['customer_phone'] ?? optional($customer?->customerProfile)->phone_number,
                'notes' => $payload['notes'] ?? null,
                'queue_number' => $queueNumber,
                'status' => $status,
            ]);

            $booking->services()->attach(
                $services->mapWithKeys(fn (Service $service) => [
                    $service->id => [
                        'price' => $service->price ?? 0,
                        'estimated_duration' => $service->estimated_duration ?: 30,
                    ],
                ])->all()
            );

            Payment::create([
                'booking_id' => $booking->id,
                'payment_type' => $payment['payment_type'],
                'amount' => $payment['amount'],
                'status' => $payment['status'],
                'payment_method' => $payment['payment_method'],
                'paid_at' => $payment['paid_at'],
            ]);

            if ($priceSummary['coupon']) {
                $priceSummary['coupon']->increment('used_count');
            }

            return $booking->refresh()->load($this->bookingRelations());
        });
    }

    public function chooseStaffForScheduled(
        ProviderBranch $branch,
        Collection $services,
        string $date,
        string $startTime,
        ?int $staffId = null
    ): ProviderStaff {
        $duration = $this->totals($services)['total_duration'];
        $eligibleStaff = $this->eligibleStaff($branch, $services, $date, $staffId);

        foreach ($eligibleStaff as $staff) {
            if ($this->staffCanTakeSlot($branch, $staff, $date, $startTime, $duration)) {
                return $staff;
            }
        }

        throw ValidationException::withMessages([
            'staff_id' => $staffId
                ? 'Staff tidak tersedia untuk service dan slot yang dipilih.'
                : 'Belum ada staff yang tersedia untuk slot ini.',
        ]);
    }

    public function chooseStaffForQueue(
        ProviderBranch $branch,
        Collection $services,
        ?int $staffId = null,
        ?string $date = null
    ): ?ProviderStaff {
        $date = $date ?: now()->toDateString();
        $eligibleStaff = $this->eligibleStaff($branch, $services, $date, $staffId);

        if ($eligibleStaff->isEmpty()) {
            throw ValidationException::withMessages([
                'staff_id' => 'Belum ada staff yang punya skill untuk semua service ini.',
            ]);
        }

        if ($staffId) {
            return $eligibleStaff->first();
        }

        return $eligibleStaff
            ->sortBy(fn (ProviderStaff $staff) => $this->activeWorkloadMinutes($staff, $date))
            ->first();
    }

    public function eligibleStaff(ProviderBranch $branch, Collection $services, ?string $date = null, ?int $staffId = null): Collection
    {
        $serviceIds = $services->pluck('id')->map(fn ($id) => (int) $id)->all();

        return ProviderStaff::query()
            ->with(['branch', 'skills:id,title', 'schedules'])
            ->where('provider_id', $branch->provider_id)
            ->where('branch_id', $branch->id)
            ->where('status', 'active')
            ->where('current_status', '!=', 'offline')
            ->when($staffId, fn ($query) => $query->whereKey($staffId))
            ->orderBy('first_name')
            ->get()
            ->filter(function (ProviderStaff $staff) use ($branch, $serviceIds, $date) {
                $skillIds = $staff->skills->pluck('id')->map(fn ($id) => (int) $id)->all();

                if (count(array_intersect($serviceIds, $skillIds)) !== count($serviceIds)) {
                    return false;
                }

                return ! $date || $this->isStaffWorking($branch, $staff, $date);
            })
            ->values();
    }

    public function queueEstimation(ProviderBranch $branch, int $requestedDuration, ?int $staffId = null): array
    {
        $date = now()->toDateString();
        $query = Booking::query()
            ->where('branch_id', $branch->id)
            ->whereDate('booking_date', $date)
            ->whereIn('booking_type', ['queue', 'walk_in'])
            ->whereIn('status', ['waiting', 'checked_in', 'in_progress', 'inprogress'])
            ->when($staffId, fn ($query) => $query->where('staff_id', $staffId));

        $waitingMinutes = (int) $query->get()->sum(fn (Booking $booking) => (int) ($booking->total_duration ?: 30));
        $waitingCount = (clone $query)->count();

        if (! $staffId) {
            $staffCount = max(1, ProviderStaff::where('branch_id', $branch->id)->where('status', 'active')->count());
            $waitingMinutes = (int) ceil($waitingMinutes / $staffCount);
        }

        $min = max(0, $waitingMinutes - 10);
        $max = max(10, $waitingMinutes + (int) ceil($requestedDuration / 2) + 10);

        return [
            'waiting_count' => $waitingCount,
            'estimated_wait_min' => $min,
            'estimated_wait_max' => $max,
            'label' => "{$min} - {$max} menit",
        ];
    }

    public function staffCanTakeSlot(ProviderBranch $branch, ProviderStaff $staff, string $date, string $startTime, int $duration): bool
    {
        $slotStart = Carbon::parse($date . ' ' . $startTime);
        $slotEnd = $slotStart->copy()->addMinutes($duration);

        if ($slotStart->lte(now())) {
            return false;
        }

        $insideWorkingWindow = collect($this->workingWindows($branch, $staff, $date))
            ->contains(function (array $window) use ($date, $slotStart, $slotEnd) {
                $windowStart = Carbon::parse($date . ' ' . $window['start']);
                $windowEnd = Carbon::parse($date . ' ' . $window['end']);

                return $slotStart->gte($windowStart) && $slotEnd->lte($windowEnd);
            });

        return $insideWorkingWindow && ! $this->hasStaffConflict($staff, $date, $startTime, $duration);
    }

    public function hasStaffConflict(ProviderStaff $staff, string $date, string $startTime, int $duration): bool
    {
        $bookings = Booking::query()
            ->where('staff_id', $staff->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', self::ACTIVE_BOOKING_STATUSES)
            ->get();

        return $this->slotConflictsWithBookings($bookings, $date, $startTime, $duration);
    }

    private function slotConflictsWithBookings(Collection $bookings, string $date, string $startTime, int $duration): bool
    {
        $requestedStart = Carbon::parse($date . ' ' . $startTime);
        $requestedEnd = $requestedStart->copy()->addMinutes($duration);

        return $bookings->contains(function (Booking $booking) use ($date, $requestedStart, $requestedEnd) {
            $bookingStartValue = $booking->start_time ?: $booking->booking_time;

            if (! $bookingStartValue) {
                return false;
            }

            $bookingStart = Carbon::parse($date . ' ' . $bookingStartValue);
            $bookingEnd = $booking->estimated_end_time
                ? Carbon::parse($date . ' ' . $booking->estimated_end_time)
                : $bookingStart->copy()->addMinutes((int) ($booking->total_duration ?: 30));

            return $requestedStart->lt($bookingEnd) && $requestedEnd->gt($bookingStart);
        });
    }

    public function bookingRelations(): array
    {
        return [
            'provider:id,name,email',
            'provider.providerProfile:user_id,status,document_status,image',
            'customer:id,name,email',
            'customer.customerProfile:user_id,phone_number',
            'branch',
            'staff',
            'service',
            'services.serviceCategory',
            'payment',
            'review',
        ];
    }

    public function completeBooking(Booking $booking): Booking
    {
        return DB::transaction(function () use ($booking) {
            $booking->update([
                'status' => 'completed',
                'actual_end_time' => now(),
                'completed_at' => now(),
            ]);

            if ($booking->staff) {
                $booking->staff->update(['current_status' => 'available']);
            }

            if ($booking->payment?->payment_type === 'pay_at_salon') {
                $booking->payment->update([
                    'amount' => $booking->total_price ?: $booking->amount,
                    'status' => 'paid',
                    'payment_method' => 'pay_at_salon',
                    'paid_at' => now(),
                ]);

                $booking->update(['payment_status' => 'paid']);
            }

            return $booking->refresh()->load($this->bookingRelations());
        });
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        return DB::transaction(function () use ($booking, $status) {
            $updates = ['status' => $status];

            if ($status === 'checked_in' && ! $booking->checked_in_at) {
                $updates['checked_in_at'] = now();
            }

            if ($status === 'in_progress') {
                $updates['actual_start_time'] = $booking->actual_start_time ?: now();

                if ($booking->staff) {
                    $booking->staff->update(['current_status' => 'busy']);
                }
            }

            if (in_array($status, ['cancelled', 'no_show'], true) && $booking->staff) {
                $booking->staff->update(['current_status' => 'available']);
            }

            $booking->update($updates);
            $booking->loadMissing('payment');

            if (in_array($status, ['cancelled', 'no_show'], true) && $booking->payment && ! in_array($booking->payment->status, ['paid', 'refunded'], true)) {
                $booking->payment->update(['status' => 'failed']);
                $booking->update(['payment_status' => 'failed']);
            }

            return $booking->refresh()->load($this->bookingRelations());
        });
    }

    private function serviceBelongsToBranch(Service $service, ProviderBranch $branch): bool
    {
        $branchIds = $service->branch_ids;

        if (empty($branchIds)) {
            return true;
        }

        return in_array((int) $branch->id, array_map('intval', (array) $branchIds), true);
    }

    private function staffPayload(ProviderStaff $staff): array
    {
        return [
            'id' => $staff->id,
            'name' => $staff->full_name ?: $staff->email,
            'first_name' => $staff->first_name,
            'last_name' => $staff->last_name,
            'rating' => $staff->rating,
            'current_status' => $staff->current_status,
            'status' => $staff->status,
            'branch_id' => $staff->branch_id,
            'skills' => $staff->skills->map(fn (Service $service) => [
                'id' => $service->id,
                'title' => $service->title,
            ])->values(),
        ];
    }

    private function isStaffWorking(ProviderBranch $branch, ProviderStaff $staff, string $date): bool
    {
        return count($this->workingWindows($branch, $staff, $date)) > 0;
    }

    private function workingWindows(ProviderBranch $branch, ProviderStaff $staff, string $date): array
    {
        if (! $this->branchWorksOnDate($branch, $date)) {
            return [];
        }

        $dayAliases = $this->dayAliases(Carbon::parse($date));
        $schedules = $staff->schedules
            ->filter(function ($schedule) use ($dayAliases) {
                return $schedule->is_available
                    && in_array(Str::lower((string) $schedule->day_of_week), $dayAliases, true);
            })
            ->values();

        if ($schedules->isEmpty()) {
            return [[
                'start' => $this->shortTime($branch->working_start_hour ?: '09:00'),
                'end' => $this->shortTime($branch->working_end_hour ?: '18:00'),
            ]];
        }

        $branchStart = $this->shortTime($branch->working_start_hour ?: '09:00');
        $branchEnd = $this->shortTime($branch->working_end_hour ?: '18:00');

        return $schedules
            ->map(function ($schedule) use ($branchStart, $branchEnd) {
                $start = max($this->shortTime($schedule->start_time), $branchStart);
                $end = min($this->shortTime($schedule->end_time), $branchEnd);

                return compact('start', 'end');
            })
            ->filter(fn (array $window) => $window['start'] < $window['end'])
            ->values()
            ->all();
    }

    private function branchWorksOnDate(ProviderBranch $branch, string $date): bool
    {
        $workingDays = collect((array) $branch->working_days)->map(fn ($day) => Str::lower((string) $day))->all();

        if (empty($workingDays)) {
            return true;
        }

        return count(array_intersect($workingDays, $this->dayAliases(Carbon::parse($date)))) > 0;
    }

    private function dayAliases(Carbon $date): array
    {
        $aliases = [
            0 => ['0', 'sunday', 'sun', 'minggu', 'ahad'],
            1 => ['1', 'monday', 'mon', 'senin'],
            2 => ['2', 'tuesday', 'tue', 'selasa'],
            3 => ['3', 'wednesday', 'wed', 'rabu'],
            4 => ['4', 'thursday', 'thu', 'kamis'],
            5 => ['5', 'friday', 'fri', 'jumat', "jum'at"],
            6 => ['6', 'saturday', 'sat', 'sabtu'],
        ];

        return $aliases[$date->dayOfWeek] ?? [];
    }

    private function shortTime(mixed $value): string
    {
        return substr((string) $value, 0, 5);
    }

    private function activeWorkloadMinutes(ProviderStaff $staff, string $date): int
    {
        return (int) Booking::query()
            ->where('staff_id', $staff->id)
            ->whereDate('booking_date', $date)
            ->whereIn('status', ['waiting', 'checked_in', 'in_progress', 'inprogress'])
            ->sum('total_duration');
    }

    private function nextQueueNumber(ProviderBranch $branch, string $date): int
    {
        return ((int) Booking::query()
            ->where('branch_id', $branch->id)
            ->whereDate('booking_date', $date)
            ->whereIn('booking_type', ['queue', 'walk_in'])
            ->max('queue_number')) + 1;
    }

    private function paymentPayload(string $paymentType, Collection $services, float $totalPrice): array
    {
        $paymentType = in_array($paymentType, ['dp', 'full_payment', 'pay_at_salon'], true)
            ? $paymentType
            : 'pay_at_salon';

        if ($paymentType === 'pay_at_salon') {
            return [
                'payment_type' => 'pay_at_salon',
                'amount' => 0,
                'status' => 'unpaid',
                'payment_method' => null,
                'paid_at' => null,
            ];
        }

        if ($paymentType === 'dp') {
            $configuredDp = (float) $services->sum(fn (Service $service) => (float) ($service->dp_amount ?? 0));
            $amount = $configuredDp > 0 ? $configuredDp : round($totalPrice * 0.3, 2);

            return [
                'payment_type' => 'dp',
                'amount' => $amount,
                'status' => 'pending',
                'payment_method' => 'midtrans',
                'paid_at' => null,
            ];
        }

        return [
            'payment_type' => 'full_payment',
            'amount' => $totalPrice,
            'status' => 'pending',
            'payment_method' => 'midtrans',
            'paid_at' => null,
        ];
    }

    private function uniqueBookingCode(): string
    {
        do {
            $code = 'BK-' . now()->format('ymd') . '-' . Str::upper(Str::random(6));
        } while (Booking::where('booking_code', $code)->exists());

        return $code;
    }
}
