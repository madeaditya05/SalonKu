<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AdminUserSeeder::class,
            DemoServiceCategorySeeder::class,
            DemoProviderSeeder::class,
            DemoBranchSeeder::class,
            DemoBranchAccountSeeder::class,
            DemoServiceSeeder::class,
            DemoStaffSeeder::class,
            DemoStaffSkillSeeder::class,
            DemoStaffScheduleSeeder::class,
            DemoBookingSeeder::class,
        ]);
    }
}
