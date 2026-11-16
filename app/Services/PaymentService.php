<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Consultation;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class PaymentService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected NumberGenerator $numbers,
        protected AuditService $audit,
    ) {
    }

    /**
     * Record a (partial or full) payment against a consultation.
     *
     * A one-time submission token makes the operation idempotent: re-submitting the same form
     * returns the original payment instead of creating a second one.
     *
     * @return array{payment:Payment,duplicate:bool}
     *
     * @throws ValidationException
     */
    public function record(Consultation $consultation, float $amount, string $method, string $token, User $by): array
    {
        if ($existing = Payment::where('submission_token', $token)->first()) {
            return ['payment' => $existing, 'duplicate' => true];
        }

        if (! array_key_exists($method, Payment::METHODS)) {
            throw new ValidationException('Invalid payment method.', ['payment_method' => 'Invalid payment method.']);
        }

        try {
            $payment = $this->db->transaction(function () use ($consultation, $amount, $method, $token, $by) {
                $locked = Consultation::whereKey($consultation->id)->lockForUpdate()->firstOrFail();

                $fee = round((float) $locked->fee_charged, 2);
                $paidSoFar = round((float) Payment::where('consultation_id', $locked->id)->sum('amount_paid'), 2);
                $outstanding = round($fee - $paidSoFar, 2);
                $amount = round($amount, 2);

                if ($outstanding <= 0) {
                    throw new ValidationException('This consultation has already been paid in full.');
                }
                if ($amount <= 0) {
                    throw new ValidationException('The amount paid must be greater than zero.', ['amount_paid' => 'Must be greater than zero.']);
                }
                if ($amount > $outstanding) {
                    throw new ValidationException('The amount exceeds the outstanding balance of '.money($outstanding).'.', ['amount_paid' => 'Cannot exceed the outstanding balance of '.money($outstanding).'.']);
                }

                $balance = round($outstanding - $amount, 2);

                $payment = Payment::create([
                    'receipt_number' => $this->numbers->next('receipt', 'RCT-'),
                    'patient_id' => $locked->patient_id,
                    'consultation_id' => $locked->id,
                    'consultation_fee' => $fee,        // historical snapshot
                    'amount_paid' => $amount,
                    'balance_after' => $balance,
                    'payment_method' => $method,
                    'payment_status' => $balance <= 0 ? 'paid' : 'partial',
                    'paid_at' => now(),
                    'recorded_by' => $by->id,
                    'submission_token' => $token,
                ]);

                $this->audit->log('payment.recorded', "Recorded {$payment->receipt_number}: ".money($amount).' ('.$payment->payment_status.')', $payment, $by);

                return $payment;
            });
        } catch (UniqueConstraintViolationException) {
            // Concurrent double-submit with the same token.
            $existing = Payment::where('submission_token', $token)->firstOrFail();

            return ['payment' => $existing, 'duplicate' => true];
        }

        return ['payment' => $payment, 'duplicate' => false];
    }
}
