<?php

namespace Tests\Feature;

use App\Exceptions\ValidationException;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Str;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    protected function pay($consultation, float $amount, string $method = 'cash', ?string $token = null, $by = null): array
    {
        return app(PaymentService::class)->record($consultation, $amount, $method, $token ?? (string) Str::uuid(), $by ?? $this->staff('attendant'));
    }

    public function test_attendant_records_a_full_payment_and_receives_a_receipt(): void
    {
        $attendant = $this->staff('attendant');
        $consultation = $this->consultation(15000);

        $this->actingAs($attendant)->get(route('payments.create', $consultation))
            ->assertOk()->assertSee($consultation->patient->full_name)->assertSee('15,000.00')->assertSee('submission_token', false);

        $response = $this->actingAs($attendant)->post(route('payments.store', $consultation), [
            'amount_paid' => '15000', 'payment_method' => 'mobile_money', 'submission_token' => (string) Str::uuid(),
        ]);

        $payment = Payment::firstOrFail();
        $response->assertRedirect(route('payments.show', $payment))->assertSessionHas('success');

        $this->assertSame('RCT-000001', $payment->receipt_number);
        $this->assertEquals(15000, $payment->consultation_fee);
        $this->assertEquals(15000, $payment->amount_paid);
        $this->assertEquals(0, $payment->balance_after);
        $this->assertSame('paid', $payment->payment_status);
        $this->assertSame('mobile_money', $payment->payment_method);
        $this->assertSame($attendant->id, $payment->recorded_by);
        $this->assertSame($consultation->patient_id, $payment->patient_id);
        $this->assertSame($consultation->id, $payment->consultation_id);
        $this->assertNotNull($payment->paid_at);
        $this->assertDatabaseHas('audit_logs', ['action' => 'payment.recorded', 'auditable_id' => $payment->id]);
    }

    public function test_receipt_numbers_are_unique(): void
    {
        $numbers = [];
        foreach (range(1, 6) as $i) {
            $numbers[] = $this->pay($this->consultation(15000, null, null, sprintf('%02d:00:00', 8 + $i)), 15000)['payment']->receipt_number;
        }

        $this->assertCount(6, array_unique($numbers));
        $this->assertSame(['RCT-000001', 'RCT-000002', 'RCT-000003', 'RCT-000004', 'RCT-000005', 'RCT-000006'], $numbers);
    }

    public function test_database_rejects_duplicate_receipt_numbers(): void
    {
        $first = $this->pay($this->consultation(15000), 15000)['payment'];
        $second = $this->consultation(15000, null, null, '11:00:00');

        $this->expectException(UniqueConstraintViolationException::class);
        Payment::create([
            'receipt_number' => $first->receipt_number, 'patient_id' => $second->patient_id, 'consultation_id' => $second->id,
            'consultation_fee' => 15000, 'amount_paid' => 15000, 'balance_after' => 0, 'payment_method' => 'cash',
            'payment_status' => 'paid', 'paid_at' => now(), 'recorded_by' => $this->staff('attendant')->id,
        ]);
    }

    public function test_partial_payments_are_tracked_until_the_balance_is_cleared(): void
    {
        $consultation = $this->consultation(15000);

        $first = $this->pay($consultation, 5000)['payment'];
        $this->assertSame('partial', $first->payment_status);
        $this->assertEquals(10000, $first->balance_after);
        $this->assertSame('10000.00', $consultation->fresh()->outstandingBalance());
        $this->assertSame('5000.00', $consultation->fresh()->totalPaid());

        $second = $this->pay($consultation, 10000, 'bank_transfer')['payment'];
        $this->assertSame('paid', $second->payment_status);
        $this->assertEquals(0, $second->balance_after);
        $this->assertSame('0.00', $consultation->fresh()->outstandingBalance());
        $this->assertNotSame($first->receipt_number, $second->receipt_number);
        $this->assertSame(2, Payment::where('consultation_id', $consultation->id)->count());
    }

    public function test_paid_consultation_cannot_be_paid_again(): void
    {
        $consultation = $this->consultation(15000);
        $this->pay($consultation, 15000);

        $this->expectException(ValidationException::class);
        $this->pay($consultation, 100);
    }

    public function test_overpayment_zero_and_invalid_amounts_are_rejected(): void
    {
        $attendant = $this->staff('attendant');
        $consultation = $this->consultation(15000);
        $post = fn (array $data) => $this->actingAs($attendant)->post(route('payments.store', $consultation), $data + ['submission_token' => (string) Str::uuid()]);

        $post(['amount_paid' => '15000.01', 'payment_method' => 'cash'])->assertSessionHasErrors('amount_paid');
        $post(['amount_paid' => '0', 'payment_method' => 'cash'])->assertSessionHasErrors('amount_paid');
        $post(['amount_paid' => '-50', 'payment_method' => 'cash'])->assertSessionHasErrors('amount_paid');
        $post(['amount_paid' => 'abc', 'payment_method' => 'cash'])->assertSessionHasErrors('amount_paid');
        $post(['amount_paid' => '100.555', 'payment_method' => 'cash'])->assertSessionHasErrors('amount_paid');
        $post(['amount_paid' => '100', 'payment_method' => 'barter'])->assertSessionHasErrors('payment_method');
        $post(['amount_paid' => '', 'payment_method' => ''])->assertSessionHasErrors(['amount_paid', 'payment_method']);

        $this->assertSame(0, Payment::count());
    }

    public function test_overpayment_is_blocked_by_the_service_even_if_validation_is_bypassed(): void
    {
        $consultation = $this->consultation(15000);
        $this->pay($consultation, 10000);

        $this->expectException(ValidationException::class);
        $this->pay($consultation, 5000.01);
    }

    public function test_duplicate_form_submission_does_not_create_a_second_payment(): void
    {
        $attendant = $this->staff('attendant');
        $consultation = $this->consultation(15000);
        $data = ['amount_paid' => '4000', 'payment_method' => 'cash', 'submission_token' => 'token-abc-123'];

        $this->actingAs($attendant)->post(route('payments.store', $consultation), $data)->assertSessionHas('success');
        $this->actingAs($attendant)->post(route('payments.store', $consultation), $data)->assertSessionHas('info');

        $this->assertSame(1, Payment::count());
        $this->assertSame('4000.00', $consultation->fresh()->totalPaid());
    }

    public function test_service_is_idempotent_for_the_same_token(): void
    {
        $consultation = $this->consultation(15000);

        $a = $this->pay($consultation, 3000, 'cash', 'same-token');
        $b = $this->pay($consultation, 3000, 'cash', 'same-token');

        $this->assertFalse($a['duplicate']);
        $this->assertTrue($b['duplicate']);
        $this->assertSame($a['payment']->id, $b['payment']->id);
        $this->assertSame(1, Payment::count());
    }

    public function test_historical_amounts_are_not_changed_by_later_fee_changes(): void
    {
        $consultation = $this->consultation(12000);
        $payment = $this->pay($consultation, 12000)['payment'];

        $this->fee(25000);

        $this->assertEquals(12000, $payment->fresh()->consultation_fee);
        $this->assertEquals(12000, $consultation->fresh()->fee_charged);
        $this->assertEquals(0, $payment->fresh()->balance_after);
    }

    public function test_pdf_receipt_is_generated_for_attendant_and_admin(): void
    {
        $payment = $this->pay($this->consultation(15000), 15000)['payment'];

        foreach (['attendant', 'admin'] as $role) {
            $response = $this->actingAs($this->staff($role))->get(route('payments.receipt', $payment))->assertOk();
            $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
            $this->assertStringContainsString($payment->receipt_number.'.pdf', $response->headers->get('Content-Disposition'));
            $this->assertStringStartsWith('%PDF', $response->getContent());
        }
    }

    public function test_receipt_and_payment_records_are_denied_to_other_roles(): void
    {
        $consultation = $this->consultation(15000);
        $payment = $this->pay($consultation, 15000)['payment'];
        $doctor = $consultation->doctor;

        foreach ([$this->staff('receptionist'), $doctor->user] as $user) {
            $this->actingAs($user)->get(route('payments.receipt', $payment))->assertForbidden();
            $this->actingAs($user)->get(route('payments.show', $payment))->assertForbidden();
            $this->actingAs($user)->get(route('payments.index'))->assertForbidden();
        }
    }

    public function test_only_attendants_can_record_payments(): void
    {
        $consultation = $this->consultation(15000);
        $data = ['amount_paid' => '1000', 'payment_method' => 'cash', 'submission_token' => 'x-1'];

        foreach (['admin', 'receptionist'] as $role) {
            $this->actingAs($this->staff($role))->post(route('payments.store', $consultation), $data)->assertForbidden();
        }
        $this->actingAs($consultation->doctor->user)->post(route('payments.store', $consultation), $data)->assertForbidden();

        $this->assertSame(0, Payment::count());
    }

    public function test_pending_payments_list_shows_only_outstanding_balances(): void
    {
        $unpaid = $this->consultation(15000, null, $this->patient(null, ['full_name' => 'Owes Money']), '09:00:00');
        $partial = $this->consultation(15000, null, $this->patient(null, ['full_name' => 'Part Payer']), '09:30:00');
        $settled = $this->consultation(15000, null, $this->patient(null, ['full_name' => 'Fully Settled']), '10:00:00');
        $this->pay($partial, 5000);
        $this->pay($settled, 15000);

        $this->actingAs($this->staff('attendant'))->get(route('payments.pending'))
            ->assertOk()->assertSee('Owes Money')->assertSee('Part Payer')->assertDontSee('Fully Settled')
            ->assertSee('10,000.00');
    }

    public function test_payment_records_can_be_searched_and_filtered(): void
    {
        $cash = $this->pay($this->consultation(15000, null, $this->patient(null, ['patient_number' => 'CC-000901']), '09:00:00'), 15000, 'cash')['payment'];
        $momo = $this->pay($this->consultation(15000, null, $this->patient(null, ['patient_number' => 'CC-000902']), '09:30:00'), 5000, 'mobile_money')['payment'];
        $user = $this->staff('attendant');

        $this->actingAs($user)->get(route('payments.index', ['receipt' => $cash->receipt_number]))->assertSee($cash->receipt_number)->assertDontSee($momo->receipt_number);
        $this->actingAs($user)->get(route('payments.index', ['patient_number' => 'CC-000902']))->assertSee($momo->receipt_number)->assertDontSee($cash->receipt_number);
        $this->actingAs($user)->get(route('payments.index', ['method' => 'cash']))->assertSee($cash->receipt_number)->assertDontSee($momo->receipt_number);
        $this->actingAs($user)->get(route('payments.index', ['status' => 'partial']))->assertSee($momo->receipt_number)->assertDontSee($cash->receipt_number);
        $this->actingAs($user)->get(route('payments.index', ['date' => today()->toDateString()]))->assertSee($cash->receipt_number)->assertSee($momo->receipt_number);
        $this->actingAs($user)->get(route('payments.index', ['date' => today()->subDays(5)->toDateString()]))->assertDontSee($cash->receipt_number);
    }

    public function test_payment_records_have_no_delete_or_edit_route(): void
    {
        $payment = $this->pay($this->consultation(15000), 15000)['payment'];
        $admin = $this->staff('admin');

        $this->actingAs($admin)->delete('/payments/'.$payment->id)->assertStatus(405);
        $this->actingAs($admin)->put('/payments/'.$payment->id, ['amount_paid' => 1])->assertStatus(405);
        $this->assertEquals(15000, $payment->fresh()->amount_paid);
    }
}
