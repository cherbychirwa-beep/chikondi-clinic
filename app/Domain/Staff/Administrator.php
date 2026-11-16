<?php

namespace App\Domain\Staff;

class Administrator extends StaffMember
{
    public function slug(): string
    {
        return 'admin';
    }

    public function title(): string
    {
        return 'Administrator';
    }

    public function dashboardView(): string
    {
        return 'dashboard.admin';
    }

    public function menu(): array
    {
        return [
            $this->item('Dashboard', 'dashboard', 'bi-speedometer2', 'dashboard.view'),
            $this->item('Patients', 'patients.index', 'bi-people', 'patients.view', 'patients.*'),
            $this->item('Appointments', 'appointments.index', 'bi-calendar-check', 'appointments.view', 'appointments.*'),
            $this->item('Doctors', 'doctors.index', 'bi-person-badge', 'doctors.manage', 'doctors.*'),
            $this->item('Staff', 'staff.index', 'bi-person-gear', 'staff.manage', 'staff.*'),
            $this->item('Medicines', 'medicines.index', 'bi-capsule', 'medicines.manage', 'medicines.*'),
            $this->item('Consultation Fee', 'fees.index', 'bi-cash-coin', 'fees.manage', 'fees.*'),
            $this->item('Payments', 'payments.index', 'bi-receipt', 'payments.view', 'payments.*'),
            $this->item('Reports', 'reports.index', 'bi-file-earmark-bar-graph', 'dashboard.view', 'reports.*'),
            $this->item('Audit Logs', 'audit.index', 'bi-shield-check', 'audit.view', 'audit.*'),
        ];
    }
}
