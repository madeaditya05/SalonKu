<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffSkill extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'service_id',
    ];

    public function staff()
    {
        return $this->belongsTo(ProviderStaff::class, 'staff_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}
