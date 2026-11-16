<?php

namespace Tests\Feature;

use App\Exceptions\SlotUnavailableException;
use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Services\AppointmentService;
use App\Services\AvailabilityService;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;

class AppointmentBookingTest extends TestCase
{
    protected function book($patient, $doctor, ?string $date = null, string $time = '10:00', $by = null): Appointment
    {
        return app(AppointmentService::class)->book($patient, $doctor, $date ?? $this->tomorrow(), $time, $by ?? $this->staff('receptionist'));
    }

    public function test_receptionist_can_book_an_appointment(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);

        $response = $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_date' => $this->tomorrow(), 'appointment_time' => '10:00',
        ]);

        $appointment = Appointment::firstOrFail();
        $response->assertRedirect(route('appointments.show', $appointment))->assertSessionHas('success');

        $this->assertSame(Appointment::SCHEDULED, $appointment->status);
        $this->assertSame('APT-000001', $appointment->appointment_number);
        $this->assertSame($receptionist->id, $appointment->booked_by);
        $this->assertSame('10:00', $appointment->time_short);
        $this->assertNotNull($appointment->created_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.created', 'auditable_id' => $appointment->id]);
    }

    public function test_confirmation_page_is_printable_and_shows_the_booking(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);
        $appointment = $this->book($patient, $doctor);

        $this->actingAs($this->staff('receptionist'))->get(route('appointments.show', $appointment))
            ->assertOk()->assertSee('Appointment confirmation')->assertSee($appointment->appointment_number)
            ->assertSee($patient->full_name)->assertSee('window.print()', false);
    }

    public function test_double_booking_of_the_same_doctor_and_slot_is_prevented(): void
    {
        $doctor = $this->doctor();
        $first = $this->patient($doctor);
        $second = $this->patient($doctor);

        $this->book($first, $doctor);

        $this->expectException(SlotUnavailableException::class);
        $this->book($second, $doctor);
    }

    public function test_double_booking_via_the_interface_creates_only_one_appointment(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $first = $this->patient($doctor);
        $second = $this->patient($doctor);
        $data = ['doctor_id' => $doctor->id, 'appointment_date' => $this->tomorrow(), 'appointment_time' => '09:30'];

        $this->actingAs($receptionist)->post(route('appointments.store'), $data + ['patient_id' => $first->id])->assertSessionHas('success');
        $this->actingAs($receptionist)->post(route('appointments.store'), $data + ['patient_id' => $second->id])->assertSessionHas('error');

        $this->assertSame(1, Appointment::count());
    }

    public function test_database_unique_slot_key_blocks_concurrent_double_booking(): void
    {
        $doctor = $this->doctor();
        $a = $this->patient($doctor);
        $b = $this->patient($doctor);
        $this->appointment($doctor, $a, 'scheduled', $this->tomorrow(), '10:00:00');

        $this->expectException(UniqueConstraintViolationException::class);
        $this->appointment($doctor, $b, 'scheduled', $this->tomorrow(), '10:00:00');
    }

    public function test_same_patient_cannot_hold_two_appointments_at_the_same_time(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $patient = $this->patient($doctorA);

        $this->book($patient, $doctorA);

        $this->expectException(SlotUnavailableException::class);
        $this->book($patient, $doctorB);
    }

    public function test_alternative_doctor_is_offered_and_bookable_when_registered_doctor_is_unavailable(): void
    {
        $registered = $this->doctor('full_time', false);   // no schedule => never available
        $alternative = $this->doctor();
        $patient = $this->patient($registered);

        $slots = app(AvailabilityService::class);
        $this->assertTrue($slots->availableSlots($registered, today()->addDay())->isEmpty());
        $this->assertSame([$alternative->id], $slots->alternativeDoctors(today()->addDay(), $registered->id)->pluck('doctor.id')->all());

        $receptionist = $this->staff('receptionist');
        $this->actingAs($receptionist)->get(route('appointments.book', ['patient_id' => $patient->id, 'date' => $this->tomorrow()]))
            ->assertOk()->assertSee('Alternative doctors available')->assertSee($alternative->name);

        $appointment = $this->book($patient, $alternative);
        $this->assertSame($alternative->id, $appointment->doctor_id);
        $this->assertSame($registered->id, $patient->fresh()->primary_doctor_id);
    }

    public function test_booking_page_finds_patient_by_number_or_date_of_birth(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient($doctor, ['patient_number' => 'CC-000555', 'date_of_birth' => '1988-08-08']);
        $receptionist = $this->staff('receptionist');

        $this->actingAs($receptionist)->get(route('appointments.book', ['q' => 'CC-000555']))
            ->assertRedirect(route('appointments.book', ['patient_id' => $patient->id]));
        $this->actingAs($receptionist)->get(route('appointments.book', ['q' => '1988-08-08']))
            ->assertRedirect(route('appointments.book', ['patient_id' => $patient->id]));
        $this->actingAs($receptionist)->get(route('appointments.book', ['patient_id' => $patient->id]))
            ->assertOk()->assertSee($patient->full_name)->assertSee($doctor->name);
    }

    public function test_availability_calculation_marks_booked_slots(): void
    {
        $doctor = $this->doctor();
        $this->book($this->patient($doctor), $doctor, null, '10:00');

        $slots = app(AvailabilityService::class)->slotsFor($doctor, today()->addDay());

        $this->assertCount(18, $slots); // 08:00-17:00 in 30 minute slots
        $this->assertTrue($slots->firstWhere('time', '10:00')['booked']);
        $this->assertTrue($slots->firstWhere('time', '10:30')['available']);
        $this->assertNull(app(AvailabilityService::class)->availableSlots($doctor, today()->addDay())->firstWhere('time', '10:00'));
    }

    public function test_visiting_doctor_can_only_be_booked_inside_the_visit_window(): void
    {
        $visiting = $this->doctor('visiting', true, [
            'next_visit_date' => today()->addDays(5)->toDateString(),
            'next_visit_duration_days' => 2,
        ]);
        $patient = $this->patient($visiting);

        foreach ([today()->addDays(2), today()->addDays(4), today()->addDays(7)] as $outside) {
            try {
                $this->book($patient, $visiting, $outside->toDateString());
                $this->fail('Booking outside the visit window should be rejected: '.$outside->toDateString());
            } catch (SlotUnavailableException $e) {
                $this->assertStringContainsString('not available', $e->getMessage());
            }
        }

        $this->assertSame(0, Appointment::count());

        $inside = $this->book($patient, $visiting, today()->addDays(5)->toDateString());
        $this->assertSame(Appointment::SCHEDULED, $inside->status);
        $this->book($this->patient($visiting), $visiting, today()->addDays(6)->toDateString());
        $this->assertSame(2, Appointment::count());
    }

    public function test_visiting_doctor_without_a_visit_is_not_bookable(): void
    {
        $visiting = $this->doctor('visiting', true, ['next_visit_date' => null, 'next_visit_duration_days' => null]);

        $this->expectException(SlotUnavailableException::class);
        $this->book($this->patient($visiting), $visiting);
    }

    public function test_appointments_in_the_past_are_rejected(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);

        $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'appointment_date' => today()->subDay()->toDateString(), 'appointment_time' => '10:00',
        ])->assertSessionHasErrors('appointment_date');

        $this->assertSame(0, Appointment::count());

        $this->expectException(ValidationException::class);
        $this->book($patient, $doctor, today()->subDay()->toDateString());
    }

    public function test_invalid_bookings_are_rejected(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);

        $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => 99999, 'doctor_id' => 99999, 'appointment_date' => 'not-a-date', 'appointment_time' => '25:99',
        ])->assertSessionHasErrors(['patient_id', 'doctor_id', 'appointment_date', 'appointment_time']);

        // A time that is not one of the doctor's slots.
        $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'appointment_date' => $this->tomorrow(), 'appointment_time' => '03:00',
        ])->assertSessionHas('error');

        // A deactivated doctor cannot be booked.
        $inactive = $this->doctor('full_time', true, ['is_active' => false]);
        $this->actingAs($receptionist)->post(route('appointments.store'), [
            'patient_id' => $patient->id, 'doctor_id' => $inactive->id, 'appointment_date' => $this->tomorrow(), 'appointment_time' => '10:00',
        ])->assertSessionHasErrors('doctor_id');

        $this->assertSame(0, Appointment::count());
    }

    public function test_cancellation_records_reason_and_date_and_frees_the_slot(): void
    {
        $receptionist = $this->staff('receptionist');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor);
        $appointment = $this->book($patient, $doctor);

        $this->actingAs($receptionist)->post(route('appointments.cancel', $appointment), ['reason' => 'Patient is travelling'])
            ->assertSessionHas('success');

        $appointment->refresh();
        $this->assertSame(Appointment::CANCELLED, $appointment->status);
        $this->assertNull($appointment->slot_key);
        $this->assertDatabaseHas('appointment_cancellations', [
            'appointment_id' => $appointment->id, 'reason' => 'Patient is travelling', 'cancelled_by' => $receptionist->id,
        ]);
        $this->assertNotNull($appointment->cancellation->cancelled_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'appointment.cancelled', 'auditable_id' => $appointment->id]);
    }

    public function test_cancellation_requires_a_reason(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->book($this->patient($doctor), $doctor);

        $this->actingAs($this->staff('receptionist'))->post(route('appointments.cancel', $appointment), ['reason' => ''])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Appointment::SCHEDULED, $appointment->fresh()->status);
    }

    public function test_cancelled_slot_can_be_booked_again(): void
    {
        $doctor = $this->doctor();
        $first = $this->patient($doctor);
        $second = $this->patient($doctor);
        $receptionist = $this->staff('receptionist');

        $original = $this->book($first, $doctor, null, '11:00');
        app(AppointmentService::class)->cancel($original, 'Rescheduled by patient', $receptionist);

        $slots = app(AvailabilityService::class)->availableSlots($doctor, today()->addDay());
        $this->assertNotNull($slots->firstWhere('time', '11:00'));

        $replacement = $this->book($second, $doctor, null, '11:00');

        $this->assertSame(Appointment::SCHEDULED, $replacement->status);
        $this->assertSame(2, Appointment::count());
        $this->assertSame(1, Appointment::where('status', Appointment::SCHEDULED)->count());
    }

    public function test_completed_appointments_cannot_be_cancelled(): void
    {
        $consultation = $this->consultation();
        $receptionist = $this->staff('receptionist');

        $this->actingAs($receptionist)->post(route('appointments.cancel', $consultation->appointment), ['reason' => 'Trying to cancel'])
            ->assertSessionHas('error');

        $this->assertSame(Appointment::COMPLETED, $consultation->appointment->fresh()->status);
        $this->assertDatabaseCount('appointment_cancellations', 0);
    }

    public function test_an_appointment_cannot_be_cancelled_twice(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->book($this->patient($doctor), $doctor);
        $service = app(AppointmentService::class);
        $user = $this->staff('receptionist');

        $service->cancel($appointment, 'First cancellation', $user);

        $this->expectException(ValidationException::class);
        $service->cancel($appointment, 'Second cancellation', $user);
    }

    public function test_attendant_and_doctor_cannot_book_or_cancel(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->book($this->patient($doctor), $doctor);

        foreach (['attendant', 'doctor'] as $role) {
            $user = $role === 'doctor' ? $doctor->user : $this->staff($role);
            $this->actingAs($user)->post(route('appointments.store'), [])->assertForbidden();
            $this->actingAs($user)->post(route('appointments.cancel', $appointment), ['reason' => 'Not allowed here'])->assertForbidden();
        }

        $this->assertSame(Appointment::SCHEDULED, $appointment->fresh()->status);
    }

    public function test_appointment_list_supports_filters(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $a = $this->appointment($doctorA, $this->patient($doctorA, ['full_name' => 'Filter Alpha']), 'scheduled', $this->tomorrow(), '09:00:00');
        $b = $this->appointment($doctorB, $this->patient($doctorB, ['full_name' => 'Filter Bravo']), 'cancelled', today()->addDays(2)->toDateString(), '09:00:00');
        $user = $this->staff('receptionist');

        $this->actingAs($user)->get(route('appointments.index', ['doctor_id' => $doctorA->id]))->assertSee('Filter Alpha')->assertDontSee('Filter Bravo');
        $this->actingAs($user)->get(route('appointments.index', ['status' => 'cancelled']))->assertSee('Filter Bravo')->assertDontSee('Filter Alpha');
        $this->actingAs($user)->get(route('appointments.index', ['date' => $this->tomorrow()]))->assertSee('Filter Alpha')->assertDontSee('Filter Bravo');
        $this->actingAs($user)->get(route('appointments.index', ['q' => $b->patient->patient_number]))->assertSee('Filter Bravo')->assertDontSee('Filter Alpha');
    }

    public function test_daily_schedule_view_shows_booked_and_free_slots(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient($doctor, ['full_name' => 'Schedule Visible']);
        $this->book($patient, $doctor, null, '10:00');

        $this->actingAs($this->staff('receptionist'))->get(route('appointments.schedule', ['date' => $this->tomorrow()]))
            ->assertOk()->assertSee('Schedule Visible')->assertSee($doctor->name);
    }

    public function test_no_show_command_marks_old_unattended_appointments(): void
    {
        $doctor = $this->doctor();
        $old = $this->appointment($doctor, $this->patient($doctor), 'scheduled', today()->subDays(2)->toDateString());
        $future = $this->appointment($doctor, $this->patient($doctor), 'scheduled', $this->tomorrow(), '11:00:00');

        $this->artisan('appointments:mark-no-shows')->assertSuccessful();

        $this->assertSame(Appointment::NO_SHOW, $old->fresh()->status);
        $this->assertSame(Appointment::SCHEDULED, $future->fresh()->status);
    }
}
