<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function adminProfile()
    {
        return $this->hasOne(AdminProfile::class);
    }

    public function providerProfile()
{
    return $this->hasOne(\App\Models\ProviderProfile::class, 'user_id');
}

    public function customerProfile()
    {
        return $this->hasOne(CustomerProfile::class);
    }

    public function providerBookings()
    {
        return $this->hasMany(Booking::class, 'provider_id');
    }

    public function customerBookings()
    {
        return $this->hasMany(Booking::class, 'customer_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isProvider(): bool
    {
        return $this->role === 'provider';
    }

    public function isCustomer(): bool
    {
        return $this->role === 'customer';
    }


public function isProviderAccountActive(): bool
{
    if ($this->role !== 'provider') {
        return true;
    }

    return optional($this->providerProfile)->status === 'active';
}

public function isProviderDocumentVerified(): bool
{
    if ($this->role !== 'provider') {
        return true;
    }

    return optional($this->providerProfile)->document_status === 'verified';
}
}