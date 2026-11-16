<?php

namespace Tests\Unit;

use App\Support\Permissions;
use PHPUnit\Framework\TestCase;

/** Replaces Laravel's default example test with checks on the pure-PHP permission matrix. */
class ExampleTest extends TestCase
{
    public function test_every_permission_in_the_matrix_is_defined(): void
    {
        foreach (Permissions::matrix() as $role => $slugs) {
            $this->assertArrayHasKey($role, Permissions::ROLES);
            foreach ($slugs as $slug) {
                $this->assertArrayHasKey($slug, Permissions::PERMISSIONS, "{$role} references unknown permission {$slug}");
            }
        }
    }

    public function test_confidential_clinical_permissions_are_not_granted_to_front_desk_roles(): void
    {
        $matrix = Permissions::matrix();

        foreach (['receptionist', 'attendant'] as $role) {
            $this->assertNotContains('consultations.view', $matrix[$role]);
            $this->assertNotContains('prescriptions.view', $matrix[$role]);
        }

        $this->assertContains('consultations.perform', $matrix['doctor']);
        $this->assertNotContains('consultations.perform', $matrix['admin']);
    }

    public function test_only_admin_can_see_audit_logs_and_manage_staff(): void
    {
        foreach (Permissions::matrix() as $role => $slugs) {
            $this->assertSame($role === 'admin', in_array('audit.view', $slugs, true), $role.' audit.view');
            $this->assertSame($role === 'admin', in_array('staff.manage', $slugs, true), $role.' staff.manage');
        }
    }
}
