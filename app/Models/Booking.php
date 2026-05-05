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
        'provider_id',
        'customer_id',
        'service_id',
        'amount',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'booking_date' => 'date',
            'amount' => 'decimal:2',
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
}