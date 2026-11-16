<?php

namespace App\Domain\Staff;

/** The "Doctor" staff role (named Clinician to avoid clashing with the Doctor employment hierarchy). */
class Clinician extends StaffMember
{
    public function slug(): string
    {
        return 'doctor';
    }

    public function title(): string
    {
        return 'Doctor';
    }

    public function dashboardView(): string
    {
        return 'dashboard.doctor';
    }

    public function menu(): array
    {
        return [
            $this->item('Dashboard', 'dashboard', 'bi-speedometer2', 'dashboard.view'),
            $this->item('Consultation Queue', 'consultations.queue', 'bi-journal-medical', 'consultations.perform'),
            $this->item('My Appointments', 'appointments.index', 'bi-calendar-check', 'appointments.view', 'appointments.*'),
            $this->item('My Patients', 'patients.index', 'bi-people', 'patients.view', 'patients.*'),
            $this->item('Consultations', 'consultations.index', 'bi-clipboard2-pulse', 'consultations.view', 'consultations.index'),
            $this->item('Reports', 'reports.index', 'bi-file-earmark-bar-graph', 'dashboard.view', 'reports.*'),
        ];
    }
}
