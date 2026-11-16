<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Doctor;
use App\Models\DoctorSchedule;
use App\Models\Role;
use App\Models\User;

class DoctorService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected NumberGenerator $numbers,
        protected AuditService $audit,
    ) {
    }

    protected function assertVisitFields(array $data): void
    {
        if (($data['employment_type'] ?? null) === Doctor::VISITING
            && (empty($data['next_visit_date']) || empty($data['next_visit_duration_days']))) {
            throw new ValidationException('Visiting doctors require a next visit date and duration.', [
                'next_visit_date' => 'Required for visiting doctors.',
                'next_visit_duration_days' => 'Required for visiting doctors.',
            ]);
        }
    }

    /** Creates the doctor's login account and doctor profile together. */
    public function register(array $data, User $by): Doctor
    {
        $this->assertVisitFields($data);

        return $this->db->transaction(function () use ($data, $by) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'email' => $data['email'] ?? null,
                'password' => $data['password'],
                'role_id' => Role::where('slug', 'doctor')->firstOrFail()->id,
                'is_active' => true,
                'must_change_password' => true,
            ]);

            $visiting = $data['employment_type'] === Doctor::VISITING;

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'doctor_number' => ($data['doctor_number'] ?? null) ?: $this->numbers->next('doctor', 'DR-', 3),
                'phone' => $data['phone'],
                'consulting_room' => $data['consulting_room'],
                'employment_type' => $data['employment_type'],
                'next_visit_date' => $visiting ? $data['next_visit_date'] : null,
                'next_visit_duration_days' => $visiting ? $data['next_visit_duration_days'] : null,
                'is_active' => true,
            ]);

            $this->audit->log('doctor.created', "Registered {$doctor->employment_type} doctor {$doctor->doctor_number} ({$user->name})", $doctor, $by);

            return $doctor;
        });
    }

    public function update(Doctor $doctor, array $data, User $by): Doctor
    {
        $this->assertVisitFields($data);

        return $this->db->transaction(function () use ($doctor, $data, $by) {
            $doctor->user->update(['name' => $data['name'], 'email' => $data['email'] ?? null]);

            $visiting = $data['employment_type'] === Doctor::VISITING;
            $doctor->update([
                'doctor_number' => $data['doctor_number'],
                'phone' => $data['phone'],
                'consulting_room' => $data['consulting_room'],
                'employment_type' => $data['employment_type'],
                'next_visit_date' => $visiting ? $data['next_visit_date'] : null,
                'next_visit_duration_days' => $visiting ? $data['next_visit_duration_days'] : null,
            ]);

            $this->audit->log('doctor.updated', "Updated doctor {$doctor->doctor_number}", $doctor, $by);

            return $doctor->refresh();
        });
    }

    /** Deactivation (never deletion) keeps all historical records intact. */
    public function setActive(Doctor $doctor, bool $active, User $by): Doctor
    {
        return $this->db->transaction(function () use ($doctor, $active, $by) {
            $doctor->update(['is_active' => $active]);
            $doctor->user->update(['is_active' => $active]);

            $this->audit->log($active ? 'doctor.activated' : 'doctor.deactivated',
                ($active ? 'Activated' : 'Deactivated')." doctor {$doctor->doctor_number}", $doctor, $by);

            return $doctor;
        });
    }

    /** @throws ValidationException */
    public function addSchedule(Doctor $doctor, array $data, User $by): DoctorSchedule
    {
        $start = substr($data['start_time'], 0, 5);
        $end = substr($data['end_time'], 0, 5);

        if ($end <= $start) {
            throw new ValidationException('End time must be after start time.', ['end_time' => 'End time must be after start time.']);
        }

        $overlap = $doctor->schedules()
            ->where('day_of_week', $data['day_of_week'])
            ->get()
            ->contains(fn (DoctorSchedule $s) => $s->start_short < $end && $s->end_short > $start);

        if ($overlap) {
            throw new ValidationException('This time range overlaps an existing schedule on the same day.', ['start_time' => 'Overlaps an existing schedule.']);
        }

        $schedule = $doctor->schedules()->create([
            'day_of_week' => $data['day_of_week'],
            'start_time' => $start.':00',
            'end_time' => $end.':00',
            'slot_minutes' => $data['slot_minutes'],
            'is_active' => true,
        ]);

        $this->audit->log('doctor.schedule_changed', "Added schedule {$schedule->day_name} {$start}-{$end} for {$doctor->doctor_number}", $doctor, $by);

        return $schedule;
    }

    public function removeSchedule(DoctorSchedule $schedule, User $by): void
    {
        $doctor = $schedule->doctor;
        $label = "{$schedule->day_name} {$schedule->start_short}-{$schedule->end_short}";
        $schedule->delete();

        $this->audit->log('doctor.schedule_changed', "Removed schedule {$label} for {$doctor->doctor_number}", $doctor, $by);
    }
}
