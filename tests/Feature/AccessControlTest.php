<?php

namespace Tests\Feature;

use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    private const ROLES = ['admin', 'receptionist', 'attendant', 'doctor'];

    /** route name => roles that may open it (everyone else must receive 403). */
    public static function routeMatrix(): array
    {
        return [
            'dashboard' => ['dashboard', ['admin', 'receptionist', 'attendant', 'doctor']],
            'staff list' => ['staff.index', ['admin']],
            'staff create' => ['staff.create', ['admin']],
            'medicines' => ['medicines.index', ['admin']],
            'fees' => ['fees.index', ['admin']],
            'audit logs' => ['audit.index', ['admin']],
            'doctor list' => ['doctors.index', ['admin', 'receptionist']],
            'doctor create' => ['doctors.create', ['admin']],
            'patient list' => ['patients.index', ['admin', 'receptionist', 'attendant', 'doctor']],
            'patient register' => ['patients.create', ['attendant']],
            'book appointment' => ['appointments.book', ['receptionist']],
            'appointment list' => ['appointments.index', ['admin', 'receptionist', 'doctor']],
            'daily schedule' => ['appointments.schedule', ['admin', 'receptionist', 'doctor']],
            'check-in desk' => ['checkin.index', ['attendant']],
            'consultation queue' => ['consultations.queue', ['doctor']],
            'consultation list' => ['consultations.index', ['admin', 'doctor']],
            'payment records' => ['payments.index', ['admin', 'attendant']],
            'pending payments' => ['payments.pending', ['attendant']],
            'reports hub' => ['reports.index', ['admin', 'receptionist', 'attendant', 'doctor']],
            'revenue report' => ['reports.revenue', ['admin']],
        ];
    }

    #[DataProvider('routeMatrix')]
    public function test_route_is_available_only_to_authorised_roles(string $routeName, array $allowed): void
    {
        foreach (self::ROLES as $role) {
            $user = $this->staff($role);
            $response = $this->actingAs($user)->get(route($routeName));

            if (in_array($role, $allowed, true)) {
                $response->assertOk();
            } else {
                $response->assertForbidden();
            }
        }
    }

    public function test_guests_are_redirected_to_login_for_every_protected_page(): void
    {
        foreach (['dashboard', 'patients.index', 'appointments.index', 'payments.index', 'staff.index', 'audit.index', 'reports.index'] as $name) {
            $this->get(route($name))->assertRedirect(route('login'));
        }
    }

    public function test_state_changing_routes_are_also_protected(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);

        // Receptionists cannot register patients, create staff, doctors, medicines or change fees.
        $this->actingAs($receptionist)->post(route('patients.store'), [])->assertForbidden();
        $this->actingAs($receptionist)->post(route('staff.store'), [])->assertForbidden();
        $this->actingAs($receptionist)->post(route('doctors.store'), [])->assertForbidden();
        $this->actingAs($receptionist)->post(route('medicines.store'), [])->assertForbidden();
        $this->actingAs($receptionist)->post(route('fees.store'), [])->assertForbidden();
        $this->actingAs($receptionist)->put(route('patients.update', $patient), [])->assertForbidden();
    }

    public function test_receptionist_cannot_read_consultation_notes_or_diagnoses(): void
    {
        $consultation = $this->consultation();
        $receptionist = $this->staff('receptionist');

        $this->actingAs($receptionist)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->actingAs($receptionist)->get(route('consultations.index'))->assertForbidden();
        $this->actingAs($receptionist)->get(route('patients.prescriptions', $consultation->patient))->assertForbidden();

        // The patient profile is visible but never shows clinical content.
        $this->actingAs($receptionist)->get(route('patients.show', $consultation->patient))
            ->assertOk()->assertDontSee('Test diagnosis');
    }

    public function test_attendant_cannot_read_consultations_or_prescriptions(): void
    {
        $consultation = $this->consultation();
        $attendant = $this->staff('attendant');

        $this->actingAs($attendant)->get(route('consultations.show', $consultation))->assertForbidden();
        $this->actingAs($attendant)->get(route('patients.prescriptions', $consultation->patient))->assertForbidden();
        $this->actingAs($attendant)->get(route('patients.show', $consultation->patient))->assertOk()->assertDontSee('Test diagnosis');
    }

    public function test_doctor_cannot_open_another_doctors_appointment_or_consultation(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $consultationOfB = $this->consultation(15000, $doctorB);
        $appointmentOfB = $consultationOfB->appointment;

        $this->actingAs($doctorA->user)->get(route('appointments.show', $appointmentOfB))->assertForbidden();
        $this->actingAs($doctorA->user)->get(route('consultations.show', $consultationOfB))->assertForbidden();
        $this->actingAs($doctorA->user)->get(route('patients.show', $consultationOfB->patient))->assertForbidden();
        $this->actingAs($doctorA->user)->get(route('patients.prescriptions', $consultationOfB->patient))->assertForbidden();
        $this->actingAs($doctorA->user)->post(route('consultations.store', $appointmentOfB), [
            'consultation_date' => today()->toDateString(), 'diagnosis' => 'Sneaky',
        ])->assertForbidden();
    }

    public function test_doctor_lists_contain_only_their_own_records(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $mine = $this->consultation(15000, $doctorA);
        $theirs = $this->consultation(15000, $doctorB, null, '11:00:00');

        $appointments = $this->actingAs($doctorA->user)->get(route('appointments.index'))->assertOk();
        $appointments->assertSee($mine->appointment->appointment_number)
            ->assertDontSee($theirs->appointment->appointment_number);

        $patients = $this->actingAs($doctorA->user)->get(route('patients.index'))->assertOk();
        $patients->assertSee($mine->patient->patient_number)->assertDontSee($theirs->patient->patient_number);

        $consultations = $this->actingAs($doctorA->user)->get(route('consultations.index'))->assertOk();
        $consultations->assertSee($mine->patient->patient_number)->assertDontSee($theirs->patient->patient_number);
    }

    public function test_admin_can_view_clinical_records(): void
    {
        $consultation = $this->consultation();

        $this->actingAs($this->staff('admin'))->get(route('consultations.show', $consultation))->assertOk()->assertSee('Test diagnosis');
    }

    public function test_hiding_a_menu_item_is_not_the_only_guard(): void
    {
        // The attendant menu has no "Staff" entry, but typing the URL must still fail on the server.
        $this->actingAs($this->staff('attendant'))->get('/admin/staff')->assertForbidden();
        $this->actingAs($this->staff('attendant'))->get('/admin/audit-logs')->assertForbidden();
    }

    public function test_sidebar_shows_only_modules_for_the_role(): void
    {
        $this->actingAs($this->staff('receptionist'))->get(route('dashboard'))
            ->assertOk()->assertSee('Book Appointment')->assertDontSee('Audit Logs')->assertDontSee('Pending Payments');

        $this->actingAs($this->staff('admin'))->get(route('dashboard'))
            ->assertOk()->assertSee('Audit Logs')->assertDontSee('Book Appointment');
    }
}
