<?php

namespace Tests\Feature;

use App\Domain\Doctors\Doctor as DoctorDomain;
use App\Domain\Doctors\FullTimeDoctor;
use App\Domain\Doctors\VisitingDoctor;
use App\Domain\Staff\Administrator;
use App\Domain\Staff\Attendant;
use App\Domain\Staff\Clinician;
use App\Domain\Staff\Receptionist;
use App\Domain\Staff\StaffMember;
use App\Models\AuditLog;
use App\Services\AuditService;
use App\Services\DatabaseConnection;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;

class OopAndInfrastructureTest extends TestCase
{
    public function test_doctor_domain_is_polymorphic_by_employment_type(): void
    {
        $fullTime = $this->doctor('full_time');
        $visiting = $this->doctor('visiting');

        $this->assertInstanceOf(FullTimeDoctor::class, $fullTime->domain());
        $this->assertInstanceOf(VisitingDoctor::class, $visiting->domain());
        $this->assertInstanceOf(DoctorDomain::class, $fullTime->domain());
        $this->assertInstanceOf(DoctorDomain::class, $visiting->domain());
        $this->assertSame('Full-time', $fullTime->domain()->employmentLabel());
        $this->assertSame('Visiting', $visiting->domain()->employmentLabel());
    }

    public function test_visiting_doctor_is_only_available_inside_the_visit_window(): void
    {
        $doctor = $this->doctor('visiting', true, [
            'next_visit_date' => today()->addDays(5)->toDateString(),
            'next_visit_duration_days' => 2,
        ]);

        $this->assertFalse($doctor->domain()->isAvailableOn(today()->addDays(4)));
        $this->assertTrue($doctor->domain()->isAvailableOn(today()->addDays(5)));
        $this->assertTrue($doctor->domain()->isAvailableOn(today()->addDays(6)));
        $this->assertFalse($doctor->domain()->isAvailableOn(today()->addDays(7)));
    }

    public function test_deactivated_doctor_is_never_available(): void
    {
        $doctor = $this->doctor('full_time', true, ['is_active' => false]);

        $this->assertFalse($doctor->domain()->isAvailableOn(today()->addDay()));
    }

    public function test_staff_roles_resolve_to_their_domain_classes(): void
    {
        $map = [
            'admin' => Administrator::class, 'receptionist' => Receptionist::class,
            'attendant' => Attendant::class, 'doctor' => Clinician::class,
        ];

        foreach ($map as $role => $class) {
            $staff = $this->staff($role)->staff();
            $this->assertInstanceOf($class, $staff);
            $this->assertInstanceOf(StaffMember::class, $staff);
            $this->assertNotEmpty($staff->menu());
        }
    }

    public function test_database_connection_service_is_a_singleton(): void
    {
        $this->assertSame(app(DatabaseConnection::class), app(DatabaseConnection::class));
        $this->assertSame(app(DatabaseConnection::class)->connection()->getName(), DB::connection()->getName());
    }

    public function test_number_generator_never_repeats(): void
    {
        $numbers = app(NumberGenerator::class);
        $generated = [];

        foreach (range(1, 25) as $_) {
            $generated[] = $numbers->next('test_seq', 'T-', 4);
        }

        $this->assertCount(25, array_unique($generated));
        $this->assertSame('T-0001', $generated[0]);
        $this->assertSame('T-0025', $generated[24]);
    }

    public function test_audit_records_cannot_be_updated_or_deleted(): void
    {
        $log = app(AuditService::class)->log('test.action', 'Immutable record');

        $this->expectException(LogicException::class);
        $log->update(['description' => 'tampered']);
    }

    public function test_audit_records_cannot_be_deleted(): void
    {
        $log = app(AuditService::class)->log('test.action', 'Immutable record');

        try {
            $log->delete();
            $this->fail('Deleting an audit record should have thrown.');
        } catch (LogicException) {
            $this->assertSame(1, AuditLog::count());
        }
    }
}
