<?php

namespace Tests\Feature;

use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Services\AppointmentService;
use Tests\TestCase;

class CheckInTest extends TestCase
{
    public function test_attendant_can_check_in_a_patient_for_todays_appointment(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor));

        $this->actingAs($attendant)->post(route('checkin.store', $appointment))->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(Appointment::CHECKED_IN, $appointment->status);
        $this->assertNotNull($appointment->checked_in_at);
        $this->assertTrue($appointment->checked_in_at->isToday());
        $this->assertSame($attendant->id, $appointment->checked_in_by);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.checked_in', 'auditable_id' => $appointment->id]);
    }

    public function test_check_in_desk_lists_todays_appointments_and_finds_by_patient_number(): void
    {
        $doctor = $this->doctor();
        $mine = $this->appointment($doctor, $this->patient($doctor, ['patient_number' => 'CC-000321', 'full_name' => 'Desk Person']), 'scheduled', null, '09:00:00');
        $other = $this->appointment($doctor, $this->patient($doctor, ['patient_number' => 'CC-000322', 'full_name' => 'Other Person']), 'scheduled', null, '09:30:00');
        $tomorrow = $this->appointment($doctor, $this->patient($doctor, ['full_name' => 'Tomorrow Person']), 'scheduled', $this->tomorrow(), '09:00:00');
        $attendant = $this->staff('attendant');

        $this->actingAs($attendant)->get(route('checkin.index'))
            ->assertOk()->assertSee('Desk Person')->assertSee('Other Person')->assertDontSee('Tomorrow Person');

        $this->actingAs($attendant)->get(route('checkin.index', ['q' => 'CC-000321']))
            ->assertSee('Desk Person')->assertDontSee('Other Person');
    }

    public function test_unknown_patient_number_is_reported_as_not_found(): void
    {
        $this->actingAs($this->staff('attendant'))->get(route('checkin.index', ['q' => 'CC-999999']))
            ->assertOk()->assertSee('No appointment found today');
    }

    public function test_non_existent_appointment_is_rejected(): void
    {
        $this->actingAs($this->staff('attendant'))->post(route('checkin.store', 987654))->assertNotFound();
    }

    public function test_cancelled_appointment_cannot_be_checked_in(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor), 'cancelled');

        $this->actingAs($this->staff('attendant'))->post(route('checkin.store', $appointment))->assertSessionHas('error');

        $this->assertSame(Appointment::CANCELLED, $appointment->fresh()->status);
        $this->assertNull($appointment->fresh()->checked_in_at);
    }

    public function test_duplicate_check_in_is_prevented(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor));
        $service = app(AppointmentService::class);

        $service->checkIn($appointment, $attendant);
        $firstTime = $appointment->fresh()->checked_in_at;

        try {
            $service->checkIn($appointment, $attendant);
            $this->fail('A second check-in should be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already been checked in', $e->getMessage());
        }

        $this->assertEquals($firstTime, $appointment->fresh()->checked_in_at);
        $this->assertSame(1, \App\Models\AuditLog::where('action', 'appointment.checked_in')->count());
    }

    public function test_appointments_for_other_days_cannot_be_checked_in_today(): void
    {
        $doctor = $this->doctor();
        $future = $this->appointment($doctor, $this->patient($doctor), 'scheduled', $this->tomorrow());

        $this->actingAs($this->staff('attendant'))->post(route('checkin.store', $future))->assertSessionHas('error');

        $this->assertSame(Appointment::SCHEDULED, $future->fresh()->status);
    }

    public function test_completed_and_no_show_appointments_cannot_be_checked_in(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();
        $service = app(AppointmentService::class);

        foreach (['completed' => '09:00:00', 'no_show' => '09:30:00'] as $status => $time) {
            $appointment = $this->appointment($doctor, $this->patient($doctor), $status, null, $time);

            try {
                $service->checkIn($appointment, $attendant);
                $this->fail("A {$status} appointment must not be checkable.");
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->getMessage());
            }

            $this->assertSame($status, $appointment->fresh()->status);
            $this->assertNull($appointment->fresh()->checked_in_at);
        }
    }

    public function test_only_attendants_can_check_patients_in(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor));

        foreach (['receptionist', 'admin'] as $role) {
            $this->actingAs($this->staff($role))->post(route('checkin.store', $appointment))->assertForbidden();
        }
        $this->actingAs($doctor->user)->post(route('checkin.store', $appointment))->assertForbidden();

        $this->assertSame(Appointment::SCHEDULED, $appointment->fresh()->status);
    }

    public function test_scheduled_appointment_can_be_marked_no_show_by_attendant_only_when_not_in_the_future(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();
        $today = $this->appointment($doctor, $this->patient($doctor), 'scheduled', null, '09:00:00');
        $future = $this->appointment($doctor, $this->patient($doctor), 'scheduled', $this->tomorrow(), '09:00:00');

        $this->actingAs($attendant)->post(route('checkin.noshow', $today))->assertSessionHas('success');
        $this->actingAs($attendant)->post(route('checkin.noshow', $future))->assertSessionHas('error');

        $this->assertSame(Appointment::NO_SHOW, $today->fresh()->status);
        $this->assertSame(Appointment::SCHEDULED, $future->fresh()->status);
    }
}
