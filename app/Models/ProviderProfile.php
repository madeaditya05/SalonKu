<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'image',
        'phone_number',
        'status',
        'document_status',
        'ktp_image',
        'business_image',
        'document_note',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function getImageUrlAttribute(): ?string
    {
        return $this->image ? asset('storage/' . $this->image) : null;
    }

    public function getKtpImageUrlAttribute(): ?string
    {
        return $this->ktp_image ? asset('storage/' . $this->ktp_image) : null;
    }

    public function getBusinessImageUrlAttribute(): ?string
    {
        return $this->business_image ? asset('storage/' . $this->business_image) : null;
    }
}