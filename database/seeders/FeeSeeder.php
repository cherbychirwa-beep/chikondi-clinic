<?php

namespace Database\Seeders;

use App\Models\FeeSetting;
use App\Models\User;
use Illuminate\Database\Seeder;

/** Demonstration fee history: an older fee and the current one (historical consultations keep their snapshot). */
class FeeSeeder extends Seeder
{
    public function run(): void
    {
        if (FeeSetting::exists()) {
            return;
        }

        $admin = User::where('username', 'admin')->firstOrFail(); // created by StaffSeeder

        FeeSetting::create(['amount' => 12000, 'effective_from' => today()->subDays(90), 'note' => 'Initial fee (demo)', 'created_by' => $admin->id]);
        FeeSetting::create(['amount' => 15000, 'effective_from' => today()->subDays(10), 'note' => 'Revised fee (demo)', 'created_by' => $admin->id]);
    }
}
