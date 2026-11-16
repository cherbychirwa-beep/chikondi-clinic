<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Consultation extends Model
{
    protected $fillable = [
        'appointment_id', 'patient_id', 'doctor_id', 'consultation_date',
        'diagnosis', 'observations', 'notes', 'fee_charged',
    ];

    protected function casts(): array
    {
        return ['consultation_date' => 'date', 'fee_charged' => 'decimal:2'];
    }

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    public function prescriptions(): HasMany
    {
        return $this->hasMany(Prescription::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function totalPaid(): string
    {
        return number_format((float) $this->payments()->sum('amount_paid'), 2, '.', '');
    }

    public function outstandingBalance(): string
    {
        return number_format(max(0, (float) $this->fee_charged - (float) $this->payments()->sum('amount_paid')), 2, '.', '');
    }

    /** Consultations whose fee is not yet fully covered by payments. */
    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereRaw(
            'consultations.fee_charged > COALESCE((SELECT SUM(p.amount_paid) FROM payments p WHERE p.consultation_id = consultations.id), 0)'
        );
    }
}
