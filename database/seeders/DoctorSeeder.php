<?php

namespace Database\Seeders;

use App\Models\Doctor;
use App\Models\Role;
use App\Models\User;
use App\Services\NumberGenerator;
use Illuminate\Database\Seeder;

/** Two full-time and two visiting demonstration doctors, each with a weekly schedule. */
class DoctorSeeder extends Seeder
{
    public function run(NumberGenerator $numbers): void
    {
        $roleId = Role::where('slug', 'doctor')->firstOrFail()->id;

        $doctors = [
            // username, name, room, type, schedule [days => [start, end, slot]]
            ['drtembo', 'Dr. Chikondi Tembo (DEMO)', '1', Doctor::FULL_TIME, [1, 2, 3, 4, 5, 6], ['08:00', '16:00', 30]],
            ['drkamanga', 'Dr. Linda Kamanga (DEMO)', '2', Doctor::FULL_TIME, [0, 1, 2, 3, 4, 5], ['09:00', '17:00', 30]],
            ['drnyirenda', 'Dr. Samuel Nyirenda (DEMO)', '3', Doctor::VISITING, [1, 2, 3, 4, 5, 6], ['09:00', '15:00', 30]],
            ['drmsiska', 'Dr. Ruth Msiska (DEMO)', '4', Doctor::VISITING, [0, 1, 2, 3, 4, 5, 6], ['10:00', '14:00', 20]],
        ];

        $n = 0;
        foreach ($doctors as [$username, $name, $room, $type, $days, [$start, $end, $slot]]) {
            if (User::where('username', $username)->exists()) {
                continue;
            }

            $user = User::create([
                'name' => $name,
                'username' => $username,
                'email' => $username.'@chikondicare.demo',
                'password' => 'Doctor@12345',
                'role_id' => $roleId,
                'is_active' => true,
                'must_change_password' => false,
            ]);

            $visiting = $type === Doctor::VISITING;

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'doctor_number' => $numbers->next('doctor', 'DR-', 3),
                'phone' => '+265 999 100 10'.(++$n),
                'consulting_room' => $room,
                'employment_type' => $type,
                // Visit windows start in the near future so they are bookable during a demo.
                'next_visit_date' => $visiting ? today()->addDays($n === 3 ? 1 : 2)->toDateString() : null,
                'next_visit_duration_days' => $visiting ? ($n === 3 ? 5 : 4) : null,
                'is_active' => true,
            ]);

            foreach ($days as $day) {
                $doctor->schedules()->create([
                    'day_of_week' => $day,
                    'start_time' => $start.':00',
                    'end_time' => $end.':00',
                    'slot_minutes' => $slot,
                    'is_active' => true,
                ]);
            }
        }
    }
}
