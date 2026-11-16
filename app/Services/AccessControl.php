<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Reusable server-side authorisation and data-scoping rules.
 * Every query that returns patient / clinical data for a given user goes through here.
 */
class AccessControl
{
    public function can(User $user, string $permission): bool
    {
        return $user->hasPermission($permission);
    }

    /** @throws AuthorizationException */
    public function assert(User $user, string $permission): void
    {
        if (! $this->can($user, $permission)) {
            throw new AuthorizationException;
        }
    }

    public function doctorFor(User $user): ?Doctor
    {
        return $user->hasRole('doctor') ? $user->doctor : null;
    }

    /** Appointments visible to the user (doctors only see their own). */
    public function appointmentsQuery(User $user): Builder
    {
        $query = Appointment::query();

        if ($user->hasRole('doctor')) {
            $query->where('appointments.doctor_id', $this->doctorFor($user)?->id ?? 0);
        }

        return $query;
    }

    /** Patients visible to the user (doctors only see patients booked with them). */
    public function patientsQuery(User $user): Builder
    {
        $query = Patient::query();

        if ($user->hasRole('doctor')) {
            $doctorId = $this->doctorFor($user)?->id ?? 0;
            // Own scope: patients registered to the doctor OR booked with them.
            $query->where(function (Builder $w) use ($doctorId) {
                $w->where('patients.primary_doctor_id', $doctorId)
                    ->orWhereHas('appointments', function (Builder $a) use ($doctorId) {
                        $a->where('doctor_id', $doctorId)->where('status', '!=', Appointment::CANCELLED);
                    });
            });
        }

        return $query;
    }

    public function canViewPatient(User $user, Patient $patient): bool
    {
        if (! $this->can($user, 'patients.view')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $this->patientsQuery($user)->whereKey($patient->id)->exists();
        }

        return true;
    }

    /** Consultations visible to the user (doctors only see ones they recorded). */
    public function consultationsQuery(User $user): Builder
    {
        $query = Consultation::query();

        if ($user->hasRole('doctor')) {
            $query->where('consultations.doctor_id', $this->doctorFor($user)?->id ?? 0);
        }

        return $query;
    }

    public function canViewConsultation(User $user, Consultation $consultation): bool
    {
        if (! $this->can($user, 'consultations.view')) {
            return false;
        }

        if ($user->hasRole('doctor')) {
            return $consultation->doctor_id === $this->doctorFor($user)?->id;
        }

        return true;
    }
}
