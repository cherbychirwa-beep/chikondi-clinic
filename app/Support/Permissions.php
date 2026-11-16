<?php

namespace App\Support;

/**
 * Single source of truth for permission slugs and the role => permission matrix.
 * The RolePermissionSeeder syncs this into the roles / permissions / role_permissions tables.
 */
final class Permissions
{
    public const ROLES = [
        'admin' => ['Administrator', 'System-wide management'],
        'receptionist' => ['Receptionist', 'Appointment booking and scheduling'],
        'attendant' => ['Health Care Attendant', 'Registration, check-in and payments'],
        'doctor' => ['Doctor', 'Consultations and prescriptions'],
    ];

    public const PERMISSIONS = [
        'dashboard.view' => ['View dashboard', 'General'],
        'patients.view' => ['View patients', 'Patients'],
        'patients.register' => ['Register patients', 'Patients'],
        'patients.update' => ['Update patients', 'Patients'],
        'doctors.view' => ['View doctors and availability', 'Doctors'],
        'doctors.manage' => ['Manage doctors', 'Doctors'],
        'staff.manage' => ['Manage staff accounts', 'Administration'],
        'medicines.manage' => ['Manage medicine catalogue', 'Administration'],
        'fees.manage' => ['Configure consultation fees', 'Administration'],
        'audit.view' => ['View audit logs', 'Administration'],
        'appointments.view' => ['View appointments', 'Appointments'],
        'appointments.book' => ['Book appointments', 'Appointments'],
        'appointments.cancel' => ['Cancel appointments', 'Appointments'],
        'checkin.perform' => ['Check patients in', 'Check-in'],
        'consultations.perform' => ['Conduct consultations', 'Consultations'],
        'consultations.view' => ['View consultation records', 'Consultations'],
        'prescriptions.view' => ['View prescriptions', 'Consultations'],
        'payments.record' => ['Record payments', 'Payments'],
        'payments.view' => ['View payments and receipts', 'Payments'],
        'reports.daily' => ['Daily appointment list report', 'Reports'],
        'reports.doctor_patients' => ['Patient list per doctor report', 'Reports'],
        'reports.revenue' => ['Revenue and attendance report', 'Reports'],
        'reports.visiting' => ['Visiting doctor schedule report', 'Reports'],
    ];

    public static function matrix(): array
    {
        return [
            'admin' => [
                'dashboard.view', 'patients.view', 'doctors.view', 'doctors.manage', 'staff.manage',
                'medicines.manage', 'fees.manage', 'audit.view', 'appointments.view',
                'consultations.view', 'prescriptions.view', 'payments.view',
                'reports.daily', 'reports.doctor_patients', 'reports.revenue', 'reports.visiting',
            ],
            'receptionist' => [
                'dashboard.view', 'patients.view', 'doctors.view', 'appointments.view',
                'appointments.book', 'appointments.cancel', 'reports.daily', 'reports.visiting',
            ],
            'attendant' => [
                'dashboard.view', 'patients.view', 'patients.register', 'patients.update',
                'checkin.perform', 'payments.record', 'payments.view',
            ],
            'doctor' => [
                'dashboard.view', 'patients.view', 'appointments.view', 'consultations.perform',
                'consultations.view', 'prescriptions.view', 'reports.daily', 'reports.doctor_patients',
            ],
        ];
    }

    public static function all(): array
    {
        return array_keys(self::PERMISSIONS);
    }
}
