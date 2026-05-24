<?php

namespace Database\Seeders;

use App\Models\ServiceCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoServiceCategorySeeder extends Seeder
{
    public function run(): void
    {
        foreach (['Hair', 'Face & Spa', 'Nail'] as $name) {
            ServiceCategory::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => 'Demo category for ' . $name,
                    'status' => 'active',
                    'is_featured' => true,
                ]
            );
        }
    }
}
