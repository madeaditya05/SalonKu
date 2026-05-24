<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'booking_id',
        'payment_type',
        'amount',
        'status',
        'payment_method',
        'payment_channel',
        'midtrans_order_id',
        'midtrans_transaction_id',
        'midtrans_transaction_status',
        'fraud_status',
        'payment_code_label',
        'payment_code',
        'biller_code',
        'qr_url',
        'deeplink_url',
        'expiry_time',
        'raw_response',
        'raw_notification',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'expiry_time' => 'datetime',
            'raw_response' => 'array',
            'raw_notification' => 'array',
            'paid_at' => 'datetime',
        ];
    }

    public function booking()
    {
        return $this->belongsTo(Booking::class);
    }
}
