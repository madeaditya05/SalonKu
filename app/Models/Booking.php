<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_code',
        'booking_date',
        'booking_time',
        'provider_id',
        'customer_id',
        'service_id',
        'branch_id',
        'staff_id',
        'booking_type',
        'start_time',
        'estimated_end_time',
        'actual_start_time',
        'actual_end_time',
        'total_duration',
        'total_price',
        'payment_status',
        'customer_name',
        'customer_phone',
        'notes',
        'queue_number',
        'checked_in_at',
        'completed_at',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'amount' => 'decimal:2',
            'total_price' => 'decimal:2',
            'total_duration' => 'integer',
            'actual_start_time' => 'datetime',
            'actual_end_time' => 'datetime',
            'checked_in_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function branch()
    {
        return $this->belongsTo(ProviderBranch::class, 'branch_id');
    }

    public function staff()
    {
        return $this->belongsTo(ProviderStaff::class, 'staff_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'booking_services')
            ->withPivot(['price', 'estimated_duration'])
            ->withTimestamps();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function review()
    {
        return $this->hasOne(Review::class);
    }
}
