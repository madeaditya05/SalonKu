<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class ProviderBranch extends Model
{
    protected $table = 'provider_branches';

    protected $fillable = [
        'provider_id',
        'branch_name',
        'email',
        'phone_code',
        'phone_number',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'latitude',
        'longitude',
        'zip_code',
        'working_start_hour',
        'working_end_hour',
        'working_days',
        'holidays',
        'image',
        'status',
    ];

    protected $casts = [
        'working_days' => 'array',
        'holidays' => 'array',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    protected $appends = [
        'image_url',
    ];

    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image) {
            return null;
        }

        if (str_starts_with($this->image, 'http://') || str_starts_with($this->image, 'https://')) {
            return $this->image;
        }

        $path = str_starts_with($this->image, 'storage/')
            ? $this->image
            : 'storage/' . ltrim($this->image, '/');

        return asset($path);
    }

    public function staffs(): HasMany
    {
        return $this->hasMany(ProviderStaff::class, 'branch_id', 'id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'branch_id');
    }

    public function servicesForBranch(): Collection
    {
        return Service::query()
            ->where('provider_id', $this->provider_id)
            ->where('status', 'active')
            ->latest()
            ->get()
            ->filter(function (Service $service) {
                $branchIds = $service->branch_ids;

                if (empty($branchIds)) {
                    return true;
                }

                return in_array((int) $this->id, array_map('intval', (array) $branchIds), true);
            })
            ->values();
    }
}
