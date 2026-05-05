<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderStaff extends Model
{
    use HasFactory;

    protected $table = 'provider_staffs';

    protected $fillable = [
        'provider_id',
        'image',
        'first_name',
        'last_name',
        'email',
        'username',
        'country_code',
        'phone_number',
        'gender',
        'date_of_birth',
        'address',
        'country_id',
        'state_id',
        'city_id',
        'postal_code',
        'bio',
        'category_id',
        'sub_category_id',
        'branch_id',
        'role',
        'status',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    protected $appends = [
        'full_name',
    ];

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_id');
    }

    public function branch()
    {
        return $this->belongsTo(ProviderBranch::class, 'branch_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function subCategory()
    {
        return $this->belongsTo(ServiceSubCategory::class, 'sub_category_id');
    }
}