<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;
use App\Services\AccessControl;

class AppointmentPolicy
{
    public function __construct(protected AccessControl $access)
    {
    }

    public function view(User $user, Appointment $appointment): bool
    {
        if ($user->hasRole('doctor')) {
            return $user->hasPermission('appointments.view') && $appointment->doctor_id === $this->access->doctorFor($user)?->id;
        }

        // Attendants reach appointments through the check-in module only.
        return $user->hasPermission('appointments.view') || $user->hasPermission('checkin.perform');
    }

    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('appointments.cancel');
    }

    public function checkIn(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('checkin.perform');
    }

    public function consult(User $user, Appointment $appointment): bool
    {
        return $user->hasPermission('consultations.perform')
            && $appointment->doctor_id === $this->access->doctorFor($user)?->id;
    }
}
