<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class CalendarController extends Controller
{
    public function index(Request $request)
    {
        if (!Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses ditolak.');
        }

        $view = $request->get('view', 'month');
        $dateParam = $request->get('date');

        $baseDate = $dateParam
            ? Carbon::parse($dateParam)->startOfDay()
            : now()->startOfDay();

        // Navigation date
        $prevDate = $baseDate->copy();
        $nextDate = $baseDate->copy();

        if ($view === 'month') {
            $prevDate->subMonth();
            $nextDate->addMonth();
        } elseif ($view === 'week') {
            $prevDate->subWeek();
            $nextDate->addWeek();
        } else {
            $prevDate->subDay();
            $nextDate->addDay();
        }

        // Booking query range
        $rangeStart = $baseDate->copy();
        $rangeEnd = $baseDate->copy();

        if ($view === 'month') {
            $rangeStart = $baseDate->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
            $rangeEnd = $baseDate->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);
        } elseif ($view === 'week') {
            $rangeStart = $baseDate->copy()->startOfWeek(Carbon::SUNDAY);
            $rangeEnd = $baseDate->copy()->endOfWeek(Carbon::SATURDAY);
        }

        $bookings = Booking::with(['provider', 'customer', 'service'])
            ->whereBetween('booking_date', [
                $rangeStart->format('Y-m-d'),
                $rangeEnd->format('Y-m-d'),
            ])
            ->get();

        $eventsByDate = $bookings->groupBy(function ($booking) {
            return Carbon::parse($booking->booking_date)->format('Y-m-d');
        });

        // Month data
        $monthWeeks = [];
        if ($view === 'month') {
            $monthStart = $baseDate->copy()->startOfMonth()->startOfWeek(Carbon::SUNDAY);
            $monthEnd = $baseDate->copy()->endOfMonth()->endOfWeek(Carbon::SATURDAY);

            $cursor = $monthStart->copy();
            $week = [];

            while ($cursor->lte($monthEnd)) {
                $week[] = [
                    'date' => $cursor->copy(),
                    'day' => $cursor->day,
                    'is_current_month' => $cursor->month === $baseDate->month,
                    'is_today' => $cursor->isToday(),
                ];

                if (count($week) === 7) {
                    $monthWeeks[] = $week;
                    $week = [];
                }

                $cursor->addDay();
            }
        }

        // Week data
        $weekDays = [];
        if ($view === 'week') {
            $weekStart = $baseDate->copy()->startOfWeek(Carbon::SUNDAY);
            for ($i = 0; $i < 7; $i++) {
                $day = $weekStart->copy()->addDays($i);
                $weekDays[] = [
                    'date' => $day,
                    'label' => $day->format('D'),
                    'label_full' => $day->format('D n/j'),
                    'is_today' => $day->isToday(),
                ];
            }
        }

        // Day data
        $dayData = null;
        if ($view === 'day') {
            $dayData = [
                'date' => $baseDate->copy(),
                'label' => $baseDate->format('l'),
                'is_today' => $baseDate->isToday(),
            ];
        }

        // time labels 12am - 11pm
        $timeSlots = [];
        for ($hour = 0; $hour < 24; $hour++) {
            $timeSlots[] = Carbon::createFromTime($hour, 0)->format('ga');
        }

        // Title
        if ($view === 'month') {
            $calendarTitle = strtoupper($baseDate->format('F Y'));
        } elseif ($view === 'week') {
            $weekStart = $baseDate->copy()->startOfWeek(Carbon::SUNDAY);
            $weekEnd = $baseDate->copy()->endOfWeek(Carbon::SATURDAY);

            if ($weekStart->month === $weekEnd->month && $weekStart->year === $weekEnd->year) {
                $calendarTitle = strtoupper(
                    $weekStart->format('M j') . ' – ' . $weekEnd->format('j, Y')
                );
            } else {
                $calendarTitle = strtoupper(
                    $weekStart->format('M j') . ' – ' . $weekEnd->format('M j, Y')
                );
            }
        } else {
            $calendarTitle = strtoupper($baseDate->format('F j, Y'));
        }

        return view('admin.calendar.index', compact(
            'view',
            'baseDate',
            'prevDate',
            'nextDate',
            'calendarTitle',
            'eventsByDate',
            'monthWeeks',
            'weekDays',
            'dayData',
            'timeSlots'
        ));
    }
}