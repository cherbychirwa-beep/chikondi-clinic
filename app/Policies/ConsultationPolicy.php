<?php

namespace App\Policies;

use App\Models\Consultation;
use App\Models\User;
use App\Services\AccessControl;

class ConsultationPolicy
{
    public function __construct(protected AccessControl $access)
    {
    }

    public function view(User $user, Consultation $consultation): bool
    {
        return $this->access->canViewConsultation($user, $consultation);
    }
}
