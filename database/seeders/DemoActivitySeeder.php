<?php

namespace Database\Seeders;

use App\Models\Appointment;
use App\Models\AppointmentCancellation;
use App\Models\Consultation;
use App\Models\Doctor;
use App\Models\FeeSetting;
use App\Models\Medicine;
use App\Models\Patient;
use App\Models\Payment;
use App\Models\User;
use App\Services\AuditService;
use App\Services\NumberGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Demonstration appointments, consultations, prescriptions and payments spread over the last
 * two weeks plus today and the coming week. Deterministic (fixed random seed). Fictional data only.
 */
class DemoActivitySeeder extends Seeder
{
    /** @var array<string,bool> */
    protected array $usedSlots = [];

    /** @var array<string,bool> */
    protected array $patientSlots = [];

    public function run(NumberGenerator $numbers): void
    {
        if (Appointment::exists()) {
            return;
        }

        mt_srand(2026);

        $patients = Patient::all()->values();
        $doctors = Doctor::with('user')->get()->keyBy('id');
        $medicineIds = Medicine::active()->pluck('id')->all();
        $receptionists = User::whereHas('role', fn ($q) => $q->where('slug', 'receptionist'))->pluck('id')->all();
        $attendants = User::whereHas('role', fn ($q) => $q->where('slug', 'attendant'))->pluck('id')->all();

        $pick = fn (array $items) => $items[mt_rand(0, count($items) - 1)];
        $methods = array_keys(Payment::METHODS);
        $reasons = ['Patient requested a different day', 'Patient is unwell and cannot travel', 'Doctor unavailable – rescheduled', 'Transport problems'];
        $diagnoses = [
            'Uncomplicated malaria', 'Upper respiratory tract infection', 'Hypertension – routine review', 'Gastroenteritis',
            'Urinary tract infection', 'Type 2 diabetes – follow-up', 'Tension headache', 'Skin infection', 'Iron-deficiency anaemia', 'Allergic rhinitis',
        ];
        $rx = [
            ['1 tablet', 'Three times daily', '5 days'], ['2 tablets', 'Twice daily', '3 days'],
            ['1 capsule', 'Twice daily', '7 days'], ['5 ml', 'Three times daily', '5 days'], ['1 tablet', 'Once daily', '30 days'],
        ];

        // ---- Past two weeks: completed (with consultation/prescriptions/payments), cancelled, no-shows ----
        for ($back = 14; $back >= 1; $back--) {
            $day = today()->subDays($back);

            foreach (range(1, mt_rand(2, 4)) as $_) {
                $patient = $patients[mt_rand(0, $patients->count() - 1)];
                $doctor = (mt_rand(1, 10) <= 8 && $patient->primary_doctor_id)
                    ? $doctors[$patient->primary_doctor_id]
                    : $doctors->values()[mt_rand(0, $doctors->count() - 1)];

                $time = $this->freeTime($doctor, $day, $patient, $pick);
                if (! $time) {
                    continue;
                }

                $roll = mt_rand(1, 100);
                $status = $roll <= 72 ? Appointment::COMPLETED : ($roll <= 86 ? Appointment::CANCELLED : Appointment::NO_SHOW);

                $appointment = $this->makeAppointment($numbers, $patient, $doctor, $day, $time, $status, $pick($receptionists), $pick($attendants));

                if ($status === Appointment::CANCELLED) {
                    AppointmentCancellation::create([
                        'appointment_id' => $appointment->id,
                        'reason' => $pick($reasons),
                        'cancelled_at' => $day->copy()->subDay()->setTime(15, 0),
                        'cancelled_by' => $pick($receptionists),
                    ]);

                    continue;
                }

                if ($status !== Appointment::COMPLETED) {
                    continue;
                }

                $fee = (float) (FeeSetting::whereDate('effective_from', '<=', $day->toDateString())->orderByDesc('effective_from')->value('amount') ?? 0);

                $consultation = Consultation::create([
                    'appointment_id' => $appointment->id,
                    'patient_id' => $patient->id,
                    'doctor_id' => $doctor->id,
                    'consultation_date' => $day->toDateString(),
                    'diagnosis' => $pick($diagnoses),
                    'observations' => 'Vitals within acceptable range. (demo)',
                    'notes' => 'Patient advised on adherence and follow-up. (demo)',
                    'fee_charged' => $fee, // snapshot of the fee in force that day
                ]);

                $count = mt_rand(0, 3);
                $chosen = $count ? array_rand(array_flip($medicineIds), min($count, count($medicineIds))) : [];
                foreach ((array) $chosen as $medicineId) {
                    [$dose, $freq, $dur] = $pick($rx);
                    $consultation->prescriptions()->create([
                        'medicine_id' => $medicineId, 'dosage' => $dose, 'frequency' => $freq, 'duration' => $dur,
                        'instructions' => mt_rand(0, 1) ? 'Take after meals' : null,
                    ]);
                }

                $this->payFor($numbers, $consultation, $day, mt_rand(1, 10), $pick($attendants), $pick($methods));
            }
        }

        // ---- Today: one waiting for the doctor, others awaiting check-in ----
        $this->seedUpcoming($numbers, today(), 3, $patients, $doctors, $receptionists, $attendants, $pick, true);

        // ---- Next 7 days ----
        for ($ahead = 1; $ahead <= 7; $ahead++) {
            $this->seedUpcoming($numbers, today()->addDays($ahead), mt_rand(1, 3), $patients, $doctors, $receptionists, $attendants, $pick, false);
        }

        app(AuditService::class)->log('system.seeded', 'Demonstration data was seeded (fictional records only).');
    }

    /** First free slot time for the doctor on that day, or null. */
    protected function freeTime(Doctor $doctor, Carbon $day, Patient $patient, callable $pick): ?string
    {
        $times = [];
        foreach ($doctor->schedules()->where('is_active', true)->where('day_of_week', $day->dayOfWeek)->get() as $s) {
            $cursor = Carbon::parse($day->toDateString().' '.$s->start_time);
            $end = Carbon::parse($day->toDateString().' '.$s->end_time);
            while ($cursor->copy()->addMinutes($s->slot_minutes)->lte($end)) {
                $t = $cursor->format('H:i');
                if (! isset($this->usedSlots[$doctor->id.'|'.$day->toDateString().'|'.$t])
                    && ! isset($this->patientSlots[$patient->id.'|'.$day->toDateString().'|'.$t])) {
                    $times[] = $t;
                }
                $cursor->addMinutes($s->slot_minutes);
            }
        }

        return $times ? $pick($times) : null;
    }

    protected function makeAppointment(NumberGenerator $numbers, Patient $patient, Doctor $doctor, Carbon $day, string $time, string $status, int $bookedBy, int $attendantId): Appointment
    {
        $key = Appointment::makeSlotKey($doctor->id, $day->toDateString(), $time);
        $live = $status !== Appointment::CANCELLED;

        if ($live) {
            $this->usedSlots[$doctor->id.'|'.$day->toDateString().'|'.$time] = true;
            $this->patientSlots[$patient->id.'|'.$day->toDateString().'|'.$time] = true;
        }

        $checkedIn = in_array($status, [Appointment::COMPLETED, Appointment::CHECKED_IN], true);

        return Appointment::create([
            'appointment_number' => $numbers->next('appointment', 'APT-'),
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => $day->toDateString(),
            'appointment_time' => $time.':00',
            'status' => $status,
            'slot_key' => $live ? $key : null,
            'booked_by' => $bookedBy,
            'checked_in_at' => $checkedIn ? $day->copy()->setTimeFromTimeString($time)->subMinutes(10) : null,
            'checked_in_by' => $checkedIn ? $attendantId : null,
        ]);
    }

    /** Roughly 60% paid in full, 20% partial, 20% unpaid (outstanding). */
    protected function payFor(NumberGenerator $numbers, Consultation $consultation, Carbon $day, int $roll, int $attendantId, string $method): void
    {
        if ($roll > 8) {
            return; // unpaid – appears under Pending Payments
        }

        $fee = (float) $consultation->fee_charged;
        $amount = $roll <= 6 ? $fee : round($fee / 2, 2);

        Payment::create([
            'receipt_number' => $numbers->next('receipt', 'RCT-'),
            'patient_id' => $consultation->patient_id,
            'consultation_id' => $consultation->id,
            'consultation_fee' => $fee,
            'amount_paid' => $amount,
            'balance_after' => round($fee - $amount, 2),
            'payment_method' => $method,
            'payment_status' => $amount >= $fee ? 'paid' : 'partial',
            'paid_at' => $day->copy()->setTime(11, mt_rand(0, 59)),
            'recorded_by' => $attendantId,
            'submission_token' => (string) Str::uuid(),
        ]);
    }

    protected function seedUpcoming(NumberGenerator $numbers, Carbon $day, int $howMany, $patients, $doctors, array $receptionists, array $attendants, callable $pick, bool $today): void
    {
        $available = $doctors->filter(fn (Doctor $d) => $d->domain()->isAvailableOn($day))->values();
        if ($available->isEmpty()) {
            return;
        }

        for ($i = 0; $i < $howMany; $i++) {
            $patient = $patients[mt_rand(0, $patients->count() - 1)];
            $doctor = $available[$i % $available->count()];
            $time = $this->freeTime($doctor, $day, $patient, $pick);
            if (! $time) {
                continue;
            }

            $status = ($today && $i === 0) ? Appointment::CHECKED_IN : Appointment::SCHEDULED;
            $this->makeAppointment($numbers, $patient, $doctor, $day, $time, $status, $pick($receptionists), $pick($attendants));
        }
    }
}
