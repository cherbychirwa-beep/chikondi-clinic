<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            StaffSeeder::class,
            FeeSeeder::class,
            MedicineSeeder::class,
            DoctorSeeder::class,
            PatientSeeder::class,
            DemoActivitySeeder::class,
        ]);
    }
}
