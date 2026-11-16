<?php

namespace Tests\Feature;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Medicine;
use App\Models\Prescription;
use App\Services\ConsultationService;
use App\Services\FeeService;
use Tests\TestCase;

class ConsultationTest extends TestCase
{
    protected function checkedIn($doctor, $patient = null, string $time = '09:00:00'): Appointment
    {
        $patient ??= $this->patient($doctor);

        return $this->appointment($doctor, $patient, Appointment::CHECKED_IN, null, $time);
    }

    protected function rx(Medicine $medicine, array $overrides = []): array
    {
        return array_merge([
            'medicine_id' => $medicine->id, 'dosage' => '1 tablet', 'frequency' => 'Twice daily', 'duration' => '5 days', 'instructions' => 'After meals',
        ], $overrides);
    }

    public function test_doctor_saves_diagnosis_notes_and_multiple_prescriptions(): void
    {
        $this->fee(15000);
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);
        [$m1, $m2, $m3] = Medicine::factory()->count(3)->create()->all();

        $response = $this->actingAs($doctor->user)->post(route('consultations.store', $appointment), [
            'consultation_date' => today()->toDateString(),
            'diagnosis' => 'Uncomplicated malaria',
            'observations' => 'Temperature 38.5C',
            'notes' => 'Advised rest and fluids',
            'prescriptions' => [$this->rx($m1), $this->rx($m2, ['dosage' => '2 tablets']), $this->rx($m3, ['instructions' => null])],
        ]);

        $consultation = Consultation::firstOrFail();
        $response->assertRedirect(route('consultations.show', $consultation))->assertSessionHas('success');

        $this->assertSame('Uncomplicated malaria', $consultation->diagnosis);
        $this->assertSame('Temperature 38.5C', $consultation->observations);
        $this->assertSame('Advised rest and fluids', $consultation->notes);
        $this->assertSame($appointment->patient_id, $consultation->patient_id);
        $this->assertSame($doctor->id, $consultation->doctor_id);
        $this->assertSame($appointment->id, $consultation->appointment_id);
        $this->assertCount(3, $consultation->prescriptions);
        $this->assertSame(Appointment::COMPLETED, $appointment->fresh()->status);
        $this->assertSame('2 tablets', Prescription::where('medicine_id', $m2->id)->value('dosage'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'consultation.created', 'auditable_id' => $consultation->id]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'prescription.created', 'auditable_id' => $consultation->id]);
    }

    public function test_a_consultation_may_have_zero_prescriptions(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);

        $this->actingAs($doctor->user)->post(route('consultations.store', $appointment), [
            'consultation_date' => today()->toDateString(), 'diagnosis' => 'Mild headache',
        ])->assertSessionHasNoErrors();

        $this->assertSame(0, Consultation::firstOrFail()->prescriptions()->count());
        $this->assertSame(Appointment::COMPLETED, $appointment->fresh()->status);
    }

    public function test_consultation_snapshots_the_fee_in_force(): void
    {
        $this->fee(12000);
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);

        $consultation = app(ConsultationService::class)->record($appointment, ['diagnosis' => 'Flu'], [], $doctor->user);
        $this->assertEquals(12000, $consultation->fee_charged);

        // A later fee change never alters the historical record.
        app(FeeService::class)->set(20000, today()->toDateString(), 'Increase', $this->staff('admin'));
        $this->assertEquals(12000, $consultation->fresh()->fee_charged);
        $this->assertEquals(20000, app(FeeService::class)->currentAmount());
    }

    public function test_duplicate_consultation_for_the_same_appointment_is_prevented(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);
        $service = app(ConsultationService::class);

        $service->record($appointment, ['diagnosis' => 'First'], [], $doctor->user);

        try {
            $service->record($appointment, ['diagnosis' => 'Second'], [], $doctor->user);
            $this->fail('A second consultation for the same appointment must be rejected.');
        } catch (ValidationException $e) {
            $this->assertStringContainsString('already been recorded', $e->getMessage());
        }

        $this->assertSame(1, Consultation::count());
    }

    public function test_consultation_requires_a_checked_in_patient(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor)); // still scheduled

        $this->actingAs($doctor->user)->post(route('consultations.store', $appointment), [
            'consultation_date' => today()->toDateString(), 'diagnosis' => 'Too early',
        ])->assertSessionHas('error');

        $this->assertSame(0, Consultation::count());
        $this->actingAs($doctor->user)->get(route('consultations.create', $appointment))->assertRedirect(route('consultations.queue'));
    }

    public function test_cancelled_appointment_cannot_be_consulted(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->appointment($doctor, $this->patient($doctor), Appointment::CANCELLED);

        $this->expectException(ValidationException::class);
        app(ConsultationService::class)->record($appointment, ['diagnosis' => 'Nope'], [], $doctor->user);
    }

    public function test_doctor_cannot_record_a_consultation_for_another_doctors_appointment(): void
    {
        $owner = $this->doctor();
        $other = $this->doctor();
        $appointment = $this->checkedIn($owner);

        $this->expectException(AuthorizationException::class);
        app(ConsultationService::class)->record($appointment, ['diagnosis' => 'Wrong doctor'], [], $other->user);
    }

    public function test_failed_consultation_is_rolled_back_completely(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);
        $active = Medicine::factory()->create();
        $inactive = Medicine::factory()->create(['is_active' => false]);

        try {
            app(ConsultationService::class)->record($appointment, ['diagnosis' => 'Rollback test'],
                [$this->rx($active), $this->rx($inactive)], $doctor->user);
            $this->fail('An inactive medicine must abort the whole consultation.');
        } catch (ValidationException) {
            // expected
        }

        $this->assertSame(0, Consultation::count());
        $this->assertSame(0, Prescription::count());
        $this->assertSame(Appointment::CHECKED_IN, $appointment->fresh()->status);
    }

    public function test_consultation_form_validation(): void
    {
        $doctor = $this->doctor();
        $appointment = $this->checkedIn($doctor);
        $medicine = Medicine::factory()->create();
        $inactive = Medicine::factory()->create(['is_active' => false]);
        $post = fn (array $data) => $this->actingAs($doctor->user)->post(route('consultations.store', $appointment), $data);

        $post(['consultation_date' => today()->toDateString(), 'diagnosis' => ''])->assertSessionHasErrors('diagnosis');
        $post(['consultation_date' => today()->addDay()->toDateString(), 'diagnosis' => 'Future date'])->assertSessionHasErrors('consultation_date');
        $post(['consultation_date' => today()->toDateString(), 'diagnosis' => 'Dx', 'prescriptions' => [['medicine_id' => $medicine->id]]])
            ->assertSessionHasErrors(['prescriptions.0.dosage', 'prescriptions.0.frequency', 'prescriptions.0.duration']);
        $post(['consultation_date' => today()->toDateString(), 'diagnosis' => 'Dx', 'prescriptions' => [$this->rx($medicine), $this->rx($medicine)]])
            ->assertSessionHasErrors('prescriptions.1.medicine_id');
        $post(['consultation_date' => today()->toDateString(), 'diagnosis' => 'Dx', 'prescriptions' => [$this->rx($inactive)]])
            ->assertSessionHasErrors('prescriptions.0.medicine_id');
        $post(['consultation_date' => today()->toDateString(), 'diagnosis' => 'Dx', 'prescriptions' => [$this->rx($medicine, ['medicine_id' => 99999])]])
            ->assertSessionHasErrors('prescriptions.0.medicine_id');

        $this->assertSame(0, Consultation::count());
    }

    public function test_consultation_workspace_shows_patient_appointment_and_history(): void
    {
        $doctor = $this->doctor();
        $patient = $this->patient($doctor, ['full_name' => 'Workspace Patient']);
        $medicine = Medicine::factory()->create(['name' => 'Historymycin', 'strength' => '250mg']);

        $earlierAppt = $this->appointment($doctor, $patient, Appointment::COMPLETED, today()->subDays(3)->toDateString(), '09:00:00');
        $earlier = Consultation::create([
            'appointment_id' => $earlierAppt->id, 'patient_id' => $patient->id, 'doctor_id' => $doctor->id,
            'consultation_date' => today()->subDays(3)->toDateString(), 'diagnosis' => 'Earlier bronchitis', 'fee_charged' => 10000,
        ]);
        $earlier->prescriptions()->create(['medicine_id' => $medicine->id, 'dosage' => '1 capsule', 'frequency' => 'Daily', 'duration' => '7 days']);

        $current = $this->checkedIn($doctor, $patient, '10:00:00');

        $this->actingAs($doctor->user)->get(route('consultations.create', $current))
            ->assertOk()
            ->assertSee('Workspace Patient')
            ->assertSee($patient->patient_number)
            ->assertSee($current->appointment_number)
            ->assertSee('Earlier bronchitis')
            ->assertSee('Historymycin')
            ->assertSee('Add medicine')
            ->assertSee('id="rxTemplate"', false)
            ->assertSee('remove-rx', false);
    }

    public function test_history_only_includes_consultations_by_the_viewing_doctor(): void
    {
        $doctorA = $this->doctor();
        $doctorB = $this->doctor();
        $patient = $this->patient($doctorA);
        $byB = $this->consultation(15000, $doctorB, $patient);
        $current = $this->checkedIn($doctorA, $patient, '10:30:00');

        $this->actingAs($doctorA->user)->get(route('consultations.create', $current))
            ->assertOk()->assertDontSee($byB->diagnosis . ' ');

        $this->actingAs($doctorA->user)->get(route('patients.show', $patient))
            ->assertOk()->assertSee('No consultations on record with you');
    }

    public function test_previous_prescriptions_are_retrievable_by_the_treating_doctor(): void
    {
        $doctor = $this->doctor();
        $consultation = $this->consultation(15000, $doctor);
        $medicine = Medicine::factory()->create(['name' => 'Retrievamol']);
        $consultation->prescriptions()->create(['medicine_id' => $medicine->id, 'dosage' => '2 tablets', 'frequency' => 'Daily', 'duration' => '3 days']);

        $this->actingAs($doctor->user)->get(route('consultations.show', $consultation))
            ->assertOk()->assertSee('Retrievamol')->assertSee('2 tablets');

        $this->actingAs($doctor->user)->get(route('patients.show', $consultation->patient))
            ->assertOk()->assertSee('Retrievamol');

        $this->assertDatabaseHas('audit_logs', ['action' => 'prescription.viewed', 'auditable_id' => $consultation->id, 'user_id' => $doctor->user->id]);
    }

    public function test_consultation_queue_lists_waiting_patients_first(): void
    {
        $doctor = $this->doctor();
        $this->appointment($doctor, $this->patient($doctor, ['full_name' => 'Scheduled Sam']), 'scheduled', null, '08:00:00');
        $this->checkedIn($doctor, $this->patient($doctor, ['full_name' => 'Waiting Wendy']), '11:00:00');

        $content = $this->actingAs($doctor->user)->get(route('consultations.queue'))->assertOk()->getContent();

        $this->assertLessThan(strpos($content, 'Scheduled Sam'), strpos($content, 'Waiting Wendy'));
        $this->assertStringContainsString('Start consultation', $content);
    }

    public function test_consultation_records_cannot_be_deleted_through_the_application(): void
    {
        $consultation = $this->consultation();

        $this->actingAs($this->staff('admin'))->delete('/consultations/'.$consultation->id)->assertStatus(405);
        $this->assertSame(1, Consultation::count());
    }
}
