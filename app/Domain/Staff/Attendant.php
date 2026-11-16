<?php

namespace App\Domain\Staff;

class Attendant extends StaffMember
{
    public function slug(): string
    {
        return 'attendant';
    }

    public function title(): string
    {
        return 'Health Care Attendant';
    }

    public function dashboardView(): string
    {
        return 'dashboard.attendant';
    }

    public function menu(): array
    {
        return [
            $this->item('Dashboard', 'dashboard', 'bi-speedometer2', 'dashboard.view'),
            $this->item('Register Patient', 'patients.create', 'bi-person-plus', 'patients.register'),
            $this->item('Patients', 'patients.index', 'bi-people', 'patients.view', 'patients.index'),
            $this->item('Check-in', 'checkin.index', 'bi-clipboard-check', 'checkin.perform', 'checkin.*'),
            $this->item('Pending Payments', 'payments.pending', 'bi-hourglass-split', 'payments.record'),
            $this->item('Payment Records', 'payments.index', 'bi-receipt', 'payments.view', 'payments.index'),
        ];
    }
}
