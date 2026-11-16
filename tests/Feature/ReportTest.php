<?php

namespace Tests\Feature;

use App\Exports\ArrayExport;
use App\Models\Appointment;
use App\Models\Medicine;
use App\Services\PaymentService;
use App\Services\ReportService;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class ReportTest extends TestCase
{
    protected function assertPdf($response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    protected function assertXlsx($response): void
    {
        $response->assertOk();
        $this->assertStringContainsString('.xlsx', $response->headers->get('Content-Disposition'));
    }

    protected function pay($consultation, float $amount, string $method)
    {
        return app(PaymentService::class)->record($consultation, $amount, $method, (string) Str::uuid(), $this->staff('attendant'))['payment'];
    }

    public function test_prescription_pdf_for_doctor_and_admin_only(): void
    {
        $doctor = $this->doctor();
        $consultation = $this->consultation(15000, $doctor);
        $consultation->prescriptions()->create([
            'medicine_id' => Medicine::factory()->create()->id, 'dosage' => '1', 'frequency' => 'Daily', 'duration' => '3 days',
        ]);
        $url = route('patients.prescriptions', $consultation->patient);

        $this->assertPdf($this->actingAs($doctor->user)->get($url));
        $this->assertPdf($this->actingAs($this->staff('admin'))->get($url));
        $this->actingAs($this->staff('attendant'))->get($url)->assertForbidden();
        $this->actingAs($this->staff('receptionist'))->get($url)->assertForbidden();
        $this->actingAs($this->doctor()->user)->get($url)->assertForbidden();
    }

    public function test_daily_appointment_list_pdf_and_excel(): void
    {
        $doctor = $this->doctor();
        $this->appointment($doctor, $this->patient($doctor), 'scheduled', null, '09:00:00');

        $this->assertPdf($this->actingAs($doctor->user)->get(route('reports.daily', ['format' => 'pdf', 'date' => today()->toDateString()])));
        $this->assertXlsx($this->actingAs($doctor->user)->get(route('reports.daily', ['format' => 'xlsx', 'date' => today()->toDateString()])));

        $admin = $this->staff('admin');
        $this->assertPdf($this->actingAs($admin)->get(route('reports.daily', ['doctor_id' => $doctor->id, 'format' => 'pdf'])));
        $this->assertXlsx($this->actingAs($this->staff('receptionist'))->get(route('reports.daily', ['doctor_id' => $doctor->id, 'format' => 'xlsx'])));

        $this->actingAs($this->staff('attendant'))->get(route('reports.daily', ['doctor_id' => $doctor->id]))->assertForbidden();
        $this->actingAs($admin)->get(route('reports.daily', ['format' => 'pdf']))->assertSessionHasErrors('doctor_id');
    }

    public function test_daily_list_contains_only_that_doctors_real_appointments(): void
    {
        Excel::fake();
        $doctor = $this->doctor();
        $other = $this->doctor();
        $mine = $this->appointment($doctor, $this->patient($doctor, ['full_name' => 'Listed Patient']), 'scheduled', null, '09:00:00');
        $cancelled = $this->appointment($doctor, $this->patient($doctor, ['full_name' => 'Cancelled Patient']), 'cancelled', null, '09:30:00');
        $theirs = $this->appointment($other, $this->patient($other, ['full_name' => 'Foreign Patient']), 'scheduled', null, '09:00:00');

        // A doctor passing someone else's doctor_id still only gets their own list.
        $this->actingAs($doctor->user)->get(route('reports.daily', ['doctor_id' => $other->id, 'format' => 'xlsx']));

        Excel::assertDownloaded('appointments-'.$doctor->doctor_number.'-'.today()->toDateString().'.xlsx', function (ArrayExport $export) use ($mine) {
            $flat = json_encode($export->array());

            return str_contains($flat, 'Listed Patient') && str_contains($flat, $mine->appointment_number)
                && ! str_contains($flat, 'Cancelled Patient') && ! str_contains($flat, 'Foreign Patient');
        });
    }

    public function test_patient_list_per_doctor_excel(): void
    {
        $doctor = $this->doctor();
        $other = $this->doctor();
        $this->patient($doctor, ['full_name' => 'Doctor One Patient']);
        $this->patient($other, ['full_name' => 'Doctor Two Patient']);

        $this->assertXlsx($this->actingAs($doctor->user)->get(route('reports.doctor-patients')));
        $this->assertXlsx($this->actingAs($this->staff('admin'))->get(route('reports.doctor-patients', ['doctor_id' => $doctor->id])));
        $this->actingAs($this->staff('receptionist'))->get(route('reports.doctor-patients', ['doctor_id' => $doctor->id]))->assertForbidden();
        $this->actingAs($this->staff('attendant'))->get(route('reports.doctor-patients', ['doctor_id' => $doctor->id]))->assertForbidden();

        Excel::fake();
        $this->actingAs($doctor->user)->get(route('reports.doctor-patients', ['doctor_id' => $other->id]));
        Excel::assertDownloaded('patients-'.$doctor->doctor_number.'.xlsx', function (ArrayExport $export) {
            $flat = json_encode($export->array());

            return str_contains($flat, 'Doctor One Patient') && ! str_contains($flat, 'Doctor Two Patient');
        });
    }

    public function test_visiting_doctor_schedule_pdf_access(): void
    {
        $this->doctor('visiting');

        $this->assertPdf($this->actingAs($this->staff('admin'))->get(route('reports.visiting')));
        $this->assertPdf($this->actingAs($this->staff('receptionist'))->get(route('reports.visiting')));
        $this->actingAs($this->staff('attendant'))->get(route('reports.visiting'))->assertForbidden();
        $this->actingAs($this->doctor()->user)->get(route('reports.visiting'))->assertForbidden();
    }

    protected function seedRevenueData(): void
    {
        $a = $this->consultation(15000, null, null, '09:00:00');
        $b = $this->consultation(15000, null, null, '09:30:00');
        $this->pay($a, 15000, 'cash');
        $this->pay($b, 5000, 'mobile_money');

        $doctor = $this->doctor();
        $this->appointment($doctor, $this->patient($doctor), 'cancelled', null, '10:00:00');
        $this->appointment($doctor, $this->patient($doctor), 'no_show', null, '10:30:00');
        $this->appointment($doctor, $this->patient($doctor), 'scheduled', null, '11:00:00');
    }

    public function test_revenue_and_attendance_calculations_are_accurate(): void
    {
        $this->seedRevenueData();

        $s = app(ReportService::class)->revenueSummary(today(), today());

        $this->assertSame(5, $s['total_appointments']);
        $this->assertSame(2, $s['completed']);
        $this->assertSame(1, $s['cancelled']);
        $this->assertSame(1, $s['no_show']);
        $this->assertSame(1, $s['scheduled']);
        $this->assertSame(2, $s['total_consultations']);
        $this->assertEquals(20000, $s['total_revenue']);
        $this->assertEquals(10000, $s['outstanding']);
        $this->assertSame(2, $s['payments_count']);
        $this->assertEquals(40.0, $s['attendance']['completion_rate']);
        $this->assertEquals(20.0, $s['attendance']['cancellation_rate']);
        $this->assertEquals(20.0, $s['attendance']['no_show_rate']);

        $methods = collect($s['methods'])->pluck('total', 'method');
        $this->assertEquals(15000, $methods['Cash']);
        $this->assertEquals(5000, $methods['Mobile Money']);

        $this->assertCount(1, $s['daily_revenue']);
        $this->assertEquals(20000, $s['daily_revenue'][0]['total']);
    }

    public function test_revenue_report_excludes_data_outside_the_range(): void
    {
        $this->seedRevenueData();

        $s = app(ReportService::class)->revenueSummary(today()->subDays(10), today()->subDays(5));

        $this->assertSame(0, $s['total_appointments']);
        $this->assertEquals(0, $s['total_revenue']);
        $this->assertCount(6, $s['daily_revenue']);
    }

    public function test_revenue_report_is_admin_only_and_downloadable(): void
    {
        $this->seedRevenueData();
        $admin = $this->staff('admin');
        $range = ['from' => today()->toDateString(), 'to' => today()->toDateString()];

        $this->actingAs($admin)->get(route('reports.revenue', $range))->assertOk()->assertSee('20,000.00')->assertSee('10,000.00');
        $this->assertPdf($this->actingAs($admin)->get(route('reports.revenue.download', $range + ['format' => 'pdf'])));
        $this->assertXlsx($this->actingAs($admin)->get(route('reports.revenue.download', $range + ['format' => 'xlsx'])));

        foreach (['receptionist', 'attendant'] as $role) {
            $user = $this->staff($role);
            $this->actingAs($user)->get(route('reports.revenue', $range))->assertForbidden();
            $this->actingAs($user)->get(route('reports.revenue.download', $range + ['format' => 'pdf']))->assertForbidden();
        }
        $this->actingAs($this->doctor()->user)->get(route('reports.revenue.download', $range))->assertForbidden();
    }

    public function test_revenue_report_rejects_an_inverted_date_range(): void
    {
        $this->actingAs($this->staff('admin'))
            ->get(route('reports.revenue', ['from' => today()->toDateString(), 'to' => today()->subDays(3)->toDateString()]))
            ->assertSessionHasErrors('to');
    }

    public function test_excel_revenue_export_contains_the_computed_figures(): void
    {
        $this->seedRevenueData();
        Excel::fake();

        $this->actingAs($this->staff('admin'))->get(route('reports.revenue.download', [
            'from' => today()->toDateString(), 'to' => today()->toDateString(), 'format' => 'xlsx',
        ]));

        Excel::assertDownloaded('revenue-attendance-'.today()->toDateString().'_to_'.today()->toDateString().'.xlsx', function (ArrayExport $export) {
            $rows = collect($export->array())->filter()->mapWithKeys(fn ($r) => [$r[0] => $r[1] ?? null]);

            return (float) $rows['Total revenue (MWK)'] === 20000.0
                && (float) $rows['Outstanding balances (MWK)'] === 10000.0
                && (int) $rows['Total appointments'] === 5
                && (int) $rows['Cancelled appointments'] === 1;
        });
    }

    public function test_report_generation_is_audited(): void
    {
        $admin = $this->staff('admin');
        $this->actingAs($admin)->get(route('reports.visiting'));

        $this->assertDatabaseHas('audit_logs', ['action' => 'report.generated', 'user_id' => $admin->id]);
    }

    public function test_dashboard_statistics_come_from_real_data(): void
    {
        $this->seedRevenueData();
        $admin = $this->staff('admin');

        $stats = app(\App\Services\DashboardService::class)->admin();

        $this->assertEquals(20000, $stats['revenue_today']);
        $this->assertEquals(10000, $stats['outstanding']);
        $this->assertSame(2, $stats['consultations_today']);
        $this->assertSame(4, $stats['appointments_today']); // 5 minus the cancelled one
        $this->assertEquals(20000, array_sum($stats['revenue_trend']['values']));
        $this->actingAs($admin)->get(route('dashboard'))->assertOk()->assertSee('20,000.00');
    }
}
