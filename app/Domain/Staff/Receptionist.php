<?php

namespace App\Domain\Staff;

class Receptionist extends StaffMember
{
    public function slug(): string
    {
        return 'receptionist';
    }

    public function title(): string
    {
        return 'Receptionist';
    }

    public function dashboardView(): string
    {
        return 'dashboard.receptionist';
    }

    public function menu(): array
    {
        return [
            $this->item('Dashboard', 'dashboard', 'bi-speedometer2', 'dashboard.view'),
            $this->item('Book Appointment', 'appointments.book', 'bi-calendar-plus', 'appointments.book'),
            $this->item('Appointments', 'appointments.index', 'bi-calendar-check', 'appointments.view', 'appointments.index'),
            $this->item('Daily Schedule', 'appointments.schedule', 'bi-grid-3x3-gap', 'appointments.view'),
            $this->item('Patients', 'patients.index', 'bi-people', 'patients.view', 'patients.*'),
            $this->item('Doctors', 'doctors.index', 'bi-person-badge', 'doctors.view', 'doctors.*'),
            $this->item('Reports', 'reports.index', 'bi-file-earmark-bar-graph', 'dashboard.view', 'reports.*'),
        ];
    }
}
