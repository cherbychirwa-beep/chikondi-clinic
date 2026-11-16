<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Demonstration staff (clearly fictional). Credentials are listed in README.md.
 * Demo accounts do not force a password change so they can be used immediately;
 * accounts created through the admin interface always do.
 */
class StaffSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            ['admin', 'System Administrator (DEMO)', 'Admin@12345', 'admin'],
            ['reception1', 'Grace Mwale (DEMO Receptionist)', 'Reception@123', 'receptionist'],
            ['reception2', 'Peter Banda (DEMO Receptionist)', 'Reception@123', 'receptionist'],
            ['attendant1', 'Mercy Phiri (DEMO Attendant)', 'Attendant@123', 'attendant'],
            ['attendant2', 'James Chirwa (DEMO Attendant)', 'Attendant@123', 'attendant'],
        ];

        foreach ($accounts as [$username, $name, $password, $role]) {
            User::firstOrCreate(
                ['username' => $username],
                [
                    'name' => $name,
                    'email' => $username.'@chikondicare.demo',
                    'password' => $password,
                    'role_id' => Role::where('slug', $role)->firstOrFail()->id,
                    'is_active' => true,
                    'must_change_password' => false,
                ],
            );
        }
    }
}
