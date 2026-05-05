<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'title',
        'slug',
        'category',
        'sub_category',
        'code',
        'description',
        'includes',
        'price_type',
        'price',
        'slots',
        'additional_services',
        'holidays',
        'branch_ids',
        'gallery_image',
        'video_url',
        'status',
        'verify_status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'slots' => 'array',
        'additional_services' => 'array',
        'holidays' => 'array',
        'branch_ids' => 'array',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function providerDocumentStatus(): string
    {
        return optional($this->provider?->providerProfile)->document_status ?? 'pending';
    }

    public function isProviderDocumentVerified(): bool
    {
        return $this->providerDocumentStatus() === 'verified';
    }
}