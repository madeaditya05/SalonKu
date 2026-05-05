<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    ];

    public function staffs(): HasMany
    {
        return $this->hasMany(ProviderStaff::class, 'branch_id', 'id');
    }
}