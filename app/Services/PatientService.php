<?php

namespace App\Services;

use App\Exceptions\ValidationException;
use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\UniqueConstraintViolationException;

class PatientService
{
    public function __construct(
        protected DatabaseConnection $db,
        protected NumberGenerator $numbers,
        protected AuditService $audit,
    ) {
    }

    protected function duplicateExists(array $data, ?int $ignoreId = null): bool
    {
        return Patient::where('full_name', $data['full_name'])
            ->whereDate('date_of_birth', $data['date_of_birth'])
            ->where('phone', $data['phone'])
            ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
            ->exists();
    }

    /** @throws ValidationException */
    public function register(array $data, User $by): Patient
    {
        $message = 'A patient with the same name, date of birth and phone number is already registered.';

        if ($this->duplicateExists($data)) {
            throw new ValidationException($message, ['full_name' => $message]);
        }

        try {
            return $this->db->transaction(function () use ($data, $by) {
                $patient = Patient::create($data + [
                    'patient_number' => $this->numbers->next('patient', 'CC-', 6),
                    'registered_on' => today()->toDateString(),
                    'registered_by' => $by->id,
                ]);

                $this->audit->log('patient.registered', "Registered patient {$patient->patient_number} ({$patient->full_name})", $patient, $by);

                return $patient;
            });
        } catch (UniqueConstraintViolationException) {
            throw new ValidationException($message, ['full_name' => $message]);
        }
    }

    /** @throws ValidationException */
    public function update(Patient $patient, array $data, User $by): Patient
    {
        if ($this->duplicateExists($data, $patient->id)) {
            $message = 'Another patient with the same name, date of birth and phone number already exists.';
            throw new ValidationException($message, ['full_name' => $message]);
        }

        return $this->db->transaction(function () use ($patient, $data, $by) {
            $patient->fill($data);
            $changed = array_keys($patient->getDirty());
            $patient->save();

            if ($changed) {
                $this->audit->log('patient.updated', "Updated {$patient->patient_number}: ".implode(', ', $changed), $patient, $by);
            }

            return $patient;
        });
    }
}
