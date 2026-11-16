<?php

namespace Tests\Feature;

use App\Models\Patient;
use Illuminate\Database\UniqueConstraintViolationException;
use Tests\TestCase;

class PatientManagementTest extends TestCase
{
    protected function payload(array $overrides = []): array
    {
        $doctor = $this->doctor();

        return array_merge([
            'full_name' => 'Test Patient One',
            'date_of_birth' => '1990-05-21',
            'gender' => 'female',
            'address' => 'Area 25, Lilongwe',
            'phone' => '+265 999 123 456',
            'next_of_kin_name' => 'Next Of Kin',
            'next_of_kin_phone' => '0888 765 432',
            'primary_doctor_id' => $doctor->id,
        ], $overrides);
    }

    public function test_attendant_can_register_a_patient_with_a_generated_number(): void
    {
        $attendant = $this->staff('attendant');

        $response = $this->actingAs($attendant)->post(route('patients.store'), $this->payload());

        $patient = Patient::firstOrFail();
        $response->assertRedirect(route('patients.show', $patient));
        $this->assertSame('CC-000001', $patient->patient_number);
        $this->assertSame($attendant->id, $patient->registered_by);
        $this->assertTrue($patient->registered_on->isToday());
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.registered', 'user_id' => $attendant->id]);
    }

    public function test_patient_numbers_are_unique_and_sequential(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();

        foreach (range(1, 5) as $i) {
            $this->actingAs($attendant)->post(route('patients.store'), $this->payload([
                'full_name' => "Patient Number {$i}", 'phone' => "+265 999 000 00{$i}", 'primary_doctor_id' => $doctor->id,
            ]))->assertSessionHasNoErrors();
        }

        $numbers = Patient::orderBy('id')->pluck('patient_number')->all();
        $this->assertSame(['CC-000001', 'CC-000002', 'CC-000003', 'CC-000004', 'CC-000005'], $numbers);
        $this->assertCount(5, array_unique($numbers));
    }

    public function test_database_rejects_duplicate_patient_numbers(): void
    {
        $this->patient(null, ['patient_number' => 'CC-000777']);

        $this->expectException(UniqueConstraintViolationException::class);
        $this->patient(null, ['patient_number' => 'CC-000777']);
    }

    public function test_duplicate_registration_is_prevented(): void
    {
        $attendant = $this->staff('attendant');
        $payload = $this->payload();

        $this->actingAs($attendant)->post(route('patients.store'), $payload)->assertSessionHasNoErrors();
        $this->actingAs($attendant)->post(route('patients.store'), $payload)->assertSessionHasErrors('full_name');

        $this->assertSame(1, Patient::count());
    }

    public function test_registration_validation_errors_are_shown_and_input_is_retained(): void
    {
        $attendant = $this->staff('attendant');

        $this->actingAs($attendant)->from(route('patients.create'))->post(route('patients.store'), $this->payload([
            'date_of_birth' => today()->addDay()->toDateString(),
            'phone' => 'abc',
            'next_of_kin_phone' => '',
            'gender' => 'robot',
            'primary_doctor_id' => 99999,
        ]))->assertRedirect(route('patients.create'))
            ->assertSessionHasErrors(['date_of_birth', 'phone', 'next_of_kin_phone', 'gender', 'primary_doctor_id'])
            ->assertSessionHasInput('full_name', 'Test Patient One');

        $this->assertSame(0, Patient::count());

        // Old input is re-displayed in the form and errors appear beside the fields.
        $this->actingAs($attendant)->withSession(['_old_input' => ['full_name' => 'Test Patient One']])
            ->get(route('patients.create'))->assertOk()->assertSee('Test Patient One');
    }

    public function test_inactive_doctor_cannot_be_assigned_as_primary_doctor(): void
    {
        $doctor = $this->doctor('full_time', true, ['is_active' => false]);

        $this->actingAs($this->staff('attendant'))->post(route('patients.store'), $this->payload(['primary_doctor_id' => $doctor->id]))
            ->assertSessionHasErrors('primary_doctor_id');
    }

    public function test_patients_can_be_searched_by_number_name_and_date_of_birth(): void
    {
        $doctor = $this->doctor();
        $mary = $this->patient($doctor, ['full_name' => 'Mary Zalimba', 'patient_number' => 'CC-000101', 'date_of_birth' => '1980-02-03']);
        $john = $this->patient($doctor, ['full_name' => 'John Kachale', 'patient_number' => 'CC-000102', 'date_of_birth' => '1975-07-09']);
        $user = $this->staff('receptionist');

        $this->actingAs($user)->get(route('patients.index', ['q' => 'CC-000101']))->assertSee('Mary Zalimba')->assertDontSee('John Kachale');
        $this->actingAs($user)->get(route('patients.index', ['q' => 'Kachale']))->assertSee('John Kachale')->assertDontSee('Mary Zalimba');
        $this->actingAs($user)->get(route('patients.index', ['dob' => '1980-02-03']))->assertSee('Mary Zalimba')->assertDontSee('John Kachale');
        $this->actingAs($user)->get(route('patients.index', ['q' => '1975-07-09']))->assertSee('John Kachale')->assertDontSee('Mary Zalimba');
        $this->actingAs($user)->get(route('patients.index', ['q' => 'zzzz-no-match']))->assertSee('No patients match your search.');
    }

    public function test_patient_list_is_paginated_with_configurable_page_size(): void
    {
        $doctor = $this->doctor();
        foreach (range(1, 12) as $i) {
            $this->patient($doctor, ['full_name' => sprintf('Paged Person %02d', $i)]);
        }
        $user = $this->staff('receptionist');

        $default = $this->actingAs($user)->get(route('patients.index'))->assertOk();
        $this->assertSame(10, substr_count($default->getContent(), 'Paged Person'));

        $big = $this->actingAs($user)->get(route('patients.index', ['per_page' => 25]))->assertOk();
        $this->assertSame(12, substr_count($big->getContent(), 'Paged Person'));

        // A value outside the allowed options falls back to the default.
        $odd = $this->actingAs($user)->get(route('patients.index', ['per_page' => 7]))->assertOk();
        $this->assertSame(10, substr_count($odd->getContent(), 'Paged Person'));
    }

    public function test_attendant_can_update_patient_information_and_it_is_audited(): void
    {
        $attendant = $this->staff('attendant');
        $doctor = $this->doctor();
        $patient = $this->patient($doctor, ['phone' => '+265 999 111 111']);

        $this->actingAs($attendant)->put(route('patients.update', $patient), $this->payload([
            'full_name' => $patient->full_name,
            'date_of_birth' => $patient->date_of_birth->toDateString(),
            'gender' => $patient->gender,
            'phone' => '+265 999 222 222',
            'primary_doctor_id' => $doctor->id,
        ]))->assertRedirect(route('patients.show', $patient));

        $this->assertSame('+265 999 222 222', $patient->fresh()->phone);
        $this->assertSame($patient->patient_number, $patient->fresh()->patient_number);
        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.updated', 'auditable_id' => $patient->id]);
    }

    public function test_viewing_a_patient_record_is_audited(): void
    {
        $patient = $this->patient($this->doctor());
        $admin = $this->staff('admin');

        $this->actingAs($admin)->get(route('patients.show', $patient))->assertOk()->assertSee($patient->patient_number);

        $this->assertDatabaseHas('audit_logs', ['action' => 'patient.viewed', 'user_id' => $admin->id, 'auditable_id' => $patient->id]);
    }

    public function test_patient_profile_shows_history_sections_to_authorised_roles(): void
    {
        $consultation = $this->consultation();

        $this->actingAs($this->staff('admin'))->get(route('patients.show', $consultation->patient))
            ->assertOk()->assertSee('Appointment history')->assertSee('Consultation')->assertSee('Payment history');
    }

    public function test_patient_records_have_no_delete_route(): void
    {
        $patient = $this->patient($this->doctor());

        $this->actingAs($this->staff('admin'))->delete('/patients/'.$patient->id)->assertStatus(405);
        $this->assertSame(1, Patient::count());
    }
}
