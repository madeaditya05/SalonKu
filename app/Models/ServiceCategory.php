<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'image',
        'icon',
        'description',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function subCategories()
{
    return $this->hasMany(ServiceSubCategory::class, 'service_category_id');
}
}