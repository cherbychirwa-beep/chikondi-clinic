<?php

namespace App\Policies;

use App\Models\Patient;
use App\Models\User;
use App\Services\AccessControl;

class PatientPolicy
{
    public function __construct(protected AccessControl $access)
    {
    }

    public function view(User $user, Patient $patient): bool
    {
        return $this->access->canViewPatient($user, $patient);
    }

    public function update(User $user, Patient $patient): bool
    {
        return $user->hasPermission('patients.update');
    }

    /** Prescription records: doctor (own patients) and admin. */
    public function viewPrescriptions(User $user, Patient $patient): bool
    {
        return $user->hasPermission('prescriptions.view') && $this->access->canViewPatient($user, $patient);
    }
}
