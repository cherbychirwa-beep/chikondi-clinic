<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Tests\TestCase;

/** Seeds the demonstration data and renders every major page with it, catching template/query errors. */
class DemoDataAndSmokeTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_seeder_creates_the_required_demonstration_data(): void
    {
        $count = fn (string $role) => User::whereHas('role', fn ($q) => $q->where('slug', $role))->count();

        $this->assertSame(1, $count('admin'));
        $this->assertSame(2, $count('receptionist'));
        $this->assertSame(2, $count('attendant'));
        $this->assertSame(4, $count('doctor'));
        $this->assertSame(2, Doctor::where('employment_type', 'full_time')->count());
        $this->assertSame(2, Doctor::where('employment_type', 'visiting')->count());
        $this->assertGreaterThanOrEqual(20, Patient::count());
        $this->assertGreaterThan(0, Medicine::count());
        $this->assertGreaterThan(0, Appointment::count());
        $this->assertGreaterThan(0, Consultation::count());
        $this->assertGreaterThan(0, Prescription::count());
        $this->assertGreaterThan(0, Payment::count());
        $this->assertSame(Patient::count(), Patient::distinct('patient_number')->count('patient_number'));
        $this->assertSame(Payment::count(), Payment::distinct('receipt_number')->count('receipt_number'));
    }

    public function test_seeding_twice_does_not_duplicate_anything(): void
    {
        $before = [User::count(), Patient::count(), Appointment::count(), Medicine::count(), Payment::count()];

        $this->seed(DatabaseSeeder::class);

        $this->assertSame($before, [User::count(), Patient::count(), Appointment::count(), Medicine::count(), Payment::count()]);
    }

    public function test_demo_accounts_can_sign_in(): void
    {
        $accounts = [
            ['admin', 'Admin@12345'], ['reception1', 'Reception@123'], ['reception2', 'Reception@123'],
            ['attendant1', 'Attendant@123'], ['attendant2', 'Attendant@123'],
            ['drtembo', 'Doctor@12345'], ['drkamanga', 'Doctor@12345'], ['drnyirenda', 'Doctor@12345'], ['drmsiska', 'Doctor@12345'],
        ];

        foreach ($accounts as [$username, $password]) {
            $this->post(route('login.attempt'), compact('username', 'password'))->assertRedirect(route('dashboard'));
            $this->get(route('dashboard'))->assertOk();
            $this->post(route('logout'));
        }
    }

    public function test_historical_consultations_kept_the_fee_in_force_when_they_happened(): void
    {
        $fees = Consultation::pluck('fee_charged')->map(fn ($f) => (int) $f)->unique()->sort()->values()->all();

        $this->assertNotEmpty($fees);
        $this->assertEmpty(array_diff($fees, [12000, 15000]));
    }

    public function test_every_role_can_render_its_pages_with_demo_data(): void
    {
        $admin = User::where('username', 'admin')->first();
        $this->actingAs($admin);
        $patient = Patient::first();
        $consultation = Consultation::first();
        $doctor = Doctor::first();
        $payment = Payment::first();

        foreach ([
            route('dashboard'), route('staff.index'), route('staff.create'), route('medicines.index'), route('medicines.create'),
            route('fees.index'), route('audit.index'), route('doctors.index'), route('doctors.create'), route('doctors.show', $doctor),
            route('doctors.edit', $doctor), route('patients.index'), route('patients.show', $patient), route('appointments.index'),
            route('appointments.schedule'), route('appointments.show', Appointment::first()), route('payments.index'),
            route('payments.show', $payment), route('consultations.index'), route('consultations.show', $consultation),
            route('reports.index'), route('reports.revenue', ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString()]),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->actingAs(User::where('username', 'reception1')->first());
        foreach ([
            route('dashboard'), route('appointments.book'), route('appointments.book', ['patient_id' => $patient->id]),
            route('appointments.book', ['patient_id' => $patient->id, 'date' => today()->addDays(3)->toDateString(), 'show_all' => 1]),
            route('appointments.index'), route('appointments.schedule', ['date' => today()->addDays(2)->toDateString()]),
            route('patients.index'), route('patients.show', $patient), route('doctors.index'), route('doctors.show', $doctor), route('reports.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $attendant = User::where('username', 'attendant1')->first();
        $this->actingAs($attendant);
        $outstanding = Consultation::outstanding()->first();
        foreach (array_filter([
            route('dashboard'), route('patients.create'), route('patients.index'), route('patients.edit', $patient), route('checkin.index'),
            route('payments.pending'), route('payments.index'), $outstanding ? route('payments.create', $outstanding) : null,
        ]) as $url) {
            $this->get($url)->assertOk();
        }

        $checkedIn = Appointment::where('status', Appointment::CHECKED_IN)->first();
        $this->assertNotNull($checkedIn, 'The demo data should contain a patient waiting for the doctor.');
        $doctorUser = $checkedIn->doctor->user;
        $this->actingAs($doctorUser);
        foreach ([
            route('dashboard'), route('consultations.queue'), route('consultations.create', $checkedIn), route('consultations.index'),
            route('appointments.index'), route('patients.index'), route('reports.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_demo_data_pdfs_and_exports_generate(): void
    {
        $this->actingAs(User::where('username', 'admin')->first());
        $consultation = Consultation::first();

        foreach ([
            route('payments.receipt', Payment::first()),
            route('patients.prescriptions', $consultation->patient),
            route('reports.visiting'),
            route('reports.daily', ['doctor_id' => $consultation->doctor_id, 'format' => 'pdf']),
            route('reports.revenue.download', ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString(), 'format' => 'pdf']),
        ] as $url) {
            $response = $this->get($url)->assertOk();
            $this->assertStringStartsWith('%PDF', $response->getContent(), $url);
        }

        $this->get(route('reports.daily', ['doctor_id' => $consultation->doctor_id, 'format' => 'xlsx']))->assertOk();
        $this->get(route('reports.doctor-patients', ['doctor_id' => $consultation->doctor_id]))->assertOk();
        $this->get(route('reports.revenue.download', ['from' => today()->subDays(30)->toDateString(), 'to' => today()->toDateString(), 'format' => 'xlsx']))->assertOk();
    }
}
