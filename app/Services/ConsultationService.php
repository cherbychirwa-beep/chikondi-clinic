<?php

namespace App\Services;

use App\Exceptions\AuthorizationException;
use App\Exceptions\ValidationException;
use App\Models\Appointment;
use App\Models\Consultation;
use App\Models\Medicine;
use App\Models\User;

class ConsultationService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected FeeService $fees,
        protected AuditService $audit,
    ) {
    }

    /**
     * Record a consultation (with zero or more prescriptions) and complete the appointment.
     *
     * @param  array{diagnosis:string,observations?:?string,notes?:?string,consultation_date?:?string}  $data
     * @param  array<int,array{medicine_id:int,dosage:string,frequency:string,duration:string,instructions?:?string}>  $prescriptions
     *
     * @throws AuthorizationException|ValidationException
     */
    public function record(Appointment $appointment, array $data, array $prescriptions, User $by): Consultation
    {
        $doctor = $by->doctor;
        if (! $doctor) {
            throw new AuthorizationException('Only doctors can record consultations.');
        }

        return $this->db->transaction(function () use ($appointment, $data, $prescriptions, $by, $doctor) {
            $locked = Appointment::whereKey($appointment->id)->lockForUpdate()->firstOrFail();

            if ($locked->doctor_id !== $doctor->id) {
                throw new AuthorizationException('This appointment belongs to another doctor.');
            }

            if ($locked->status === Appointment::COMPLETED || Consultation::where('appointment_id', $locked->id)->exists()) {
                throw new ValidationException('A consultation has already been recorded for this appointment.');
            }

            if ($locked->status !== Appointment::CHECKED_IN) {
                throw new ValidationException('The patient must be checked in before a consultation can be recorded.');
            }

            $date = $data['consultation_date'] ?? today()->toDateString();
            if ($date < $locked->appointment_date->toDateString()) {
                throw new ValidationException('The consultation date cannot be earlier than the appointment date.', ['consultation_date' => 'Cannot be earlier than the appointment date.']);
            }

            $consultation = Consultation::create([
                'appointment_id' => $locked->id,
                'patient_id' => $locked->patient_id,
                'doctor_id' => $doctor->id,
                'consultation_date' => $date,
                'diagnosis' => $data['diagnosis'],
                'observations' => $data['observations'] ?? null,
                'notes' => $data['notes'] ?? null,
                'fee_charged' => $this->fees->currentAmount(),
            ]);

            foreach ($prescriptions as $row) {
                if (! Medicine::active()->whereKey($row['medicine_id'])->exists()) {
                    throw new ValidationException('One of the selected medicines is not available.');
                }
                $consultation->prescriptions()->create([
                    'medicine_id' => $row['medicine_id'],
                    'dosage' => $row['dosage'],
                    'frequency' => $row['frequency'],
                    'duration' => $row['duration'],
                    'instructions' => $row['instructions'] ?? null,
                ]);
            }

            $locked->update(['status' => Appointment::COMPLETED]);

            $this->audit->log('consultation.created', "Recorded consultation for {$locked->appointment_number}", $consultation, $by);
            if (count($prescriptions) > 0) {
                $this->audit->log('prescription.created', count($prescriptions).' prescription(s) issued for consultation #'.$consultation->id, $consultation, $by);
            }

            return $consultation->load('prescriptions.medicine');
        });
    }
}
